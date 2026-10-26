// commands.ts — FILE INI YANG KAMU EDIT untuk custom command dari server (SSE)
//
// Cara routing:
//   1. SSE dengan field `event: nama`       -> commands["nama"]
//   2. SSE tanpa event (message biasa) dan data JSON punya `command` atau `type`
//        data: {"command":"print","file":"x.pdf"}  -> commands["print"]
//   3. Tidak ada yang cocok                 -> onUnknown()
//
// `data` sudah di-parse JSON kalau valid, kalau tidak berupa string mentah.
import { createWriteStream, existsSync, renameSync } from "node:fs";
import { mkdir, readFile, rm, writeFile } from "node:fs/promises";
import { homedir } from "node:os";
import { dirname, join, parse, resolve, sep } from "node:path";
import { Readable } from "node:stream";
import { pipeline } from "node:stream/promises";
import type { CommandContext, SSEEvent } from "./see";
import { isPrinterDown, printFile, probePrinter } from "./printpdf";

type Handler = (data: any, ctx: CommandContext, ev: SSEEvent) => void | Promise<void>;

/* ═════════════════════ DOWNLOAD & DELETE FILE ═════════════════════ */

const DOWNLOAD_DIR = join(homedir(), "Downloads");
const DOWNLOAD_TIMEOUT = 10 * 60_000; // 10 menit per file
const PARALLEL = 3; // maksimal download bersamaan
const AUTO_PRINT = true; // true: setelah download langsung dicetak lewat SumatraPDF (lihat printpdf.ts)
const SKIP_IF_DOWNLOADED = true; // id yang filenya masih ada di Downloads tidak diunduh ulang

/** Kalau nama file sudah dipakai file lain: "rename" -> nama (1).ext | "overwrite" -> timpa */
const ON_CONFLICT: "rename" | "overwrite" = "rename";

/* ── Nama file: TIDAK diubah, kecuali melanggar aturan penamaan Windows ── */

const INVALID_CHARS = /[<>:"/\\|?*\x00-\x1f]/g; // karakter terlarang
const RESERVED = /^(con|prn|aux|nul|com[0-9]|lpt[0-9])$/i; // nama perangkat (CON.txt juga terlarang)

function windowsSafeName(name: string, fallback: string): string {
  // sisakan ruang untuk " (99)" dan batas path Windows 260 karakter
  const maxLen = Math.max(20, Math.min(255, 259 - DOWNLOAD_DIR.length - 1 - 6));

  let n = name.replace(INVALID_CHARS, "_"); // 1. karakter terlarang -> "_"
  n = n.replace(/[. ]+$/, ""); //              2. tidak boleh diakhiri titik / spasi
  if (!n) return fallback;
  if (RESERVED.test(n.split(".")[0]!.trimEnd())) n = "_" + n; // 3. CON, NUL, COM1, LPT1, ...
  if (n.length > maxLen) { //                  4. kepanjangan -> potong, ekstensi dipertahankan
    const { name: base, ext } = parse(n);
    n = base.slice(0, Math.max(1, maxLen - ext.length)) + ext;
  }
  return n;
}

/** Ambil nama file dari header Content-Disposition (mendukung filename*= UTF-8) */
function filenameFromHeader(h: string | null): string | null {
  if (!h) return null;
  const star = /filename\*\s*=\s*UTF-8''([^;]+)/i.exec(h);
  if (star) {
    try { return decodeURIComponent(star[1]!.trim()); } catch {}
  }
  const m = /filename\s*=\s*"?([^";]+)"?/i.exec(h);
  return m ? m[1]!.trim() : null;
}

function targetPath(name: string): string {
  const p = join(DOWNLOAD_DIR, name);
  if (ON_CONFLICT === "overwrite" || !existsSync(p)) return p;
  const { name: base, ext } = parse(name);
  let i = 1, q = p;
  while (existsSync(q)) q = join(DOWNLOAD_DIR, `${base} (${i++})${ext}`);
  return q;
}

/* ── Catatan id -> lokasi file (supaya file bisa dihapus lewat id) ── */

type Rec = { path: string; name: string; at: string };

const REC_FILE = join(homedir(), ".node-printer", "downloaded.json");
const REC_MAX = 2000; // simpan 2000 catatan terbaru
let recs: Record<string, Rec> | null = null;
let recWrite: Promise<unknown> = Promise.resolve();

async function loadRecs(): Promise<Record<string, Rec>> {
  if (recs) return recs;
  try {
    const j = JSON.parse(await readFile(REC_FILE, "utf8"));
    recs = j && typeof j === "object" && !Array.isArray(j) ? j : {};
  } catch {
    recs = {};
  }
  return recs!;
}

async function saveRecs() {
  const all = await loadRecs();
  const keys = Object.keys(all);
  if (keys.length > REC_MAX) {
    keys.sort((a, b) => all[a]!.at.localeCompare(all[b]!.at)); // buang yang paling lama
    for (const k of keys.slice(0, keys.length - REC_MAX)) delete all[k];
  }
  const snapshot = JSON.stringify(all);
  // tulis berurutan supaya file tidak rusak kalau beberapa download selesai bersamaan
  recWrite = recWrite
    .then(async () => {
      await mkdir(dirname(REC_FILE), { recursive: true });
      await writeFile(REC_FILE, snapshot, "utf8");
    })
    .catch(() => {});
  await recWrite;
}

/* ── Helper umum ── */

/** Ambil daftar id dari data: {fileid} | {fileids:[..]} | {data:[..]} | "id" */
function idsFrom(data: any): string[] {
  const raw = data?.fileid ?? data?.fileids ?? data?.data ?? (typeof data === "string" || typeof data === "number" ? data : undefined);
  const arr = Array.isArray(raw) ? raw : raw !== undefined && raw !== null && raw !== "" ? [raw] : [];
  return [...new Set(arr.map(String).filter(Boolean))];
}

/** Jalankan fn untuk tiap item, maksimal `size` sekaligus */
async function runPool<T>(items: T[], size: number, fn: (x: T) => Promise<void>) {
  let i = 0;
  await Promise.all(
    Array.from({ length: Math.min(size, items.length) }, async () => {
      while (i < items.length) await fn(items[i++]!);
    })
  );
}

const errMsg = (e: unknown) => (e instanceof Error ? e.message : String(e));

/* ── Download ── */

async function downloadFile(fileid: string, ctx: CommandContext): Promise<string> {
  // POST /api/files/getid  |  Authorization: Bearer <token>  |  body: { fileid }
  const res = await ctx.request("/api/files/getid", {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "*/*" },
    body: JSON.stringify({ fileid }),
    signal: AbortSignal.timeout(DOWNLOAD_TIMEOUT),
  });

  if (!res.ok) {
    const t = (await res.text().catch(() => "")).slice(0, 200);
    throw new Error(`HTTP ${res.status}: ${t}`);
  }
  if (!res.body) throw new Error("Response tidak punya body");

  await mkdir(DOWNLOAD_DIR, { recursive: true });
  const original = filenameFromHeader(res.headers.get("content-disposition")) ?? `file-${fileid}`;
  const name = windowsSafeName(original, `file-${fileid}`);
  const tmp = join(DOWNLOAD_DIR, `.nodeprinter-${fileid.slice(0, 40)}.part`); // nama sementara

  try {
    // streaming ke disk: file besar tidak memenuhi RAM
    await pipeline(Readable.fromWeb(res.body as any), createWriteStream(tmp));
    // nama final dipilih & di-rename dalam satu langkah sinkron -> aman walau download paralel
    const dest = targetPath(name);
    renameSync(tmp, dest);

    const all = await loadRecs();
    all[fileid] = { path: dest, name: parse(dest).base, at: new Date().toISOString() };
    await saveRecs();
    return dest;
  } catch (e) {
    await rm(tmp, { force: true });
    throw e;
  }
}

/* ── Status file → server (/api/files/update/status) ── */

/** Nilai persis seperti enum `file_queue.status` di server: pending, processing, completed, failed, downloading. */
const STATUS = {
  pending: "pending", //         masuk antrean, belum mulai
  downloading: "downloading", // sedang diunduh
  processing: "processing", //   sudah diunduh, sedang dicetak / menunggu giliran cetak
  done: "completed", //          selesai (dicetak)
  failed: "failed", //           error (unduh / config / cetak / printer down)
} as const;
type FileStatus = (typeof STATUS)[keyof typeof STATUS];

/** PUT status ke server. Gagal kirim status TIDAK menghentikan proses (dicoba 3x, lalu dicatat di log). */
async function setStatus(ctx: CommandContext, fileid: string, status: FileStatus): Promise<void> {
  const waits = [0, 500, 1500]; // jeda sebelum percobaan ke-1, 2, 3
  let last = "";
  for (const wait of waits) {
    if (wait) await new Promise((r) => setTimeout(r, wait));
    try {
      const res = await ctx.request("/api/files/update/status", {
        method: "PUT",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ fileid, status }),
        signal: AbortSignal.timeout(15_000),
      });
      if (res.ok) return;
      last = `HTTP ${res.status}: ${(await res.text().catch(() => "")).slice(0, 150)}`;
      if (res.status >= 400 && res.status < 500) break; // salah input, percuma diulang
    } catch (e) {
      last = errMsg(e);
    }
  }
  await ctx.log(`  ! status "${status}" untuk ${fileid} gagal terkirim: ${last}`);
}

/**
 * mode "download": unduh, lalu cetak (kalau AUTO_PRINT).
 *                 File yang sudah ada dipakai ulang (tidak diunduh lagi) dan tetap dicetak,
 *                 supaya "retry" dari dashboard benar-benar mencetak ulang file yang gagal.
 * mode "print"   : cetak ulang file yang sudah diunduh (kalau belum ada, diunduh dulu).
 */
async function processFiles(ids: string[], ctx: CommandContext, mode: "download" | "print") {
  const all = await loadRecs();
  let downloaded = 0, printed = 0, skipped = 0, fail = 0, down = 0;

  const alreadyHave = (id: string) => !!all[id] && existsSync(all[id]!.path);
  const willPrint = mode === "print" || AUTO_PRINT;
  /** file lama di Downloads dipakai ulang (tidak diunduh lagi) */
  const useLocal = (id: string) => mode === "download" && SKIP_IF_DOWNLOADED && alreadyHave(id);
  // tanpa AUTO_PRINT tidak ada yang dicetak: file yang sudah ada memang tidak perlu diproses
  const todo = ids.filter((id) => !(useLocal(id) && !willPrint));

  // printer dicek sekali di awal: kalau down, file langsung "failed" tanpa diunduh / dicetak
  if (willPrint && todo.length) {
    const downErr = await probePrinter(ctx);
    if (downErr) {
      await ctx.log(`printer down: ${downErr.message}`);
      await Promise.all(todo.map((id) => setStatus(ctx, id, STATUS.failed)));
      return void (await ctx.log(
        `selesai: 0 diunduh, 0 dicetak, ${skipped} dilewati, ${todo.length} gagal (printer down)`
      ));
    }
  }

  // semua yang akan diproses: masuk antrean dulu (pending)
  await Promise.all(todo.map((id) => setStatus(ctx, id, STATUS.pending)));

  await runPool(ids, PARALLEL, async (id) => {
    const rec = all[id];
    const have = alreadyHave(id);

    if (useLocal(id)) {
      if (!willPrint) {
        skipped++;
        return void (await ctx.log(`  ↷ ${id} sudah ada: ${rec!.path} (dilewati, AUTO_PRINT mati)`));
      }
      // mis. setelah retry dari dashboard: jangan unduh ulang, pakai file yang ada lalu cetak
      await ctx.log(`  ↷ ${id} sudah ada: ${rec!.path} (pakai file lokal, cetak ulang)`);
    }

    let path = rec?.path ?? "";
    if (!have) {
      await setStatus(ctx, id, STATUS.downloading);
      try {
        path = await downloadFile(id, ctx);
        downloaded++;
        await ctx.log(`  ✓ ${id} -> ${path}`);
      } catch (e) {
        fail++;
        await ctx.log(`  ✗ ${id}: ${errMsg(e)}`);
        return void (await setStatus(ctx, id, STATUS.failed));
      }
    }

    if (willPrint) {
      await setStatus(ctx, id, STATUS.processing);
      try {
        await printFile(path, id, ctx);
        printed++;
        await setStatus(ctx, id, STATUS.done); // sudah selesai dicetak
      } catch (e) {
        fail++;
        if (isPrinterDown(e)) {
          // printer mati / kabel putus / tidak terpasang: jangan biarkan nyangkut di "processing"
          down++;
          await ctx.log(`  ✗ ${id} gagal dicetak: ${errMsg(e)} -> status failed`);
        } else {
          await ctx.log(`  ✗ ${id} gagal dicetak: ${errMsg(e)} (file tetap tersimpan)`);
        }
        await setStatus(ctx, id, STATUS.failed);
      }
    } else {
      await setStatus(ctx, id, STATUS.done); // AUTO_PRINT mati: selesai = selesai diunduh
    }
  });
  await ctx.log(
    `selesai: ${downloaded} diunduh, ${printed} dicetak, ${skipped} dilewati, ${fail} gagal` +
      (down ? ` (${down} karena printer down)` : "")
  );
}

/* ── Delete (pakai id file) ── */

async function deleteFiles(ids: string[], ctx: CommandContext) {
  const all = await loadRecs();
  const root = resolve(DOWNLOAD_DIR);
  const norm = (p: string) => (process.platform === "win32" ? p.toLowerCase() : p);
  let ok = 0, miss = 0, fail = 0;

  for (const id of ids) {
    const rec = all[id];
    if (!rec) {
      miss++;
      await ctx.log(`  ? ${id}: tidak ada catatan unduhan`);
      continue;
    }
    const full = resolve(rec.path);
    // pengaman: hanya boleh menghapus file di dalam folder Downloads
    if (!norm(full).startsWith(norm(root + sep))) {
      fail++;
      await ctx.log(`  ✗ ${id}: ditolak, di luar folder Downloads (${full})`);
      continue;
    }
    try {
      const existed = existsSync(full);
      await rm(full, { force: true });
      delete all[id];
      await saveRecs();
      ok++;
      await ctx.log(existed ? `  ✓ ${id} dihapus: ${full}` : `  ✓ ${id}: file sudah tidak ada, catatan dibersihkan`);
    } catch (e) {
      fail++;
      await ctx.log(`  ✗ ${id}: ${errMsg(e)}`);
    }
  }
  await ctx.log(`delete selesai: ${ok} berhasil, ${miss} tanpa catatan, ${fail} gagal`);
}

/* ═════════════════════ DAFTAR COMMAND ═════════════════════ */

export const commands: Record<string, Handler> = {
  // event bawaan, boleh dihapus/diubah
  connected: async (_data, ctx) => {
    await ctx.log("Server: connected");
  },
  heartbeat: () => {},

  ping: async (_data, ctx) => {
    await ctx.log("ping diterima");
  },

  // SSE:  event: download   data: {"fileid": "01a171f0..."}   atau   {"fileid": ["id1","id2"]}
  download: async (data, ctx) => {
    const ids = idsFrom(data);
    if (!ids.length) return void (await ctx.log('download: "fileid" kosong, dilewati'));
    await ctx.log(`download mulai: ${ids.length} file`);
    await processFiles(ids, ctx, "download");
  },

  // SSE:  event: delete     data: {"fileid": "01a171f0..."}   atau   {"fileid": ["id1","id2"]}
  delete: async (data, ctx) => {
    const ids = idsFrom(data);
    if (!ids.length) return void (await ctx.log('delete: "fileid" kosong, dilewati'));
    await ctx.log(`delete mulai: ${ids.length} file`);
    await deleteFiles(ids, ctx);
  },

  // SSE:  event: print   data: {"fileid": "01a171f0..."}   -> cetak ulang file yang sudah diunduh
  print: async (data, ctx) => {
    const ids = idsFrom(data);
    if (!ids.length) return void (await ctx.log('print: "fileid" kosong, dilewati'));
    await ctx.log(`print mulai: ${ids.length} file`);
    await processFiles(ids, ctx, "print");
  },

  // tambah command baru di sini:
  // restart: async (data, ctx) => { ... },
};

/** Dipanggil tiap SSE berhasil tersambung (termasuk reconnect). */
export async function onConnected(ctx: CommandContext) {
  await ctx.log("SSE tersambung");
}

/** Dipanggil tiap SSE putus; daemon otomatis mencoba menyambung lagi. */
export async function onDisconnected(ctx: CommandContext, reason: string) {
  await ctx.log(`SSE putus: ${reason}`);
}

/** Dipanggil kalau event tidak punya handler di `commands`. */
export async function onUnknown(ev: SSEEvent, data: unknown, ctx: CommandContext) {
  await ctx.log(`Command tidak dikenal "${ev.event}": ${ev.data.slice(0, 200)}`);
}
