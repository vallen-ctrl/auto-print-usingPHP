// setup.ts — Node Printer Setup (Bun + @clack/prompts)
import {
  intro, outro, note, log, text, select, multiselect,
  confirm, spinner, isCancel, cancel,
} from "@clack/prompts";
import pc from "picocolors";
import { mkdir, writeFile, readFile, chmod } from "node:fs/promises";
import { existsSync } from "node:fs";
import { homedir, platform } from "node:os";
import { join } from "node:path";
import { createCipheriv, randomBytes, scryptSync } from "node:crypto";
import { uninstallAll, IS_COMPILED, isDaemonRunning } from "./daemon";
import { openSSE, readSSE } from "./see";

/* ───────────────────────── Config & Paths ───────────────────────── */

const CONF_DIR = join(homedir(), ".node-printer");
const SERVER_CONF = join(CONF_DIR, "server.conf");     // host (plain JSON)
const TOKEN_FILE = join(CONF_DIR, "token.enc");        // token (terenkripsi)
const PRINTER_CONF = join(CONF_DIR, "printers.conf");
const STATUS_FILE = join(CONF_DIR, "status.json");  // prioritas printer

// TODO: ganti sumber key enkripsi sesuai keputusanmu nanti
// (env var / machine-id / hardcode di build, dll)
const ENC_SECRET = process.env.NODE_PRINTER_SECRET ?? "CHANGE_ME_LATER";


interface ApiResponse {
  message: string;
  iseror: boolean;
  data: { status: string };
  responsesCode: number;
}

type Printer = { name: string; online: boolean; detail: string };
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
    "  ╔╗╔╔═╗╔╦╗╔═╗  ╔═╗╦═╗╦╔╗╔╔╦╗╔═╗╦═╗",
    "  ║║║║ ║ ║║║╣   ╠═╝╠╦╝║║║║ ║ ║╣ ╠╦╝",
    "  ╝╚╝╚═╝═╩╝╚═╝  ╩  ╩╚═╩╝╚╝ ╩ ╚═╝╩╚═",
  ].join("\n");
  console.log(pc.cyan(art));
  console.log(pc.dim("  Setup node printer • v0.1.0\n"));
}

async function statusLine() {
  const server = await readJson<{ host: string }>(SERVER_CONF);
  const paired = server && existsSync(TOKEN_FILE);
  const printers = await loadPrinterConf();
  const dot = (ok: boolean) => (ok ? pc.green("●") : pc.red("●"));

  const running = paired ? await isDaemonRunning() : false;
  const st = running ? await readJson<{ state: string }>(STATUS_FILE) : null;
  const live = st?.state === "connected";
  const serverText = !paired
    ? pc.red("belum dipairing")
    : !running
      ? pc.yellow(`${server!.host} • daemon mati`)
      : live
        ? pc.green(`${server!.host} • SSE terhubung`)
        : pc.yellow(`${server!.host} • ${st?.state === "connecting" ? "menyambung..." : "terputus, mencoba ulang"}`);

  note(
    `${dot(live)} Server   : ${serverText}\n` +
      `${dot(!!printers?.length)} Printer  : ${
        printers?.length
          ? pc.green(`${printers.length} dipilih`) +
            pc.dim(` (utama: ${printers[0]!.name})`)
          : pc.red("belum dikonfigurasi")
      }`,
    "Status"
  );
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

async function listPrinters(): Promise<Printer[]> {
  const os = platform();

  if (os === "win32") {
    const out = await Bun.$`powershell -NoProfile -Command "Get-CimInstance Win32_Printer | Select-Object Name,WorkOffline,PrinterStatus | ConvertTo-Json -Compress"`
      .nothrow().quiet().text();
    if (!out.trim()) return [];
    const parsed = JSON.parse(out);
    const arr = Array.isArray(parsed) ? parsed : [parsed];
    return arr.map((p: any) => ({
      name: p.Name,
      online: !p.WorkOffline,
      detail: p.WorkOffline ? "offline" : "ready",
    }));
  }

  // Linux / macOS (CUPS)
  const out = await Bun.$`lpstat -p`.nothrow().quiet().text();
  const printers: Printer[] = [];
  for (const line of out.split("\n")) {
    const m = line.match(/^printer\s+(\S+)\s+(.*)$/);
    if (!m) continue;
    const rest = m[2]!;
    const disabled = /disabled/i.test(rest);
    printers.push({
      name: m[1]!,
      online: !disabled,
      detail: disabled ? "disabled" : /idle/i.test(rest) ? "idle" : "printing",
    });
  }
  return printers;
}

/* ── Config printer: urutan array = urutan prioritas (priority 1 = dipakai pertama) ── */

async function loadPrinterConf(): Promise<PrinterConf[]> {
  const raw = await readJson<{ printers?: PrinterConf[] }>(PRINTER_CONF);
  if (!raw || Array.isArray(raw)) return []; // format lama diabaikan
  return [...(raw.printers ?? [])].sort((a, b) => a.priority - b.priority);
}

async function savePrinterConf(names: string[]) {
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

/* ───────────────────────── Uninstall ───────────────────────── */

export async function uninstallFlow() {
  note(
    "Yang akan dihapus:\n" +
      pc.red("  • ") + "Daemon background & ikon system tray (dihentikan)\n" +
      pc.red("  • ") + "Auto-start Windows\n" +
      pc.red("  • ") + "Semua config, token & log: " + pc.dim(CONF_DIR) +
      (IS_COMPILED ? "\n" + pc.red("  • ") + "File aplikasi .exe (opsional, ditanya berikutnya)" : ""),
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
  note(
    "Node Printer " + pc.green("tetap berjalan di background") + ".\n\n" +
      "Untuk membukanya lagi:\n" +
      pc.cyan("  1. ") + "Klik ikon panah " + pc.bold("^") + " di taskbar (system tray)\n" +
      pc.cyan("  2. ") + "Klik kanan / dobel klik ikon " + pc.bold("Node Printer") + "\n" +
      pc.cyan("  3. ") + "Pilih " + pc.bold("Buka Setup"),
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
    await statusLine();

    const choice = await select({
      message: "Mau ngapain?",
      options: [
        { value: "server", label: "🔗  Konek ke server", hint: "pairing pakai token dari admin panel" },
        { value: "printer", label: "🖨️   Printer", hint: "lihat status & atur prioritas" },
        { value: "uninstall", label: "🗑️   Uninstall", hint: "hapus daemon, config & semua file" },
        { value: "exit", label: "🚪  Keluar" },
      ],
    });

    if (isCancel(choice) || choice === "exit") {
      await closeWithNotice(10);
    }

    if (choice === "server") await connectServer();
    if (choice === "printer") await managePrinters();
    if (choice === "uninstall") await uninstallFlow();
  }
}