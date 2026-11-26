// setup.ts — Node Printer Setup (Bun + @clack/prompts)
import {
  intro, outro, note, log, text, select, multiselect,
  confirm, spinner, isCancel, cancel,
} from "@clack/prompts";
import pc from "picocolors";
import readline from "node:readline";
import { mkdir, writeFile, readFile, chmod } from "node:fs/promises";
import { existsSync } from "node:fs";
import { homedir, platform } from "node:os";
import { join } from "node:path";
import { createCipheriv, randomBytes, scryptSync } from "node:crypto";
import { uninstallAll, IS_COMPILED, isDaemonRunning, stopDaemon } from "./daemon";
import { openSSE, readSSE } from "./see";
import { prepareQr, showQr } from "./qr";
import { log as writeLog } from "./logger";
import { listPrinters, type Printer } from "./printers";

/* ───────────────────────── Config & Paths ───────────────────────── */

const CONF_DIR = join(homedir(), ".node-printer");
const SERVER_CONF = join(CONF_DIR, "server.conf");     // host (plain JSON)
const TOKEN_FILE = join(CONF_DIR, "token.enc");        // token (terenkripsi)
const PRINTER_CONF = join(CONF_DIR, "printers.conf");
const STATUS_FILE = join(CONF_DIR, "status.json");  // state daemon (ditulis daemon)

// TODO: ganti sumber key enkripsi sesuai keputusanmu nanti
// (env var / machine-id / hardcode di build, dll)
const ENC_SECRET = process.env.NODE_PRINTER_SECRET ?? "CHANGE_ME_LATER";


interface ApiResponse {
  message: string;
  iseror: boolean;
  data: { status: string };
  responsesCode: number;
}

type PrinterConf = { name: string; priority: number }; // 1 = dipakai pertama

/* ───────────────────────── Helpers ───────────────────────── */

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));

/** Tangani cancel (Ctrl+C / Esc) -> balik ke menu, bukan crash */
function cancelled<T>(v: T | symbol): v is symbol {
  if (isCancel(v)) {
    log.warn(pc.yellow("Dibatalkan, kembali ke menu..."));
    return true;
  }
  return false;
}

async function ensureDir() {
  await mkdir(CONF_DIR, { recursive: true });
}

function encrypt(plain: string): string {
  const salt = randomBytes(16);
  const key = scryptSync(ENC_SECRET, salt, 32);
  const iv = randomBytes(12);
  const cipher = createCipheriv("aes-256-gcm", key, iv);
  const enc = Buffer.concat([cipher.update(plain, "utf8"), cipher.final()]);
  const tag = cipher.getAuthTag();
  // format: salt(16) | iv(12) | tag(16) | ciphertext
  return Buffer.concat([salt, iv, tag, enc]).toString("base64");
}

async function writeSecure(path: string, data: string) {
  await ensureDir();
  await writeFile(path, data, "utf8");
  if (platform() !== "win32") await chmod(path, 0o600);
}

async function readJson<T>(path: string): Promise<T | null> {
  if (!existsSync(path)) return null;
  try {
    return JSON.parse(await readFile(path, "utf8")) as T;
  } catch {
    return null;
  }
}

function normalizeHost(raw: string): string {
  let h = raw.trim().replace(/\/+$/, "");
  if (!/^https?:\/\//i.test(h)) h = "https://" + h;
  return h;
}

/* ───────────────────────── Banner & Status ───────────────────────── */

function banner() {
  console.clear();
  const art = [
    "  ╔╗╔╔═╗═╦╗╔═╗  ╔═╗╦═╗╦╔╗╔╔╦╗╔═╗╦═╗",
    "  ║║║║ ║ ║║╠═   ╠═╝╠╦╝║║║║ ║ ║╣ ╠╦╝",
    "  ╝╚╝╚═╝═╩╝╚═╝  ╩  ╩╚═╩╝╚╝ ╩ ╚═╝╩╚═",
  ].join("\n");
  console.log(pc.cyan(art));
  console.log(pc.dim("  Setup node printer • v0.1.0\n"));
}

type StatusSnapshot = {
  paired: boolean;
  running: boolean;
  live: boolean;
  state?: string;
  at?: string;
  host?: string;
  printers: PrinterConf[];
};

async function collectStatus(): Promise<StatusSnapshot> {
  const server = await readJson<{ host: string }>(SERVER_CONF);
  const paired = !!(server && existsSync(TOKEN_FILE));
  const printers = await loadPrinterConf();

  const running = paired ? await isDaemonRunning() : false;
  const st = running ? await readJson<{ state: string; at?: string }>(STATUS_FILE) : null;

  return {
    paired,
    running,
    live: st?.state === "connected",
    state: st?.state,
    at: st?.at,
    host: server?.host,
    printers,
  };
}

/** Lama waktu sejak status terakhir ditulis daemon, mis. "12 dtk". */
function elapsedText(at?: string): string {
  if (!at) return "";
  const ms = Date.now() - new Date(at).getTime();
  if (!Number.isFinite(ms) || ms < 0) return "";
  const s = Math.round(ms / 1000);
  return s < 60 ? `${s} dtk` : `${Math.floor(s / 60)} mnt`;
}

/** Isi kotak status (2 baris). `blink` dipakai untuk menandakan layar masih hidup. */
function statusContent(s: StatusSnapshot, blink: boolean): string[] {
  const dot = (ok: boolean) => (ok ? pc.green("●") : pc.red("●"));

  // titik berkedip selama daemon masih mencoba menyambung
  const waitingDot = () => (blink ? pc.red("●") : pc.dim("○"));
  const elapsed = elapsedText(s.at);

  const serverText = !s.paired
    ? pc.red("belum dipairing")
    : !s.running
      ? pc.yellow(`${s.host} • daemon mati`)
      : s.live
        ? pc.green(`${s.host} • SSE terhubung`)
        : pc.yellow(
            `${s.host} • ${s.state === "connecting" ? "menyambung..." : "terputus, mencoba ulang"}` +
              (elapsed ? pc.dim(` ${elapsed}`) : "")
          );

  return [
    `${s.paired && !s.live ? waitingDot() : dot(s.live)} Server   : ${serverText}`,
    `${dot(!!s.printers.length)} Printer  : ${
      s.printers.length
        ? pc.green(`${s.printers.length} dipilih`) + pc.dim(` (utama: ${s.printers[0]!.name})`)
        : pc.red("belum dikonfigurasi")
    }`,
  ];
}

/* ── Render kotak status (gaya sama dengan note() clack) ── */

const ANSI_RE = /\x1b\[[0-9;]*m/g;
const visibleLen = (s: string) => s.replace(ANSI_RE, "").length;

/** Potong baris supaya tidak melewati lebar terminal (mencegah baris pecah). */
function fitLine(line: string, max: number): string {
  if (visibleLen(line) <= max) return line;
  let out = "";
  let vis = 0;
  let i = 0;
  while (i < line.length && vis < max) {
    if (line[i] === "\x1b") {
      const m = /^\x1b\[[0-9;]*m/.exec(line.slice(i));
      if (m) {
        out += m[0];
        i += m[0].length;
        continue;
      }
    }
    out += line[i];
    vis++;
    i++;
  }
  return out + "\x1b[0m";
}

function buildStatusBox(content: string[]): string[] {
  const title = "Status";
  const inner = Math.max(title.length, ...content.map(visibleLen)) + 2;

  const top =
    pc.green("◇") +
    "  " +
    title +
    " " +
    pc.gray("─".repeat(Math.max(inner - title.length - 1, 1)) + "╮");

  const body = content.map(
    (line) =>
      `${pc.gray("│")}  ${line}${" ".repeat(Math.max(0, inner - visibleLen(line)))}${pc.gray("│")}`
  );

  const bottom = pc.gray("├" + "─".repeat(inner + 2) + "╯");

  return ["", pc.gray("│"), top, ...body, bottom];
}

/* ── Blok layar live: status + menu digambar ulang di tempat ── */

const HIDE_CURSOR = "\x1b[?25l";
const SHOW_CURSOR = "\x1b[?25h";

/** Lebar terminal (dikurangi 1 supaya tidak menyentuh tepi & membungkus baris). */
function termCols() {
  return Math.max(20, (process.stdout.columns || 80) - 1);
}

/**
 * Tampilkan menu interaktif yang bisa diperbarui tanpa menutup menu:
 * status di atas menu digambar ulang tiap detik, jadi "menyambung..."
 * otomatis berubah menjadi "terhubung" di layar yang sama.
 */
async function liveMenu(opts: {
  message: string;
  options: { value: string; label: string; hint?: string }[];
  header: () => string[];
  refresh: () => Promise<void>;
}): Promise<string | symbol> {
  const { options } = opts;
  const stdin = process.stdin;
  let cursor = 0;
  let drawnLines = 0;
  let busy = false;
  let finished = false;

  const build = (): string[] => {
    const lines = [...opts.header(), pc.cyan("│"), `${pc.cyan("◆")}  ${opts.message}`];

    for (const [i, o] of options.entries()) {
      const active = i === cursor;
      const body = active
        ? `${pc.green("●")} ${o.label}${o.hint ? " " + pc.dim(`(${o.hint})`) : ""}`
        : `${pc.dim("○")} ${pc.dim(o.label)}`;
      lines.push(`${pc.cyan("│")}  ${body}`);
    }

    lines.push(pc.cyan("└"));
    return lines;
  };

  /** Gambar ulang blok (tanpa menambah baris baru di layar). */
  let lastFrame = "";
  const draw = () => {
    if (finished) return;
    const out = build().map((l) => fitLine(l, termCols()));
    const key = out.join("\n");
    if (drawnLines > 0 && key === lastFrame) return; // tidak ada perubahan
    lastFrame = key;

    let frame = "";
    if (drawnLines > 0) frame += `\r\x1b[${drawnLines - 1}A\x1b[0J`;
    frame += key;
    process.stdout.write(frame);
    drawnLines = out.length;
  };

  const cleanup = () => {
    finished = true;
    clearInterval(timer);
    stdin.removeListener("keypress", onKey);
    if (stdin.isTTY) stdin.setRawMode(false);
    stdin.pause();
    process.stdout.write(SHOW_CURSOR);
  };

  /** Akhiri menu: hapus blok menu, sisakan status + pilihan terakhir. */
  const finish = (value: string | symbol) => {
    if (finished) return;
    cleanup();

    const idx = options.findIndex((o) => o.value === value);
    const label = idx >= 0 ? options[idx]!.label : options[cursor]!.label;
    const head = [...opts.header(), pc.gray("│"), `${pc.gray("■")}  ${opts.message}`];
    const tail =
      idx >= 0
        ? `${pc.gray("│")}  ${pc.dim(label)}`
        : `${pc.gray("│")}  ${pc.strikethrough(pc.dim(label))}\n${pc.gray("└")}`;

    const out = [...head, tail].map((l) => fitLine(l, termCols()));
    process.stdout.write(
      `\r\x1b[${Math.max(drawnLines - 1, 0)}A\x1b[0J` + out.join("\n") + "\n"
    );
    drawnLines = 0;

    resolve(value);
  };

  let resolve!: (v: string | symbol) => void;
  const result = new Promise<string | symbol>((r) => (resolve = r));

  const onKey = (_str: string, key: readline.Key | undefined) => {
    if (!key || finished) return;

    if ((key.ctrl && key.name === "c") || key.name === "escape") return void finish(CANCEL);
    if (key.name === "return" || key.name === "enter") return void finish(options[cursor]!.value);
    if (key.name === "up" || key.name === "k") cursor = (cursor - 1 + options.length) % options.length;
    else if (key.name === "down" || key.name === "j") cursor = (cursor + 1) % options.length;
    else if (key.name === "home") cursor = 0;
    else if (key.name === "end") cursor = options.length - 1;
    else if (/^[0-9]$/.test(_str)) {
      const idx = Number(_str) - 1;
      if (idx >= 0 && idx < options.length) return void finish(options[idx]!.value);
      return;
    } else return;

    draw();
  };

  // Polling status: setiap detik status dibaca ulang lalu blok digambar ulang.
  const timer = setInterval(() => {
    if (finished || busy) return;
    busy = true;
    void opts
      .refresh()
      .catch(() => {})
      .finally(() => {
        busy = false;
        draw();
      });
  }, 1000);
  timer.unref?.();

  readline.emitKeypressEvents(stdin);
  if (stdin.isTTY) stdin.setRawMode(true);
  stdin.resume();
  stdin.on("keypress", onKey);
  process.stdout.write(HIDE_CURSOR);
  draw();

  return result;
}

const CANCEL = Symbol("cancel");

/** Menu utama dengan status yang ikut ter-update otomatis. */
async function mainMenu(): Promise<string | symbol> {
  // Layar non-interaktif (mis. output dipipe): pakai menu clack biasa.
  if (!process.stdin.isTTY) {
    note(statusContent(await collectStatus(), true).join("\n"), "Status");
    return select({
      message: "Mau ngapain?",
      options: [
        { value: "server", label: "🔗  Konek ke server", hint: "pairing pakai token dari admin panel" },
        { value: "printer", label: "🖨️   Printer", hint: "lihat status & atur prioritas" },
        { value: "qr", label: "🔳  Scan QR", hint: "buka window QR (Tauri)" },
        { value: "stop", label: "⏹️   Hentikan daemon", hint: "matikan proses background tanpa hapus config" },
        { value: "uninstall", label: "🗑️   Uninstall", hint: "hapus daemon, config & semua file" },
        { value: "exit", label: "🚪  Keluar" },
      ],
    });
  }

  let snap = await collectStatus();
  let blink = false;

  return liveMenu({
    message: "Mau ngapain?",
    header: () => buildStatusBox(statusContent(snap, blink)),
    refresh: async () => {
      blink = !blink;
      snap = await collectStatus();
    },
    options: [
      { value: "server", label: "🔗  Konek ke server", hint: "pairing pakai token dari admin panel" },
      { value: "printer", label: "🖨️   Printer", hint: "lihat status & atur prioritas" },
      { value: "qr", label: "🔳  Scan QR", hint: "buka window QR (Tauri)" },
      { value: "stop", label: "⏹️   Hentikan daemon", hint: "matikan proses background tanpa hapus config" },
      { value: "uninstall", label: "🗑️   Uninstall", hint: "hapus daemon, config & semua file" },
      { value: "exit", label: "🚪  Keluar" },
    ],
  });
}

/* ───────────────────────── 1. Konek ke server ───────────────────────── */

/**
 * Validasi token lewat SSE: buka koneksi, tunggu event pertama dari server
 * (server harus langsung kirim 1 event saat connect, mis. "connected"), lalu tutup.
 */
async function verifyToken(host: string, token: string): Promise<ApiResponse> {
  const ctrl = new AbortController();
  let got = false;
  const timer = setTimeout(() => ctrl.abort(), 10_000);

  try {
    const res = await openSSE(host, token, ctrl.signal);

    for await (const ev of readSSE(res)) {
      got = true;
      let parsed: any = null;
      try { parsed = JSON.parse(ev.data); } catch {}
      if (parsed?.iseror) throw new Error(parsed.message || "Server mengembalikan error");

      return {
        message: parsed?.message ?? (ev.data.slice(0, 100) || ev.event),
        iseror: false,
        data: { status: parsed?.data?.status ?? parsed?.status ?? ev.event },
        responsesCode: 200,
      };
    }
    throw new Error("Koneksi SSE ditutup server sebelum ada event");
  } catch (e) {
    if (!got && ctrl.signal.aborted) {
      throw new Error("Timeout 10 detik: server tidak mengirim event pertama lewat SSE");
    }
    throw e;
  } finally {
    clearTimeout(timer);
    ctrl.abort();
  }
}

async function connectServer() {
  note(
    "Alamat server harus halaman root saja.\n\n" +
      pc.red("✗ https://youtube.com/channel   → bukan root\n") +
      pc.green("✓ https://youtube.com           → benar"),
    "Aturan Penulisan Alamat Server"
  );

  while (true) {
    const host = await text({
      message: "Masukkan alamat server",
      placeholder: "https://serverKamu.com",
      validate: (v) => {
        if (!v?.trim()) return "Alamat tidak boleh kosong";
        try {
          const u = new URL(normalizeHost(v));
          if (u.pathname !== "/" && u.pathname !== "")
            return "Harus halaman root (tanpa path)";
        } catch {
          return "Format URL tidak valid";
        }
      },
    });
    if (cancelled(host)) return;

    const token = await text({
      message: "Masukkan token dari admin panel",
      placeholder: "Token: ************",
      validate: (v) => (v?.trim() ? undefined : "Token tidak boleh kosong"),
    });
    if (cancelled(token)) return;

    const hostUrl = normalizeHost(host as string);
    const s = spinner();
    s.start(`Memvalidasi ke ${pc.cyan(hostUrl)}`);

    try {
      const data = await verifyToken(hostUrl, (token as string).trim());
      s.stop(pc.green("✓ Token valid"));
      log.info(`Status  : ${pc.bold(data.data?.status ?? "-")}`);
      log.info(`Pesan   : ${data.message}`);

      const s2 = spinner();
      s2.start("Mengenkripsi & menyimpan konfigurasi");
      await writeSecure(SERVER_CONF, JSON.stringify({ host: hostUrl, pairedAt: new Date().toISOString() }, null, 2));
      await writeSecure(TOKEN_FILE, encrypt((token as string).trim()));
      await sleep(400);
      s2.stop(pc.green("✓ Tersimpan"));
      await writeLog(`Pairing berhasil -> ${hostUrl}`);
      log.success(`Config : ${pc.dim(CONF_DIR)}`);
      await sleep(800);
      return;
    } catch (err) {
      s.stop(pc.red("✗ Gagal"));
      const msg = err instanceof Error ? err.message : String(err);
      // "grep" error → tampilkan ringkas
      log.error(pc.red(msg.split("\n")[0]!));

      const retry = await confirm({
        message: "Ulangi input?",
        initialValue: true,
      });
      if (cancelled(retry) || !retry) return;
    }
  }
}

/* ───────────────────────── 2. Printer ───────────────────────── */

/* ── Config printer: urutan array = urutan prioritas (priority 1 = dipakai pertama) ── */

async function loadPrinterConf(): Promise<PrinterConf[]> {
  const raw = await readJson<{ printers?: PrinterConf[] }>(PRINTER_CONF);
  if (!raw || Array.isArray(raw)) return []; // format lama diabaikan
  return [...(raw.printers ?? [])].sort((a, b) => a.priority - b.priority);
}

async function savePrinterConf(names: string[]) {
  await writeLog(`Printer disimpan (urutan prioritas): ${names.join(" > ")}`);
  const printers: PrinterConf[] = names.map((name, i) => ({ name, priority: i + 1 }));
  await writeSecure(
    PRINTER_CONF,
    JSON.stringify({ updatedAt: new Date().toISOString(), printers }, null, 2)
  );
}

function printerLabel(name: string, found: Printer | undefined, w: number) {
  const st = !found ? "[TIDAK TERDETEKSI]" : found.online ? "[HIDUP]" : "[MATI]";
  return `${name.padEnd(w)}  ${st}`;
}

/* ── Menu: Select printer (printer mana saja yang boleh dipakai) ── */

async function selectPrinters(printers: Printer[], conf: PrinterConf[]) {
  const w = Math.max(...printers.map((p) => p.name.length));
  const picked = await multiselect({
    message: `Select printer yang boleh dipakai ${pc.dim("(spasi = pilih, enter = lanjut)")}`,
    options: printers.map((p) => ({
      value: p.name,
      label: printerLabel(p.name, p, w),
      hint: p.detail,
    })),
    initialValues: conf.map((p) => p.name).filter((n) => printers.some((p) => p.name === n)),
    required: true,
  });
  if (cancelled(picked)) return;

  const sel = picked as string[];
  // pertahankan urutan prioritas lama, printer baru ditaruh di belakang
  const kept = conf.map((p) => p.name).filter((n) => sel.includes(n));
  const added = sel.filter((n) => !kept.includes(n));
  const names = [...kept, ...added];

  const off = printers.filter((p) => names.includes(p.name) && !p.online);
  if (off.length) log.warn(`Printer terpilih yang sedang mati: ${off.map((p) => p.name).join(", ")}`);

  await savePrinterConf(names);
  log.success(`${names.length} printer dipilih. Atur urutannya di menu "Pilih printer priority".`);
  await sleep(1000);
}

/* ── Menu: Pilih printer priority (urutan pemakaian) ── */

async function setPriority(printers: Printer[], conf: PrinterConf[]) {
  if (!conf.length) {
    log.warn('Belum ada printer yang dipilih. Pakai menu "Select printer" dulu.');
    await sleep(1500);
    return;
  }
  if (conf.length === 1) {
    log.info(`Baru 1 printer dipilih (${conf[0]!.name}), otomatis jadi prioritas utama.`);
    await sleep(1500);
    return;
  }

  const w = Math.max(...conf.map((p) => p.name.length));
  const remaining = conf.map((p) => p.name);
  const order: string[] = [];

  while (remaining.length > 1) {
    const n = order.length + 1;
    const pick = await select({
      message:
        n === 1
          ? `Printer mana yang dipakai ${pc.yellow("PERTAMA")}?`
          : `Prioritas ke-${pc.yellow(String(n))}`,
      options: [
        ...remaining.map((name) => ({
          value: name,
          label: printerLabel(name, printers.find((p) => p.name === name), w),
        })),
        ...(n > 1
          ? [{ value: "__done__", label: pc.dim("✓ Selesai, sisanya ikut urutan sekarang") }]
          : []),
      ],
    });
    if (cancelled(pick)) return;
    if (pick === "__done__") break;

    const name = pick as string;
    if (printers.find((p) => p.name === name)?.online === false) {
      log.warn(`${name} sedang mati.`);
    }
    order.push(name);
    remaining.splice(remaining.indexOf(name), 1);
  }

  const final = [...order, ...remaining];
  note(
    final
      .map((name, i) => `${pc.cyan(`${i + 1}.`)} ${name}${i === 0 ? pc.yellow("  ← dipakai pertama") : ""}`)
      .join("\n"),
    "Urutan Prioritas Baru"
  );

  const ok = await confirm({ message: "Simpan urutan ini?", initialValue: true });
  if (cancelled(ok) || !ok) return;

  await savePrinterConf(final);
  log.success(`Tersimpan di ${pc.dim(PRINTER_CONF)}`);
  await sleep(1000);
}

/* ── Menu Printer (tab) ── */

async function managePrinters() {
  const s = spinner();
  s.start("Mencari printer yang tersedia");
  let printers: Printer[] = [];
  try {
    printers = await listPrinters();
  } catch (err) {
    s.stop(pc.red("✗ Gagal membaca printer"));
    log.error(err instanceof Error ? err.message : String(err));
    await sleep(1200);
    return;
  }
  s.stop(`Ditemukan ${pc.bold(String(printers.length))} printer`);

  if (!printers.length) {
    log.warn("Tidak ada printer terdeteksi (cek CUPS / driver printer).");
    await sleep(1500);
    return;
  }

  const w = Math.max(...printers.map((p) => p.name.length));
  note(
    printers
      .map(
        (p) =>
          `${p.online ? pc.green("●") : pc.red("●")} ${pc.bold(p.name.padEnd(w))}  ` +
          (p.online ? pc.green("HIDUP") : pc.red("MATI ")) +
          pc.dim(`  (${p.detail})`)
      )
      .join("\n"),
    "Status Printer"
  );

  while (true) {
    const conf = await loadPrinterConf();
    note(
      conf.length
        ? conf
            .map((p, i) => `${pc.cyan(`${i + 1}.`)} ${p.name}${i === 0 ? pc.yellow("  ← dipakai pertama") : ""}`)
            .join("\n")
        : pc.dim("Belum ada printer yang dipilih"),
      "Urutan Prioritas Saat Ini"
    );

    const act = await select({
      message: "Menu printer",
      options: [
        { value: "select", label: "📋  Select printer", hint: "pilih printer yang boleh dipakai" },
        { value: "priority", label: "⭐  Pilih printer priority", hint: "atur printer mana yang dipakai duluan" },
        { value: "back", label: "←   Kembali" },
      ],
    });
    if (isCancel(act) || act === "back") return;

    if (act === "select") await selectPrinters(printers, conf);
    if (act === "priority") await setPriority(printers, conf);
  }
}

/* ───────────────────────── Scan QR (Tauri) ───────────────────────── */

async function qrFlow() {
  while (true) {
    const s = spinner();
    s.start("Mengambil QR dari server");
    try {
      const job = await prepareQr(); // request ke server dulu, baru Tauri dijalankan
      s.stop(pc.green("✓ QR diterima"));
      log.info("Window QR terbuka. Tutup window untuk kembali ke menu.");
      await showQr(job);
      return;
    } catch (err) {
      s.stop(pc.red("✗ Gagal"));
      log.error(pc.red((err instanceof Error ? err.message : String(err)).split("\n")[0]!));
      const retry = await confirm({ message: "Ulangi?", initialValue: true });
      if (cancelled(retry) || !retry) return;
    }
  }
}

/* ───────────────────────── Uninstall ───────────────────────── */

export async function uninstallFlow() {
  note(
    "Yang akan dihapus:\n" +
      pc.red("  • ") + "Daemon background & ikon system tray (dihentikan)\n" +
      pc.red("  • ") + "Auto-start\n" +
      pc.red("  • ") + "Semua config, token & log: " + pc.dim(CONF_DIR) +
      (IS_COMPILED ? "\n" + pc.red("  • ") + "File aplikasi (opsional, ditanya berikutnya)" : ""),
    pc.red("Uninstall")
  );

  const ok = await confirm({
    message: pc.red("Yakin hapus semuanya? Tidak bisa dibatalkan."),
    initialValue: false,
  });
  if (cancelled(ok) || !ok) return;

  let removeExe = false;
  if (IS_COMPILED) {
    const r = await confirm({
      message: `Hapus juga file aplikasi ini? ${pc.dim(process.execPath)}`,
      initialValue: true,
    });
    if (cancelled(r)) return;
    removeExe = r as boolean;
  }

  const s = spinner();
  s.start("Menghapus...");
  const steps = await uninstallAll({ removeExe });
  const allOk = steps.every((x) => x.ok);
  s.stop(allOk ? pc.green("✓ Selesai") : pc.yellow("Selesai dengan catatan"));

  for (const x of steps) {
    const line = `${x.step}${x.note ? pc.dim(` (${x.note})`) : ""}`;
    x.ok ? log.success(line) : log.error(pc.red(line));
  }

  outro(allOk ? "Node Printer sudah di-uninstall. Sampai jumpa 👋" : "Beberapa langkah gagal, cek pesan di atas.");
  await sleep(1500);
  process.exit(allOk ? 0 : 1);
}

/* ───────────────────────── Exit notice ───────────────────────── */

async function closeWithNotice(seconds = 10): Promise<never> {
  // Kalau daemon sudah dimatikan (mis. lewat menu Hentikan daemon), tidak perlu
  // menampilkan instruksi membukanya lagi.
  if (!(await isDaemonRunning())) {
    outro("Sampai jumpa 👋");
    process.exit(0);
  }

  const caraBuka =
    platform() === "win32"
      ? pc.cyan("  1. ") + "Klik ikon panah " + pc.bold("^") + " di taskbar (system tray)\n" +
        pc.cyan("  2. ") + "Klik kanan / dobel klik ikon " + pc.bold("Node Printer") + "\n" +
        pc.cyan("  3. ") + "Pilih " + pc.bold("Buka Setup")
      : pc.cyan("  1. ") + "Jalankan aplikasi ini lagi untuk membuka setup\n" +
        pc.cyan("  2. ") + "Untuk menghentikan daemon: " + pc.bold("node-printer stop");

  note(
    "Node Printer " + pc.green("tetap berjalan di background") + ".\n\n" +
      "Untuk membukanya lagi:\n" +
      caraBuka,
    "Info"
  );

  // tombol apa saja = tutup sekarang
  let skip = false;
  if (process.stdin.isTTY) {
    process.stdin.setRawMode(true);
    process.stdin.resume();
    process.stdin.once("data", () => { skip = true; });
  }

  const s = spinner();
  s.start("");
  const end = Date.now() + seconds * 1000;
  while (!skip && Date.now() < end) {
    const left = Math.ceil((end - Date.now()) / 1000);
    s.message(`Jendela menutup otomatis dalam ${pc.bold(String(left))} detik ${pc.dim("(tekan tombol apa saja untuk tutup sekarang)")}`);
    await sleep(100);
  }
  s.stop("Sampai jumpa 👋");
  process.exit(0);
}

/* ───────────────────────── Main loop ───────────────────────── */

export async function main() {
  while (true) {
    banner();
    intro(pc.bgCyan(pc.black(" Node Printer Setup ")));

    const choice = await mainMenu();

    if (isCancel(choice) || choice === "exit") {
      await closeWithNotice(10);
    }

    if (choice === "server") await connectServer();
    if (choice === "printer") await managePrinters();
    if (choice === "qr") await qrFlow();

    if (choice === "stop") {
      if (!(await isDaemonRunning())) {
        log.info("Daemon tidak sedang berjalan.");
      } else {
        const ok = await stopDaemon();
        ok ? log.success("Daemon dihentikan.") : log.error("Gagal menghentikan daemon.");
      }

      await sleep(1200);
      continue;
    }

    if (choice === "uninstall") await uninstallFlow();
  }
}