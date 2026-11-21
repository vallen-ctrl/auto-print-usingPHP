// printers.ts — daftar printer + status hidup/mati (dipakai setup.ts dan printpdf.ts)
//
// Windows : membaca Win32_Printer lewat PowerShell.
// Linux   : membaca antrean CUPS lewat lpstat (paket cups-client).
//
// Nama printer yang disimpan di printers.conf adalah nama persis yang
// dikembalikan sistem, jadi pilih dari daftar di menu Printer.

export type Printer = { name: string; online: boolean; detail: string };

/** PrinterStatus 7 = "Offline" (printer dimatikan / kabel putus tapi masih terdaftar di Windows). */
const PRINTER_STATUS_OFFLINE = 7;

/** Cari printer di daftar berdasarkan nama (tidak peka huruf besar/kecil). */
export function findPrinterByName(list: Printer[], name: string): Printer | undefined {
  const want = name.trim().toLowerCase();
  return list.find((p) => p.name.trim().toLowerCase() === want);
}

export async function listPrinters(): Promise<Printer[]> {
  if (process.platform === "win32") return listPrintersWindows();
  if (process.platform === "linux") return listPrintersLinux();

  throw new Error(`daftar printer belum didukung di sistem "${process.platform}"`);
}

/* ───────────── Windows (PowerShell) ───────────── */

async function listPrintersWindows(): Promise<Printer[]> {
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

/* ───────────── Linux (CUPS / lpstat) ───────────── */

/**
 * Contoh keluaran `lpstat -p`:
 *   printer EPSON_L3210 is idle.  enabled since ...
 *   printer Office_Jet now printing Office_Jet-42.  enabled since ...
 *   printer Lama disabled since ... -
 *
 * CUPS tidak membedakan "mati listrik" dari "idle"; status disabled dipakai
 * sebagai penanda printer tidak siap.
 */
async function listPrintersLinux(): Promise<Printer[]> {
  let r;
  try {
    r = await Bun.$`lpstat -p`.nothrow().quiet();
  } catch {
    throw new Error(
      "perintah lpstat tidak ditemukan; pastikan CUPS dan paket cups-client terpasang"
    );
  }

  const out = await r.text();

  if (r.exitCode !== 0) {
    throw new Error(
      `tidak bisa membaca daftar printer dari CUPS (lpstat exit ${r.exitCode}); ` +
        "pastikan CUPS dan paket cups-client terpasang"
    );
  }

  const printers: Printer[] = [];

  for (const baris of out.split(/\r?\n/)) {
    const cocok = /^printer\s+(\S+)\s+(.*)$/i.exec(baris.trim());
    if (!cocok) continue;

    const nama = cocok[1]!;
    const info = cocok[2]!.toLowerCase();
    const disabled = info.includes("disabled");
    const printing = info.includes("now printing") || info.includes("is printing");

    printers.push({
      name: nama,
      online: !disabled,
      detail: disabled ? "disabled / tidak siap" : printing ? "sedang mencetak" : "siap",
    });
  }

  return printers;
}
