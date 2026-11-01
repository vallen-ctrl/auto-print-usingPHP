Last updated: 2026-11-01
Working directory: `D:\laragon\www\printer-webaap`
Branch at time of writing: `fitur/konfirmasi-pembayaran` (commit `888815a`)

This file records what has been done, what the current business rules are, and what is
still missing, so the next agent does not have to rediscover it.

## Ringkasan

**Target feature: alur pembuatan order kilat (backend formulir kilat) dan aturan pembayaran "Bayar di Kasir". Belum ada.**

Halaman `/order/konfirmasi/{order_id}` sekarang terintegrasi penuh dengan backend:

- Data order, file, warna, ukuran, dan biaya dibaca dari database (`KonfirmasiPembayaranController`).
- Hitung mundur 20 menit dimulai saat halaman pertama kali dibuka. Waktu disimpan di
  `orders.tanggal_bayar` dan TIDAK di-reset saat reload (`KedaluwarsaOrder::tandaiBayar()`).
- Tombol QRIS memanggil `POST /order/token` lalu `window.snap.pay()` (pola sama dengan
  `resources/views/snap.blade.php`). Setelah sukses/pending, status dicek ke `/struk/order/{id}`
  lalu fallback redirect ke `/struk/{id}`. Finish Redirect URL pembayaran dikonfigurasi Vallen
  di dashboard Midtrans, jadi JANGAN hardcode `callbacks.finish` di token.
- `POST /order/token` masih mensyaratkan session `terjadwal_order` (orderid harus sama).
  Alur kilat nanti perlu mekanisme sesi sendiri.
- `createorder_terjadwal()` sekarang redirect ke halaman konfirmasi, bukan kembali ke formulir.
- Order kedaluwarsa / sudah dibayar / status lain ditangani di controller: redirect ke `/`
  dengan pesan, atau ke `/struk/{id}` untuk status `paid`/`processing`.

Yang masih kurang:

- `GET /formulir/kilat` masih `Route::view()` statis; belum ada endpoint yang membuat order kilat.
- Pilihan "Bayar di Kasir" baru menampilkan modal petunjuk. Order tetap `pending`, tetap kena
  batas 20 menit, dan filenya dihapus saat kedaluwarsa. Perlu keputusan bisnis: apakah order
  kasir harus menandai `metode_pembayaran`, melewati kedaluwarsa, dan langsung masuk antrean?
- Scheduler pembersihan order kedaluwarsa masih belum ada (tetap dicek saat halaman dibuka).

Menjalankan: `php artisan serve` + `npm run dev` (atau `npm run build` setelah mengubah CSS/JS).
Tes kedaluwarsa: `php artisan test --filter=OrderKadaluarsaPembayaran` (13 tes, SQLite).

## Detail Perubahan

### `app/Http/Controllers/KonfirmasiPembayaranController.php` (changed)
- `show(Request, string $order_id)` validasi hex lewat `KedaluwarsaOrder::cariDariHex()`.
- `paid`/`processing` → redirect `struk.render`; status lain → `/` + `error`; kedaluwarsa →
  `kadaluarsakan()` + hapus session `terjadwal_order` + `/` + `error`.
- Memanggil `tandaiBayar()` saat halaman dibuka, lalu mengirim `sisa_detik` ke view.
- Menyusun `$files` dari tabel `files` + `configuration` + `paper`; `layanan` diambil dari file
  pertama. `harga_menyesuaikan` = `kilat-edit`. Biaya penanganan = sisa total setelah subtotal
  dan biaya admin (0 = baris disembunyikan).
- `formatUkuran()` mengubah byte menjadi "400 KB" / "0,4 MB".

### `app/Service/KedaluwarsaOrder.php` (changed)
- `tandaiBayar()` tidak menimpa `tanggal_bayar` yang sudah ada, jadi reload tidak menambah waktu.
- Komentar kelas diperbarui: halaman konfirmasi memakai service ini.

### `app/Http/Controllers/OrderController.php` (changed)
- `createorder_terjadwal()` → `redirect()->route('order.konfirmasi', ['order_id' => $orderid])`.
- `bayarSekarang()`: respons sukses memakai key `message` (sebelumnya typo `messgae`);
  tidak lagi mengirim `callbacks.finish` (redirect diatur di dashboard Midtrans).

### `resources/views/order-konfirmasi.blade.php` (changed)
- `meta csrf-token` + script Snap sesuai `config('midtrans.is_production')`.
- `main#halaman-konfirmasi` membawa `data-order-id`, `data-token-url`, `data-status-url`,
  `data-struk-url`, `data-sisa-detik`.
- Timer `#timer-bayar` / `#sisa-waktu`, modal `modal-info`, `modal-kasir`, `modal-kadaluarsa`
  (dan `modal-total` untuk kilat-edit).

### `resources/js/pages/order-konfirmasi.js` (changed)
- Countdown dari `data-sisa-detik`; saat 00:00 tombol dinonaktifkan dan `modal-kadaluarsa` dibuka.
- QRIS: `POST data-token-url` → `snap.pay`; 410 memakai pesan server + mematikan pembayaran;
  409/404/error lain tampil di `modal-info`.
- `onSuccess`/`onPending` → cek `data-status-url`; `onClose` → redirect `data-struk-url`.
- Kasir: membuka `modal-kasir`, tidak memanggil Midtrans.

### `resources/css/order-konfirmasi.css` (changed)
- Tambah `.timer-bayar` (chip hitung mundur) dan state `.is-habis` (merah).

### `routes/web.php` & `vite.config.js` (changed)
- Route `GET /order/konfirmasi/{order_id}` → `order.konfirmasi`.
- Entry Vite `resources/css/order-konfirmasi.css` dan `resources/js/pages/order-konfirmasi.js`.
