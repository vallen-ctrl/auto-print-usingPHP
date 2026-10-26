// printers.ts — daftar printer Windows + status hidup/mati (dipakai setup.ts dan printpdf.ts)
// Khusus Windows (membaca Win32_Printer lewat PowerShell).

export type Printer = { name: string; online: boolean; detail: string };

/** PrinterStatus 7 = "Offline" (printer dimatikan / kabel putus tapi masih terdaftar di Windows). */
const PRINTER_STATUS_OFFLINE = 7;

/** Cari printer di daftar Windows berdasarkan nama (tidak peka huruf besar/kecil). */
export function findPrinterByName(list: Printer[], name: string): Printer | undefined {
  const want = name.trim().toLowerCase();
  return list.find((p) => p.name.trim().toLowerCase() === want);
}

export async function listPrinters(): Promise<Printer[]> {
  const r = await Bun.$`powershell -NoProfile -Command "Get-CimInstance Win32_Printer | Select-Object Name,WorkOffline,PrinterStatus | ConvertTo-Json -Compress"`
    .nothrow().quiet();
  const out = await r.text();
  // PowerShell gagal: beri tahu pemanggil (jangan disamakan dengan "tidak ada printer")
  if (r.exitCode !== 0) {
    throw new Error(`tidak bisa membaca daftar printer dari Windows (PowerShell exit ${r.exitCode})`);
  }
  if (!out.trim()) return []; // exit 0 tapi kosong = memang tidak ada printer terpasang

  const parsed = JSON.parse(out);
  const arr = Array.isArray(parsed) ? parsed : [parsed];
  return arr.map((p: any) => {
    const workOffline = !!p.WorkOffline;
    const statusOffline = Number(p.PrinterStatus) === PRINTER_STATUS_OFFLINE;
    return {
      name: p.Name,
      online: !workOffline && !statusOffline,
      detail: workOffline ? "offline" : statusOffline ? "offline (PrinterStatus 7)" : "ready",
    };
  });
}
