# Node Printer Client

Aplikasi kecil untuk menghubungkan printer di komputer toko dengan server
aplikasi. Program berjalan sebagai daemon, menerima perintah lewat SSE
(`print`, `download`, `delete`), lalu mengunduh PDF dan mencetaknya.

Sekarang bisa dijalankan di **Windows** dan **Linux**.

## Prasyarat

- [Bun](https://bun.sh) 1.1 atau lebih baru
- Windows: printer terpasang di Windows, [SumatraPDF portable](https://www.sumatrapdfreader.org/download-free-pdf-viewer)
- Linux: CUPS terpasang dan printer sudah ada di antrean CUPS
  - Debian/Ubuntu: `sudo apt install cups cups-client`
  - Cek: `lpstat -p` harus menampilkan daftar printer

## Build

```sh
bun install

# Windows (menghasilkan dist/node-printer.exe)
bun run build:win

# Linux (menghasilkan dist/node-printer)
bun run build:linux
```

`bun run build` tetap ada dan sama dengan `build:win` (kompatibel dengan
perintah lama).

Hasil build ada di folder `dist/` (tidak di-commit).

## Menjalankan

Windows:

```powershell
.\dist\node-printer.exe
```

Linux:

```sh
./dist/node-printer
```

Tanpa argumen, aplikasi memastikan daemon berjalan lalu membuka menu setup
(pairing server, pilih printer, scan QR, uninstall).

Perintah lain:

```sh
node-printer --daemon          # jalankan daemon saja
node-printer --setup           # menu setup saja
node-printer qr                # tampilkan QR (butuh binary tauri-qr)
node-printer autostart on|off  # jalan otomatis saat login
node-printer stop              # hentikan daemon (config tetap disimpan)
node-printer status            # cek daemon & status SSE
node-printer uninstall         # hapus daemon, config, dan file aplikasi
```

### Menghentikan daemon

```sh
node-printer stop     # cara normal
node-printer status   # pastikan sudah mati
```

Kalau binary yang terpasang masih versi lama (belum punya perintah `stop`),
matikan manual:

```sh
pkill -f "node-printer --daemon"
# atau cek PID dulu
pgrep -af node-printer
cat ~/.node-printer/status.json
```

Kalau dijalankan lewat systemd: `sudo systemctl stop node-printer`.

Di Windows, daemon juga bisa dihentikan dari ikon system tray (klik kanan →
Keluar) atau lewat menu setup → **Hentikan daemon**.

Saat development:

```sh
bun run dev
bun run daemon
```

## Perbedaan Windows dan Linux

| Bagian | Windows | Linux |
| --- | --- | --- |
| Daftar printer | PowerShell `Win32_Printer` | CUPS `lpstat -p` |
| Cetak PDF | SumatraPDF portable | CUPS `lp` |
| System tray | Ya (PowerShell NotifyIcon) | Tidak (daemon headless) |
| Auto-start | Registry `HKCU\...\Run` | `~/.config/autostart/node-printer.desktop` |
| Uninstall file aplikasi | Dijadwalkan setelah proses tutup | Dihapus setelah proses tutup |

### Windows: SumatraPDF

Taruh `SumatraPDF.exe` (versi portable) di folder yang sama dengan
`node-printer.exe`, atau set:

```powershell
$env:NODE_PRINTER_SUMATRA = "C:\path\SumatraPDF.exe"
```

### Linux: CUPS

Pastikan user yang menjalankan aplikasi boleh mencetak:

```sh
lpstat -p
lp -d NAMA_PRINTER file.pdf
```

Nama printer yang dipilih di menu Printer adalah nama antrean CUPS (contoh
`EPSON_L3210_Series`), bukan nama tampilan seperti di Windows.

Pilihan cetak yang dipetakan ke CUPS:

- ukuran kertas -> `-o media=...`
- orientasi -> `-o portrait` / `-o landscape`
- warna -> `-o print-color-mode=color|monochrome`
- halaman -> `-o page-ranges=...`
- jumlah salinan -> `-n ...`

#### Menjalankan otomatis saat boot (systemd)

`node-printer autostart on` memakai autostart desktop (cocok untuk komputer
yang punya layar/login desktop). Untuk server Linux headless, pakai systemd:

```ini
# /etc/systemd/system/node-printer.service
[Unit]
Description=Node Printer Client
After=network-online.target
Wants=network-online.target

[Service]
ExecStart=/path/ke/dist/node-printer --daemon
Restart=on-failure
User=NAMA_USER

[Install]
WantedBy=multi-user.target
```

```sh
sudo systemctl daemon-reload
sudo systemctl enable --now node-printer
```

## Scan QR (opsional)

Window QR memakai aplikasi Tauri terpisah:

```sh
bun run build:qr
```

Hasil build ada di `tauri-qr/src-tauri/target/release/` (`tauri-qr.exe` di
Windows, `tauri-qr` di Linux). Letakkan di sebelah binary utama, atau set
`NODE_PRINTER_QR_BIN`.

Build Tauri di Linux membutuhkan dependensi webkit2gtk (lihat dokumentasi
Tauri). Kalau QR tidak dibutuhkan di server Linux headless, menu ini boleh
dilewati.

## Lokasi config

Semua config, token, dan log disimpan di:

```
~/.node-printer/
```

Log: `node-printer.log` di folder aplikasi (bisa diganti dengan
`NODE_PRINTER_LOG_DIR`).
