// logger.ts — log terpusat, ditulis langsung ke file di folder root aplikasi
//
//   Lokasi : <folder node-printer.exe>\node-printer.log   (dev: folder project)
//            bisa diganti lewat env NODE_PRINTER_LOG_DIR
//   Reset  : file dihapus & dibuat ulang otomatis setiap 5 hari
//   Aman   : token & body request TIDAK PERNAH ditulis (hanya method, URL, status, durasi)
import { appendFile, open, writeFile } from "node:fs/promises";
import { dirname, join } from "node:path";

/* ───────────── Konfigurasi ───────────── */

const COMPILED = Bun.main.includes("~BUN") || Bun.main.includes("$bunfs");
export const ROOT_DIR =
  process.env.NODE_PRINTER_LOG_DIR ?? (COMPILED ? dirname(process.execPath) : dirname(Bun.main));
export const LOG_FILE = join(ROOT_DIR, "node-printer.log");

const MAX_AGE_MS = 5 * 24 * 60 * 60 * 1000; // 5 hari
const HEADER_TAG = "# log-started: ";

export type Level = "INFO" | "WARN" | "ERROR" | "REQ" | "RES" | "SSE";

// sumber log: daemon (background) atau tui (jendela setup / command CLI)
const SOURCE = process.argv.includes("--daemon") ? "daemon" : "tui";

/* ───────────── Internal ───────────── */

let startedAt: number | null = null; // waktu mulai "lembar" log saat ini
let disabled = false;
let queue: Promise<void> = Promise.resolve();

const pad = (n: number, w = 2) => String(n).padStart(w, "0");

function stamp(d = new Date()) {
  return (
    `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ` +
    `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}.${pad(d.getMilliseconds(), 3)}`
  );
}

/** Baca waktu mulai dari baris pertama file; null kalau file/header tidak ada. */
async function readStart(): Promise<number | null> {
  try {
    const fh = await open(LOG_FILE, "r");
    try {
      const buf = Buffer.alloc(80);
      const { bytesRead } = await fh.read(buf, 0, 80, 0);
      const first = buf.subarray(0, bytesRead).toString("utf8").split("\n")[0] ?? "";
      if (!first.startsWith(HEADER_TAG)) return null;
      const t = Date.parse(first.slice(HEADER_TAG.length).trim());
      return Number.isNaN(t) ? null : t;
    } finally {
      await fh.close();
    }
  } catch {
    return null;
  }
}

function header(startMs: number) {
  return (
    `${HEADER_TAG}${new Date(startMs).toISOString()}\n` +
    `# format : WAKTU(lokal) [LEVEL] [sumber:pid] pesan\n` +
    `# level  : INFO | WARN | ERROR | REQ (request keluar) | RES (response) | SSE (event masuk)\n` +
    `# sumber : daemon = background, tui = jendela setup / command CLI\n` +
    `# reset  : file dihapus & dibuat ulang otomatis tiap 5 hari\n` +
    `# privasi: token dan isi body request tidak pernah dicatat\n` +
    `#\n`
  );
}

async function write(line: string) {
  if (disabled) return;
  const now = Date.now();

  if (startedAt === null) startedAt = await readStart();
  // proses lain mungkin sudah mereset file -> sinkronkan dulu sebelum menghapus
  if (startedAt !== null && now - startedAt >= MAX_AGE_MS) startedAt = await readStart();

  if (startedAt === null || now - startedAt >= MAX_AGE_MS) {
    startedAt = now;
    await writeFile(LOG_FILE, header(now), "utf8"); // mulai dari awal
  }
  await appendFile(LOG_FILE, line + "\n", "utf8");
}

/* ───────────── API ───────────── */

export function log(msg: string, level: Level = "INFO"): Promise<void> {
  const line = `${stamp()} [${level.padEnd(5)}] [${SOURCE}:${process.pid}] ${msg}`;
  queue = queue.then(() => write(line)).catch(() => {}); // log tidak boleh membuat app crash
  return queue;
}

/** Dipakai uninstall supaya file log tidak terbuat lagi setelah dihapus. */
export function disableLogging() {
  disabled = true;
}

let seq = 0;

/** fetch yang otomatis mencatat request & response (tanpa token/body). */
export async function loggedFetch(url: string, init: RequestInit = {}, label = ""): Promise<Response> {
  const id = ++seq;
  const method = (init.method ?? "GET").toUpperCase();
  const t0 = Date.now();
  await log(`#${id} ${method} ${url}${label ? ` (${label})` : ""}`, "REQ");
  try {
    const res = await fetch(url, init);
    await log(
      `#${id} ${res.status} ${res.statusText} | ${res.headers.get("content-type") ?? "-"} | ${Date.now() - t0}ms`,
      res.ok ? "RES" : "WARN"
    );
    return res;
  } catch (e) {
    const aborted = e instanceof Error && e.name === "AbortError";
    const msg = e instanceof Error ? e.message : String(e);
    await log(
      `#${id} ${aborted ? "dibatalkan" : "GAGAL"}: ${msg} | ${Date.now() - t0}ms`,
      aborted ? "WARN" : "ERROR"
    );
    throw e;
  }
}
