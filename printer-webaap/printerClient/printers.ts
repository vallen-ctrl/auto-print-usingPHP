// printers.ts — daftar printer Windows + status hidup/mati (dipakai setup.ts dan printpdf.ts)
// Khusus Windows (membaca Win32_Printer lewat PowerShell).

export type Printer = { name: string; online: boolean; detail: string };

export async function listPrinters(): Promise<Printer[]> {
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
