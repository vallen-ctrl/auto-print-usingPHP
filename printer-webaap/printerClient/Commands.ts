// commands.ts — FILE INI YANG KAMU EDIT untuk custom command dari server (SSE)
//
// Cara routing:
//   1. SSE dengan field `event: nama`       -> commands["nama"]
//   2. SSE tanpa event (message biasa) dan data JSON punya `command` atau `type`
//        data: {"command":"print","file":"x.pdf"}  -> commands["print"]
//   3. Tidak ada yang cocok                 -> onUnknown()
//
// `data` sudah di-parse JSON kalau valid, kalau tidak berupa string mentah.

import type { CommandContext, SSEEvent } from "./see";

type Handler = (data: any, ctx: CommandContext, ev: SSEEvent) => void | Promise<void>;

export const commands: Record<string, Handler> = {
  // event bawaan, boleh dihapus/diubah
  connected: async (_data, ctx) => {
    await ctx.log("Server: connected");
  },
  heartbeat: () => {},

  ping: async (_data, ctx) => {
    await ctx.log("ping diterima");
  },
  notification: async(data)=>{

  },

  print: async (data, ctx) => {
    // TODO: ambil file dari server lalu kirim ke printer
    // ctx.printers sudah urut prioritas (index 0 = dipakai pertama)
    const target = ctx.printers[0]?.name;
    await ctx.log(`print -> ${target ?? "(belum ada printer)"} | data: ${JSON.stringify(data)}`);
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