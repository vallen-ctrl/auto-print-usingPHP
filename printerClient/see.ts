// sse.ts — SSE client: request, parser, auto-reconnect + watchdog
// File ini jarang perlu diubah. Logika command ada di commands.ts

/* ───────────── Konfigurasi ───────────── */

export const SSE_PATH = "/public/api/verifiedComputer.php"; // alamat yang sama dengan sebelumnya
const HEARTBEAT_TIMEOUT = 60_000; // tidak ada data/komentar > 60 dtk = dianggap putus, reconnect
const BACKOFF_MIN = 1_000;
const BACKOFF_MAX = 30_000;

/** Atur cara kirim token ke server di sini (satu-satunya tempat). */
function buildRequest(token: string, lastId?: string): RequestInit {
  const headers: Record<string, string> = {
    Accept: "text/event-stream",
    "Cache-Control": "no-cache",
    "Content-Type": "application/json",
  };
  if (lastId) headers["Last-Event-ID"] = lastId;
  return {
    method: "POST",
    headers,
    body: JSON.stringify({ token }),
  };
}

/* ───────────── Types ───────────── */

export interface SSEEvent {
  event: string; // default "message"
  data: string;
  id?: string;
}

export interface CommandContext {
  host: string;
  /** printer terpilih, sudah urut: index 0 = prioritas 1 */
  printers: { name: string; priority: number }[];
  log: (msg: string) => Promise<void>;
  /** POST JSON ke server (token otomatis ikut di body) */
  post: (path: string, body?: Record<string, unknown>) => Promise<Response>;
}

export type SSEState = "connecting" | "connected" | "disconnected";

/* ───────────── Request ───────────── */

/** Buka koneksi SSE. Throw kalau HTTP error / server membalas bukan SSE. */
export async function openSSE(
  host: string,
  token: string,
  signal: AbortSignal,
  lastId?: string
): Promise<Response> {
  const res = await fetch(host + SSE_PATH, { ...buildRequest(token, lastId), signal });

  if (!res.ok) {
    const t = (await res.text().catch(() => "")).slice(0, 200);
    throw new Error(`HTTP ${res.status}: ${t}`);
  }

  const type = res.headers.get("content-type") ?? "";
  if (!type.includes("text/event-stream")) {
    // server membalas JSON biasa (mis. token salah) -> tampilkan pesannya
    const raw = (await res.text()).slice(0, 300);
    try {
      const j = JSON.parse(raw);
      if (j.iseror) throw new Error(j.message || "Server mengembalikan error");
    } catch (e) {
      if (!(e instanceof SyntaxError)) throw e;
    }
    throw new Error(`Server tidak mengirim SSE (content-type: ${type || "kosong"})`);
  }
  if (!res.body) throw new Error("Response SSE tidak punya body");
  return res;
}

/* ───────────── Parser ───────────── */

function parseBlock(block: string): SSEEvent | null {
  let event = "";
  let id: string | undefined;
  const data: string[] = [];

  for (const line of block.split(/\r?\n/)) {
    if (!line || line.startsWith(":")) continue; // komentar / heartbeat
    const i = line.indexOf(":");
    const field = i < 0 ? line : line.slice(0, i);
    let value = i < 0 ? "" : line.slice(i + 1);
    if (value.startsWith(" ")) value = value.slice(1);

    if (field === "event") event = value;
    else if (field === "data") data.push(value);
    else if (field === "id") id = value;
  }

  if (!data.length && !event) return null;
  return { event: event || "message", data: data.join("\n"), id };
}

/** Baca stream SSE jadi event satu per satu. onActivity dipanggil tiap ada byte masuk. */
export async function* readSSE(
  res: Response,
  onActivity?: () => void
): AsyncGenerator<SSEEvent> {
  const reader = res.body!.getReader();
  const dec = new TextDecoder();
  let buf = "";

  try {
    while (true) {
      const { value, done } = await reader.read();
      if (done) break;
      onActivity?.();
      buf += dec.decode(value, { stream: true });

      while (true) {
        const m = /\r?\n\r?\n/.exec(buf);
        if (!m) break;
        const block = buf.slice(0, m.index);
        buf = buf.slice(m.index + m[0].length);
        const ev = parseBlock(block);
        if (ev) yield ev;
      }
    }
  } finally {
    reader.cancel().catch(() => {});
  }
}

/* ───────────── Loop koneksi permanen ───────────── */

function sleepAbortable(ms: number, signal: AbortSignal): Promise<void> {
  return new Promise((resolve) => {
    if (signal.aborted) return resolve();
    const t = setTimeout(done, ms);
    function done() {
      clearTimeout(t);
      signal.removeEventListener("abort", done);
      resolve();
    }
    signal.addEventListener("abort", done, { once: true });
  });
}

export async function runSSELoop(opts: {
  host: string;
  token: string;
  signal: AbortSignal;
  onEvent: (ev: SSEEvent) => void;
  onStatus: (state: SSEState, info?: string) => void;
}) {
  const { host, token, signal, onEvent, onStatus } = opts;
  let backoff = BACKOFF_MIN;
  let lastId: string | undefined;

  while (!signal.aborted) {
    const ctrl = new AbortController();
    const onAbort = () => ctrl.abort();
    signal.addEventListener("abort", onAbort, { once: true });

    let watchdog: ReturnType<typeof setTimeout> | undefined;
    let timedOut = false;
    const touch = () => {
      clearTimeout(watchdog);
      watchdog = setTimeout(() => {
        timedOut = true;
        ctrl.abort();
      }, HEARTBEAT_TIMEOUT);
    };

    try {
      onStatus("connecting");
      touch();
      const res = await openSSE(host, token, ctrl.signal, lastId);
      onStatus("connected");
      backoff = BACKOFF_MIN;

      for await (const ev of readSSE(res, touch)) {
        if (ev.id) lastId = ev.id;
        onEvent(ev);
      }
      if (!signal.aborted) onStatus("disconnected", "server menutup koneksi");
    } catch (e) {
      if (signal.aborted) break;
      const msg = timedOut
        ? `tidak ada heartbeat ${HEARTBEAT_TIMEOUT / 1000} detik`
        : e instanceof Error ? e.message : String(e);
      onStatus("disconnected", msg);
    } finally {
      clearTimeout(watchdog);
      signal.removeEventListener("abort", onAbort);
      ctrl.abort();
    }

    if (signal.aborted) break;
    await sleepAbortable(backoff, signal);
    backoff = Math.min(backoff * 2, BACKOFF_MAX);
  }
}