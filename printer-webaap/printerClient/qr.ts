// qr.ts — ambil HTML QR dari server, lalu tampilkan lewat window Tauri (tauri-qr.exe)
// Alur: Bun request ke server -> simpan HTML ke file temp -> jalankan tauri-qr.exe <file>
//       -> window ditutup = proses Tauri selesai = RAM langsung bebas.
import { spawn } from "node:child_process";
import { existsSync } from "node:fs";
import { writeFile, rm } from "node:fs/promises";
import { tmpdir } from "node:os";
import { dirname, join } from "node:path";
import { loadConfig } from "./daemon";

/* ───────────── Konfigurasi ───────────── */

export const QR_PATH = "/api/computer/scanqr";
const QR_BIN_NAME = "tauri-qr.exe";

/** Atur cara kirim token ke server di sini (samakan dengan see.ts kalau perlu). */
function buildRequest(token: string): RequestInit {
  return {
    method: "POST",
    headers: { Accept: "text/html", "Content-Type": "application/json" },
    body: JSON.stringify({ token }),
  };
}

/* ───────────── Helpers ───────────── */

/** Cari tauri-qr.exe: env > sebelah .exe ini > folder kerja > hasil build cargo */
export function findQrBinary(): string | null {
  const candidates = [
    process.env.NODE_PRINTER_QR_BIN,
    join(dirname(process.execPath), QR_BIN_NAME),
    join(process.cwd(), QR_BIN_NAME),
    join(process.cwd(), "tauri-qr", "src-tauri", "target", "release", QR_BIN_NAME),
  ];
  return candidates.find((p): p is string => !!p && existsSync(p)) ?? null;
}

async function fetchQrHtml(host: string, token: string): Promise<string> {
  const res = await fetch(host + QR_PATH, {
    ...buildRequest(token),
    signal: AbortSignal.timeout(10_000),
  });
  const body = await res.text();
  if (!res.ok) throw new Error(`HTTP ${res.status}: ${body.slice(0, 200)}`);
  if (!body.trim()) throw new Error("Response kosong dari server");

  // server membalas JSON (mis. token salah) -> tampilkan pesannya
  if (body.trimStart().startsWith("{")) {
    let j: any = null;
    try { j = JSON.parse(body); } catch {}
    if (j) throw new Error(j.iseror ? j.message || "Server mengembalikan error" : "Response bukan HTML");
  }
  return body;
}

/** Supaya path relatif (img/script/css) di HTML server tetap nyambung ke server. */
function withBase(html: string, host: string): string {
  if (/<base\s/i.test(html)) return html;
  const tag = `<base href="${host}/">`;
  return /<head[^>]*>/i.test(html) ? html.replace(/<head[^>]*>/i, (m) => m + tag) : tag + html;
}

/* ───────────── API ───────────── */

export type QrJob = { bin: string; file: string };

/** Langkah 1: request ke server dulu. Tauri baru dijalankan kalau ini sukses. */
export async function prepareQr(): Promise<QrJob> {
  const bin = findQrBinary();
  if (!bin) {
    throw new Error(
      `${QR_BIN_NAME} tidak ditemukan. Build: bun run build:qr, lalu taruh di sebelah node-printer.exe`
    );
  }
  const cfg = await loadConfig();
  if (!cfg) throw new Error('Belum dipairing. Pilih menu "Konek ke server" dulu.');

  const html = withBase(await fetchQrHtml(cfg.host, cfg.token), cfg.host);
  const file = join(tmpdir(), `node-printer-qr-${process.pid}.html`);
  await writeFile(file, html, "utf8");
  return { bin, file };
}

/** Langkah 2: buka window Tauri, tunggu sampai ditutup, lalu bersihkan file temp. */
export async function showQr(job: QrJob): Promise<void> {
  try {
    await new Promise<void>((resolve, reject) => {
      const p = spawn(job.bin, [job.file], { stdio: "ignore" });
      p.once("error", reject);
      p.once("exit", () => resolve());
    });
  } finally {
    await rm(job.file, { force: true });
  }
}
