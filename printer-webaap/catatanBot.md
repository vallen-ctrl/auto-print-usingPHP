Last updated: 2026-11-09
Working directory: `D:\laragon\www\printer-webaap`
Branch at time of writing: `fitur/kasir-konfirmasi-order` (commit `d237da2`)

This file records what has been done, what the current business rules are, and what is
still missing, so the next agent does not have to rediscover it.

## Ringkasan

**Target feature berikutnya: UI edit file di komputer toko untuk `kilat-edit` dan
perhitungan harga otomatis (kasir masih mengetik total manual), plus polling status
pembayaran di halaman konfirmasi customer. Belum dibuat.**

Yang sudah ada (dibuat di sesi ini):

- `orders.kode` (`string(8)`, unique `uq_orders_kode`): kode 6 karakter tanpa I/O/0/1,
  dibuat otomatis saat order dibuat dan ditampilkan sebagai pill "Kode order" di
  halaman konfirmasi. Order lama di-backfill oleh migration.
- Tombol **Kembali** di `/order/konfirmasi/{id}` menampilkan peringatan
  (`modal-keluar`): pesanan belum dikonfirmasi akan dianggap gagal kalau keluar.
  Sebelumnya JS cek `GET /order/konfirmasi/{id}/status`; kalau order sudah `paid`,
  yang muncul `modal-sukses-keluar` ("Pembayaran berhasil") lalu redirect `/`.
  Tombol back browser juga ditahan lewat `pushState`/`popstate`. Hanya tombol
  "Ya, batalkan pesanan" yang mengirim POST `/order/konfirmasi/{id}/batalkan`
  → order `failed`, file + `file_queue` + `browser_session` + session
  `terjadwal_order`/`kilat_computer_id` dihapus, lalu redirect `/` dengan pesan
  "Pembayaran sebelumnya dianggap gagal". `batalkan` untuk order `paid`
  dialihkan ke `/` dengan flash `success` (tidak pernah membatalkan order lunas).
- Dashboard kasir `/dashboard/kasir` (menu sidebar "Kasir"): daftar order
  `metode_pembayaran = kasir` + `status_pembayaran = processing`, cari berdasarkan
  nama pelanggan (`user.name`), kode order, atau id hex. Aksi:
  - **Pembayaran berhasil** → input Total dibayar (prefill `total_pembayaran`,
    wajib diisi untuk `kilat-edit`) → `paid`, total diperbarui, file masuk antrean.
  - **Batalkan** → `failed`, file dihapus, `browser_session` dilepas.
- `App\Service\AntreanCetak` dipakai bersama oleh `strukOrder` (QRIS) dan kasir.
- Layout dashboard memuat Flowbite lewat Vite (`resources/js/app.js`), CDN dihapus.
- Formulir kilat (`/print/kilat/formulir/{mode}`): judul "Formulir Cetak Kilat" +
  subtitle mode, **tanpa tanggal/pukul pengambilan** untuk kedua mode.
  Mode `komputer` (kilat-edit) hanya menampilkan **nama penyetak**; semua pengaturan
  cetak disembunyikan (`.konfigurasi-cetak.is-hidden`) dan footer berbunyi
  "SUB TOTAL: akan ditentukan di kasir". `POST /order/create` mengabaikan
  `tanggal_ambil` untuk order kilat dan memakai `now()`.

Belum ada: polling status di halaman konfirmasi (perubahan dari kasir baru terlihat
setelah reload customer), UI edit/cetak di komputer, webhook Midtrans.

## Detail Perubahan

- `app/Http/Controllers/KasirController.php` — index + search, `lunas()`, `batal()`.
- `app/Http/Controllers/KonfirmasiPembayaranController.php` — `kode` di data view,
  `pesanKasir()` menyebut kode order, `batalkan()`.
- `app/Service/KedaluwarsaOrder.php` — `batalkan(Order)`: klaim atomik
  `pending`/`processing+kasir` → `failed`, hapus file, lepas `browser_session`.
- `app/Service/AntreanCetak.php` — `untukOrder(Order)` memilih komputer paling sepi.
- `app/Http/Controllers/UploadTerjadwalController.php` — `kodeOrderBaru()`.
- `resources/views/Dashboard/selection/kasir.blade.php` — halaman kasir + 2 modal.
- `resources/views/Dashboard/layout.blade.php` — menu Kasir + Vite app.js.
- `resources/views/order-konfirmasi.blade.php` — pill kode, form batal tersembunyi,
  tombol Kembali pakai `data-batal-url`.
- `resources/js/pages/order-konfirmasi.js`, `resources/js/shared/back-button.js` — klik
  Kembali submit form batal; back-button.js melewati tombol ber-`data-batal-url`.
- `database/migrations/2026_11_09_000001_add_kode_to_orders_table.php` — kolom + backfill.
- `tests/Feature/KasirKonfirmasiTest.php` — 10 test untuk kasir, kode, dan tombol Kembali.
