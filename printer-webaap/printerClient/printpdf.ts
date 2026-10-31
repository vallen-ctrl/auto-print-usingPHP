// printpdf.ts — cetak PDF lewat SumatraPDF (portable) memakai konfigurasi dari server
//
// Khusus Windows.
// Alur: ambil config (/api/files/conf/getid) -> pakai printer terpilih dengan prioritas tertinggi
//       -> susun argumen CLI -> jalankan SumatraPDF -> tunggu selesai
//
// Contoh hasil:
//   SumatraPDF.exe -print-to "EPSON L3210 Series" -silent -exit-when-done
//     -print-settings "paper=210mm x 297mm,portrait,monochrome,1-3,2x" "C:\Users\kamu\Downloads\file.pdf"
import { spawn } from "node:child_process";
import { existsSync, readdirSync } from "node:fs";
import { dirname, join } from "node:path";
import { findPrinterByName, listPrinters } from "./printers";
import type { CommandContext } from "./see";

/* ───────────── Konfigurasi ───────────── */

const CONF_PATH = "/api/files/conf/getid";
const PRINT_TIMEOUT = 120_000; // 2 menit per file

/** Nama exe yang dicari (urut). Selain itu, file "SumatraPDF*.exe" apa pun juga dikenali. */
const EXE_NAMES = ["PrinterClient.exe", "SumatraPDF.exe"];

/** true: 210x297 -> "paper=A4" (nama ukuran yang dikenal SumatraPDF); false: selalu "paper=210mm x 297mm" */
const PAPER_AS_NAME = false;
/**
 * `pages_toprint` dari server = JUMLAH halaman yang dicetak (bukan nomor halaman).
 * true  -> pages_toprint=3 jadi "1-3" (semua halaman 1..3)  <-- dipakai
 * false -> "3" (SumatraPDF hanya mencetak halaman 3)
 */
const PAGES_AS_RANGE = true;

/* ───────────── Tipe config dari server ───────────── */

export interface PrintConf {
  orientasi?: string | null; // portrait | landscape
  warna?: string | null; // berwarna | hitam putih
  jumlah_cetak?: number | string | null;
  custom_halaman?: string | null;
  pages_toprint?: number | string | null;
  paper_width?: number | string | null; // mm
  paper_height?: number | string | null; // mm
  [k: string]: unknown;
}

/* ───────────── Susun argumen ───────────── */

const NAMED_PAPER: [string, number, number][] = [
  ["A2", 420, 594], ["A3", 297, 420], ["A4", 210, 297], ["A5", 148, 210], ["A6", 105, 148],
  ["letter", 216, 279], ["legal", 216, 356], ["tabloid", 279, 432], ["statement", 140, 216],
];

function namedPaper(w: number, h: number): string | null {
  const near = (a: number, b: number) => Math.abs(a - b) <= 2;
  for (const [name, pw, ph] of NAMED_PAPER) {
    if ((near(w, pw) && near(h, ph)) || (near(w, ph) && near(h, pw))) return name;
  }
  return null;
}

const norm = (v: unknown) => String(v ?? "").trim().toLowerCase().replace(/[_-]+/g, " ").replace(/\s+/g, " ");
const num = (v: unknown) => {
  const n = Number(v);
  return Number.isFinite(n) ? n : 0;
};

/** Isi nilai untuk -print-settings, contoh: paper=210mm x 297mm,portrait,monochrome,1-3,2x */
export function buildSettings(conf: PrintConf, warn: (m: string) => void = () => {}): string {
  const parts: string[] = [];

  // 1. ukuran kertas: WAJIB dari paper_width x paper_height (kolom "ukuran" diabaikan)
  const w = num(conf.paper_width), h = num(conf.paper_height);
  if (w > 0 && h > 0) {
    const mm = `${w}mm x ${h}mm`;
    parts.push(`paper=${PAPER_AS_NAME ? namedPaper(w, h) ?? mm : mm}`);
  }

  // 2. orientasi
  const ori = norm(conf.orientasi);
  if (ori === "portrait" || ori === "landscape") parts.push(ori);

  // 3. warna
  const warna = norm(conf.warna);
  if (warna === "berwarna") parts.push("color");
  else if (warna === "hitam putih") parts.push("monochrome");

  // 4. halaman: custom_halaman > pages_toprint > (kosong = semua)
  const custom = String(conf.custom_halaman ?? "").replace(/\s+/g, "");
  const pt = Math.trunc(num(conf.pages_toprint));
  if (custom) {
    if (/^[0-9]+(-[0-9]+)?(,[0-9]+(-[0-9]+)?)*$/.test(custom)) parts.push(custom);
    else warn(`custom_halaman "${custom}" tidak valid, diabaikan (cetak semua halaman)`);
  } else if (pt > 0) {
    parts.push(PAGES_AS_RANGE ? `1-${pt}` : String(pt));
  }

  // 5. jumlah salinan
  const copies = Math.trunc(num(conf.jumlah_cetak));
  if (copies > 1) parts.push(`${copies}x`);

  // kualitas diabaikan: SumatraPDF tidak punya opsi dpi untuk -print-settings
  return parts.join(",");
}

/* ───────────── Cari SumatraPDF ───────────── */

export function findSumatra(): string | null {
  const dirs = [
    process.env.NODE_PRINTER_SUMATRA_DIR,
    dirname(process.execPath),
    process.cwd(),
    join(process.cwd(), "tools"),
  ].filter((d): d is string => !!d && existsSync(d));

  const fromEnv = process.env.NODE_PRINTER_SUMATRA;
  if (fromEnv && existsSync(fromEnv)) return fromEnv;

  for (const d of dirs) {
    for (const n of EXE_NAMES) if (existsSync(join(d, n))) return join(d, n);
    const any = readdirSync(d).find((f) => /^sumatrapdf.*\.exe$/i.test(f));
    if (any) return join(d, any);
  }
  return null;
}

/* ───────────── Printer down (offline / tidak terdeteksi) ───────────── */

/**
 * Printer tidak siap dipakai: tidak terdaftar di Windows, atau statusnya offline.
 * Dipakai Commands.ts untuk membedakan "printer down" (status file -> failed)
 * dari error cetak lain, walaupun keduanya tetap ditandai gagal.
 */
export class PrinterDownError extends Error {
  readonly printer: string;
  readonly reason: string;

  constructor(printer: string, reason: string) {
    super(`printer "${printer}" sedang down (${reason})`);
    this.name = "PrinterDownError";
    this.printer = printer;
    this.reason = reason;
  }
}

export const isPrinterDown = (e: unknown): e is PrinterDownError => e instanceof PrinterDownError;

/* ───────────── Config dari server & pilih printer ───────────── */

async function fetchConf(fileid: string, ctx: CommandContext): Promise<PrintConf> {
  // POST /api/files/conf/getid | Authorization: Bearer <token> | body: { fileid }
  const res = await ctx.request(CONF_PATH, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({ fileid }),
    signal: AbortSignal.timeout(15_000),
  });
  const raw = await res.text();
  if (!res.ok) throw new Error(`config HTTP ${res.status}: ${raw.slice(0, 200)}`);

  let j: any;
  try { j = JSON.parse(raw); } catch { throw new Error("config bukan JSON: " + raw.slice(0, 120)); }
  if (j?.iseror) throw new Error(j.message || "server mengembalikan error untuk config");

  // bentuk respons bisa langsung objek, {data:{...}}, atau [ {...} ]
  let c = j?.data ?? j?.config ?? j;
  if (Array.isArray(c)) c = c[0];
  if (!c || typeof c !== "object") throw new Error("config kosong");
  return c as PrintConf;
}

const errMsgLocal = (e: unknown) => (e instanceof Error ? e.message : String(e));

/** Printer prioritas 1 (yang akan dipakai cetak). */
function topPrinter(ctx: CommandContext) {
  return [...ctx.printers].sort((a, b) => a.priority - b.priority)[0];
}

/**
 * Cek kesiapan printer prioritas 1 tanpa mencetak apa pun.
 * return PrinterDownError kalau printer down; null kalau siap / status tidak bisa dipastikan.
 */
export async function probePrinter(ctx: CommandContext): Promise<PrinterDownError | null> {
  const top = topPrinter(ctx);
  if (!top) return null; // biar pickPrinter yang memberi pesan "belum ada printer dipilih"

  try {
    const found = findPrinterByName(await listPrinters(), top.name);
    if (!found) return new PrinterDownError(top.name, "tidak terdeteksi di Windows");
    if (!found.online) return new PrinterDownError(top.name, found.detail);
    return null;
  } catch (e) {
    if (e instanceof PrinterDownError) return e;
    // pengecekan gagal (mis. PowerShell bermasalah): jangan blokir cetak, cukup catat
    await ctx.log(`peringatan: status printer "${top.name}" tidak bisa diperiksa: ${errMsgLocal(e)}`);
    return null;
  }
}

/** Printer terpilih dengan prioritas tertinggi (priority 1). Tidak ada fallback ke printer lain. */
async function pickPrinter(ctx: CommandContext): Promise<string> {
  const top = topPrinter(ctx);
  if (!top) {
    throw new Error('belum ada printer yang dipilih. Buka menu Printer > "Select printer" lalu atur prioritas');
  }

  // printer offline / tidak terpasang: cetak jangan dikirim, status file harus "failed"
  const down = await probePrinter(ctx);
  if (down) throw down;

  await ctx.log(`printer: "${top.name}" siap`);
  return top.name;
}

/* ───────────── Jalankan ───────────── */

function run(exe: string, args: string[]): Promise<number> {
  return new Promise((resolve, reject) => {
    const p = spawn(exe, args, { stdio: "ignore", windowsHide: true });
    const t = setTimeout(() => {
      p.kill();
      reject(new Error(`SumatraPDF tidak selesai dalam ${PRINT_TIMEOUT / 1000} detik, dihentikan`));
    }, PRINT_TIMEOUT);
    p.once("error", (e) => { clearTimeout(t); reject(e); });
    p.once("exit", (code) => { clearTimeout(t); resolve(code ?? 0); });
  });
}

// cetak berurutan: satu job selesai dulu, baru job berikutnya
let queue: Promise<unknown> = Promise.resolve();

/** Ambil config dari server lalu cetak `file` lewat SumatraPDF. Throw kalau gagal. */
export function printFile(file: string, fileid: string, ctx: CommandContext): Promise<void> {
  const job = queue.then(async () => {
    if (process.platform !== "win32") throw new Error("print hanya didukung di Windows");
    const exe = findSumatra();
    if (!exe) {
      throw new Error(
        "SumatraPDF tidak ditemukan. Taruh SumatraPDF.exe (portable) di sebelah node-printer.exe, " +
        "atau set env NODE_PRINTER_SUMATRA=<path exe>"
      );
    }
    if (!existsSync(file)) throw new Error(`file tidak ada: ${file}`);

    const conf = await fetchConf(fileid, ctx);
    const settings = buildSettings(conf, (m) => void ctx.log("print: " + m));
    const printer = await pickPrinter(ctx);

    const args = [
      "-print-to", printer,
      "-silent",
      "-exit-when-done",
      ...(settings ? ["-print-settings", settings] : []),
      file,
    ];

    // hanya untuk log (token tidak ada di sini)
    const shown = args.map((a) => (/[\s,]/.test(a) ? `"${a}"` : a)).join(" ");
    await ctx.log(`print: ${exe.split(/[\\/]/).pop()} ${shown}`);

    const code = await run(exe, args);
    if (code !== 0) throw new Error(`SumatraPDF exit code ${code}`);
    await ctx.log(`print selesai: ${file}`);
  });
  queue = job.catch(() => {}); // satu job gagal tidak menghentikan antrean
  return job;
}
