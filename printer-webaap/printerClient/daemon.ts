// daemon.ts — background process + system tray (Windows)
import net from "node:net";
import { spawn, spawnSync, type ChildProcess } from "node:child_process";
import { mkdir, writeFile, readFile, rm } from "node:fs/promises";
import { watch, existsSync } from "node:fs";
import { homedir } from "node:os";
import { join } from "node:path";
import { createDecipheriv, scryptSync } from "node:crypto";
import { log as writeLog, loggedFetch, disableLogging, LOG_FILE } from "./logger";

export const CONF_DIR = join(homedir(), ".node-printer");
const SERVER_CONF = join(CONF_DIR, "server.conf");
const TOKEN_FILE = join(CONF_DIR, "token.enc");
const PRINTER_CONF = join(CONF_DIR, "printers.conf");
const TRAY_SCRIPT = join(CONF_DIR, "tray.ps1");

// Samakan dengan ENC_SECRET di setup.ts
const ENC_SECRET = process.env.NODE_PRINTER_SECRET ?? "CHANGE_ME_LATER";

// Port lokal dipakai sebagai "lock" single-instance
const LOCK_PORT = 47831;

/* ───────────── Self command (dev vs compiled) ───────────── */

const COMPILED = Bun.main.includes("~BUN") || Bun.main.includes("$bunfs");
export const IS_COMPILED = COMPILED;

function selfCmd(args: string[]): string[] {
  return COMPILED
    ? [process.execPath, ...args]
    : [process.execPath, Bun.main, ...args];
}

const q = (s: string) => `"${s}"`;

/* ───────────── Daemon control (dipanggil dari launcher) ───────────── */

export function isDaemonRunning(): Promise<boolean> {
  return new Promise((resolve) => {
    const sock = net.connect({ host: "127.0.0.1", port: LOCK_PORT });
    sock.setTimeout(800);
    sock.once("connect", () => { sock.destroy(); resolve(true); });
    sock.once("timeout", () => { sock.destroy(); resolve(false); });
    sock.once("error", () => resolve(false));
  });
}

export function startDaemonDetached() {
  const [cmd, ...args] = selfCmd(["--daemon"]);
  spawn(cmd!, args, {
    detached: true,
    windowsHide: true,
    stdio: "ignore",
  }).unref();
}

/** Buka jendela console baru berisi TUI setup */
export function openSetupWindow() {
  const full = selfCmd(["--setup"]).map(q).join(" ");
  spawn("cmd.exe", ["/c", `start "Node Printer Setup" ${full}`], {
    detached: true,
    windowsHide: false,
    windowsVerbatimArguments: true,
    stdio: "ignore",
  }).unref();
}

/* ───────────── Stop daemon, autostart, uninstall ───────────── */

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));
const RUN_KEY = String.raw`HKCU\Software\Microsoft\Windows\CurrentVersion\Run`;

export async function stopDaemon(): Promise<boolean> {
  if (!(await isDaemonRunning())) return false;

  // cara halus: kirim perintah "exit" ke daemon
  await new Promise<void>((res) => {
    const s = net.connect({ host: "127.0.0.1", port: LOCK_PORT }, () => s.end("exit\n"));
    s.on("close", () => res());
    s.on("error", () => res());
    setTimeout(res, 1500);
  });
  for (let i = 0; i < 12; i++) {
    if (!(await isDaemonRunning())) return true;
    await sleep(250);
  }

  // fallback (mis. daemon versi lama): paksa matikan lewat PID yang listen di port lock
  const out = spawnSync(
    "cmd.exe",
    ["/c", `netstat -ano | findstr LISTENING | findstr :${LOCK_PORT}`],
    { encoding: "utf8", windowsHide: true }
  ).stdout;
  const pid = out.trim().split(/\s+/).pop();
  if (pid && /^\d+$/.test(pid)) {
    spawnSync("taskkill", ["/PID", pid, "/T", "/F"], { windowsHide: true });
    await sleep(500);
  }
  return !(await isDaemonRunning());
}

export function setAutostart(enable: boolean): boolean {
  const r = enable
    ? spawnSync("reg", ["add", RUN_KEY, "/v", "NodePrinter", "/t", "REG_SZ", "/d", `"${process.execPath}" --daemon`, "/f"], { windowsHide: true })
    : spawnSync("reg", ["delete", RUN_KEY, "/v", "NodePrinter", "/f"], { windowsHide: true });
  return r.status === 0;
}

export type UninstallStep = { step: string; ok: boolean; note?: string };

export async function uninstallAll(opts: { removeExe: boolean }): Promise<UninstallStep[]> {
  const res: UninstallStep[] = [];

  // 1. hentikan daemon + tray
  const wasRunning = await isDaemonRunning();
  const stopped = await stopDaemon();
  res.push({
    step: "Hentikan daemon & ikon tray",
    ok: !(await isDaemonRunning()),
    note: wasRunning ? (stopped ? undefined : "gagal dihentikan") : "tidak sedang berjalan",
  });

  // 2. hapus auto-start
  const hadAuto = setAutostart(false);
  res.push({ step: "Hapus auto-start Windows", ok: true, note: hadAuto ? undefined : "tidak aktif" });

  // 3. hapus folder config (token, server, printer, log, tray script)
  let ok = false, err = "";
  for (let i = 0; i < 6 && !ok; i++) {
    try {
      await rm(CONF_DIR, { recursive: true, force: true });
      ok = !existsSync(CONF_DIR);
    } catch (e) {
      err = e instanceof Error ? e.message : String(e);
      await sleep(400);
    }
  }
  res.push({ step: `Hapus config (${CONF_DIR})`, ok, note: ok ? undefined : err });

  // 3b. hapus file log (ada di folder aplikasi, bukan di folder config)
  disableLogging();
  try {
    await rm(LOG_FILE, { force: true });
    res.push({ step: `Hapus log (${LOG_FILE})`, ok: true });
  } catch (e) {
    res.push({ step: `Hapus log (${LOG_FILE})`, ok: false, note: e instanceof Error ? e.message : String(e) });
  }

  // 4. hapus file .exe (tidak bisa saat masih jalan -> dijadwalkan setelah proses ini selesai)
  if (opts.removeExe && COMPILED) {
    spawn("cmd.exe", ["/c", `ping 127.0.0.1 -n 4 >nul & del /f /q "${process.execPath}"`], {
      detached: true,
      windowsHide: true,
      windowsVerbatimArguments: true,
      stdio: "ignore",
    }).unref();
    res.push({ step: "Hapus file aplikasi (.exe)", ok: true, note: "dihapus beberapa detik setelah ditutup" });
  }

  return res;
}

/* ───────────── Logging ───────────── */

async function log(msg: string) {
  await writeLog(msg); // ditulis ke node-printer.log di folder root (lihat logger.ts)
}

/* ───────────── Config & token ───────────── */

function decrypt(b64: string): string {
  const buf = Buffer.from(b64, "base64");
  const salt = buf.subarray(0, 16);
  const iv = buf.subarray(16, 28);
  const tag = buf.subarray(28, 44);
  const data = buf.subarray(44);
  const key = scryptSync(ENC_SECRET, salt, 32);
  const d = createDecipheriv("aes-256-gcm", key, iv);
  d.setAuthTag(tag);
  return Buffer.concat([d.update(data), d.final()]).toString("utf8");
}

export async function loadConfig() {
  if (!existsSync(SERVER_CONF) || !existsSync(TOKEN_FILE)) return null;
  const server = JSON.parse(await readFile(SERVER_CONF, "utf8")) as { host: string };
  const token = decrypt(await readFile(TOKEN_FILE, "utf8"));
  const printers = existsSync(PRINTER_CONF)
    ? ((JSON.parse(await readFile(PRINTER_CONF, "utf8")) as { printers?: { name: string; priority: number }[] }).printers ?? [])
        .sort((a, b) => a.priority - b.priority)
    : [];
  return { host: server.host, token, printers };
}

/* ───────────── Worker: koneksi SSE permanen ───────────── */

import { runSSELoop, type SSEEvent, type SSEState, type CommandContext } from "./see";
import * as custom from "./Commands";

const STATUS_FILE = join(CONF_DIR, "status.json");
let sseCtrl: AbortController | null = null;

async function setStatus(state: SSEState | "unpaired" | "error", info?: string) {
  try {
    await writeFile(
      STATUS_FILE,
      JSON.stringify({ state, info: info ?? null, pid: process.pid, at: new Date().toISOString() }),
      "utf8"
    );
  } catch {}
}

async function dispatch(ev: SSEEvent, ctx: CommandContext) {
  let data: unknown = ev.data;
  try { data = JSON.parse(ev.data); } catch {}

  let name = ev.event;
  if (name === "message" && data && typeof data === "object") {
    const d = data as Record<string, unknown>;
    name = String(d.command ?? d.type ?? "message");
  }

  await writeLog(`event "${name}" (${ev.data.length} byte)`, "SSE");

  const handler = custom.commands[name];
  if (!handler) return custom.onUnknown(ev, data, ctx);
  await handler(data, ctx, ev);
}

async function startWorker() {
  // hentikan koneksi lama (kalau config berubah / reload)
  sseCtrl?.abort();
  sseCtrl = null;

  let cfg: Awaited<ReturnType<typeof loadConfig>>;
  try {
    cfg = await loadConfig();
  } catch (e) {
    const msg = e instanceof Error ? e.message : String(e);
    await log("Gagal baca config: " + msg);
    return void (await setStatus("error", msg));
  }
  if (!cfg) {
    await setStatus("unpaired");
    return void (await log("Belum dipairing, worker idle."));
  }

  const ctrl = new AbortController();
  sseCtrl = ctrl;

  const ctx: CommandContext = {
    host: cfg.host,
    request: (path, init = {}) => {
      const headers = new Headers(init.headers);
      headers.set("Authorization", `Bearer ${cfg!.token}`);
      return loggedFetch(cfg!.host + path, { ...init, headers }, "request");
    },
    printers: cfg.printers,
    log,
    post: (path, body = {}) =>
      loggedFetch(cfg!.host + path, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token: cfg!.token, ...body }),
        signal: AbortSignal.timeout(15_000),
      }),
  };

  await log(`Worker aktif -> ${cfg.host} | printer: ${cfg.printers.length}`);

  // event diproses berurutan, tapi tidak memblokir pembacaan stream
  let chain: Promise<unknown> = Promise.resolve();

  void runSSELoop({
    host: cfg.host,
    token: cfg.token,
    signal: ctrl.signal,
    onStatus: (state, info) => {
      void setStatus(state, info);
      void log(`SSE ${state}${info ? ": " + info : ""}`);
      if (state === "connected") {
        chain = chain.then(() => custom.onConnected(ctx)).catch((e) => log("onConnected error: " + e));
      } else if (state === "disconnected") {
        chain = chain.then(() => custom.onDisconnected(ctx, info ?? "")).catch((e) => log("onDisconnected error: " + e));
      }
    },
    onEvent: (ev) => {
      chain = chain
        .then(() => dispatch(ev, ctx))
        .catch((e) => log(`Handler "${ev.event}" error: ${e instanceof Error ? e.message : e}`));
    },
  });
}

/* ───────────── Tray (PowerShell NotifyIcon, tanpa dependency) ───────────── */

const TRAY_PS1 = String.raw`
param([int]$ParentPid)
$ErrorActionPreference = "Stop"
try {
Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

$script:icon = New-Object System.Windows.Forms.NotifyIcon
$script:icon.Icon = [System.Drawing.SystemIcons]::Application
$script:icon.Text = "Node Printer (berjalan)"
$script:icon.Visible = $true

$menu = New-Object System.Windows.Forms.ContextMenuStrip
$title = $menu.Items.Add("Node Printer")
$title.Enabled = $false
[void]$menu.Items.Add("-")
$open = $menu.Items.Add("Buka Setup")
$exit = $menu.Items.Add("Keluar")

$open.add_Click({ [Console]::Out.WriteLine("open") })
$exit.add_Click({
  [Console]::Out.WriteLine("exit")
  $script:icon.Visible = $false
  [System.Windows.Forms.Application]::Exit()
})
$script:icon.add_DoubleClick({ [Console]::Out.WriteLine("open") })
$script:icon.ContextMenuStrip = $menu

# kalau proses induk mati, tray ikut mati
$script:pp = $ParentPid
$timer = New-Object System.Windows.Forms.Timer
$timer.Interval = 3000
$timer.add_Tick({
  if (-not (Get-Process -Id $script:pp -ErrorAction SilentlyContinue)) {
    $script:icon.Visible = $false
    [System.Windows.Forms.Application]::Exit()
  }
})
$timer.Start()

$script:icon.ShowBalloonTip(3000, "Node Printer", "Berjalan di background. Klik kanan ikon ini untuk buka setup.", [System.Windows.Forms.ToolTipIcon]::Info)

[System.Windows.Forms.Application]::Run()
} catch {
  [Console]::Error.WriteLine($_.Exception.Message)
  exit 1
}
`;

async function startTray(onOpen: () => void, onExit: () => void): Promise<ChildProcess> {
  await writeFile(TRAY_SCRIPT, TRAY_PS1, "utf8");
  const ps = spawn(
    "powershell.exe",
    [
      "-NoProfile", "-ExecutionPolicy", "Bypass", "-STA",
      "-WindowStyle", "Hidden",
      "-File", TRAY_SCRIPT,
      "-ParentPid", String(process.pid),
    ],
    { windowsHide: true, stdio: ["ignore", "pipe", "pipe"] }
  );
  ps.stderr!.on("data", (d: Buffer) => void log("tray stderr: " + d.toString().trim()));
  ps.on("error", (e) => void log("tray spawn error: " + e.message));

  let buf = "";
  ps.stdout!.on("data", (d: Buffer) => {
    buf += d.toString();
    let i;
    while ((i = buf.indexOf("\n")) >= 0) {
      const cmd = buf.slice(0, i).trim();
      buf = buf.slice(i + 1);
      if (cmd === "open") onOpen();
      if (cmd === "exit") onExit();
    }
  });
  ps.on("exit", async (code) => {
    await log(`tray process exit, code=${code}`);
    onExit();
  });
  return ps;
}

/* ───────────── Entry daemon ───────────── */

export async function runDaemon() {
  await mkdir(CONF_DIR, { recursive: true });

  // single instance
  const server = net.createServer((s) => {
    s.on("error", () => {});
    s.on("data", async (d) => {
      if (d.toString().trim() === "exit") {
        await log("Stop diminta (uninstall/exit)");
        s.end();
        process.exit(0); // tray ikut mati lewat process.on("exit")
      }
    });
  });
  await new Promise<void>((resolve) => {
    server.once("error", () => process.exit(0)); // sudah ada daemon lain
    server.listen(LOCK_PORT, "127.0.0.1", () => resolve());
  });

  await log("Daemon start");
  await startWorker();

  // auto-reload kalau config diubah dari TUI
  let t: ReturnType<typeof setTimeout> | undefined;
  watch(CONF_DIR, (_e, file) => {
    if (!file || file === "tray.ps1" || file === "status.json" || file === "downloaded.json") return;
    clearTimeout(t);
    t = setTimeout(async () => {
      await log(`Config berubah (${file}), reload`);
      await startWorker();
    }, 500);
  });

  const tray = await startTray(
    () => openSetupWindow(),
    async () => {
      await log("Daemon exit");
      server.close();
      process.exit(0);
    }
  );
  process.on("exit", () => tray.kill());

  // jaga proses tetap hidup
  setInterval(() => {}, 1 << 30);
}