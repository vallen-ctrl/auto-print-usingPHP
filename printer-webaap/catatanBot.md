Last updated: 2026-11-01
Working directory: `D:\laragon\www\printer-webaap`
Branch at time of writing: `fitur/konfirmasi-pembayaran` (commit `cc109e9`)

This file records what has been done, what the current business rules are, and what is
still missing, so the next agent does not have to rediscover it.

## Ringkasan

**Target feature berikutnya: alur pembuatan order kilat (backend formulir kilat), halaman tujuan
setelah "Bayar di Kasir", dan alur konfirmasi penjual. Belum dikerjakan.**

Alur kilat sengaja dikosongkan dulu: `GET /formulir/kilat` masih `Route::view()` statis dan belum
ada endpoint yang membuat order kilat. `POST /order/token` juga masih mensyaratkan session
`terjadwal_order`; alur kilat nanti butuh mekanisme sesi sendiri.

Yang sudah berjalan di halaman `/order/konfirmasi/{order_id}`:

- Data order/file/biaya dibaca dari database (`KonfirmasiPembayaranController`).
- Timer 20 menit mulai saat halaman pertama dibuka (`orders.tanggal_bayar`, tidak di-reset reload).
- QRIS: `POST /order/token` -> `window.snap.pay()` -> cek `/struk/order/{id}` -> fallback ke
  `/struk/{id}`. Finish Redirect URL diatur di dashboard Midtrans, bukan `callbacks.finish` di kode.
- Bayar di Kasir: `POST /order/konfirmasi/{order_id}/kasir` mengubah
  `status_pembayaran = 'processing'` dan `metode_pembayaran = 'kasir'`. Halaman lalu dimuat ulang
  dan tampil "Menunggu konfirmasi kasir" (tanpa timer, CTA nonaktif).
- `createorder_terjadwal()` redirect ke halaman konfirmasi.

Yang masih kurang:

- Konfirmasi penjual: belum ada UI/endpoint untuk mengubah order kasir dari `processing` ke
  `paid`. Saat itu dibuat, file WAJIB dimasukkan ke `file_queue` (pakai logika
  `OrderController::addFileQueue()`, sekarang private). `strukOrder()` hanya menambah antrean
  untuk pembayaran Midtrans.
- Halaman/lokasi setelah user memilih bayar di kasir (diminta dibuat nanti).
- Scheduler pembersihan order kedaluwarsa belum ada; masih dicek saat halaman dibuka.

Menjalankan: `php artisan serve` + `npm run dev` (atau `npm run build` setelah mengubah CSS/JS).
Tes terkait: `php artisan test --filter=KonfirmasiPembayaran` (11 tes) dan
`php artisan test --filter=OrderKadaluarsaPembayaran` (13 tes).

## Detail Perubahan

### `app/Http/Controllers/KonfirmasiPembayaranController.php` (changed)
- `show()`: validasi hex, tangani `paid`/`processing` -> `/struk/{id}`, status lain -> `/` + error,
  kedaluwarsa -> `kadaluarsakan()` + lupakan session + `/` + error.
- Pengecualian: `processing` + `metode_pembayaran = 'kasir'` TIDAK diarahkan ke `/struk`; halaman
  ditampilkan dalam keadaan menunggu kasir (`$order['menunggu_kasir'] = true`).
- `tandaiBayar()` dipanggil saat halaman dibuka (hanya untuk order pending); `sisa_detik` dikirim
  ke view. Data file diambil dari `files` + `configuration` + `paper`.
- `bayarDiKasir()`: syarat session `terjadwal_order`, klaim atomik `pending` -> `processing`,
  set `metode_pembayaran = 'kasir'` dan `tanggal_bayar` bila kosong. Respons JSON
  `{ status, message }`; 403 sesi, 404 order, 409 status, 410 kedaluwarsa.
- `formatUkuran()` byte -> "400 KB" / "0,4 MB".

### `app/Http/Controllers/OrderController.php` (changed)
- `createorder_terjadwal()` -> `redirect()->route('order.konfirmasi', ['order_id' => $orderid])`.
- `bayarSekarang()`: key `message` (bukan `messgae`), tanpa `callbacks.finish`.
- `addFileQueue()` masih `private`; perlu dipakai juga oleh alur konfirmasi kasir nanti.

### `app/Service/KedaluwarsaOrder.php` (changed)
- `tandaiBayar()` idempoten (tidak menimpa `tanggal_bayar`), jadi reload tidak menambah waktu.
- Order `processing`/`paid` tidak dianggap kedaluwarsa (hanya `pending`).

### `resources/views/order-konfirmasi.blade.php` (changed)
- Script Snap sesuai `config('midtrans.is_production')`, `meta csrf-token`.
- `main#halaman-konfirmasi` membawa `data-order-id`, `data-token-url`, `data-kasir-url`,
  `data-status-url`, `data-struk-url`, `data-sisa-detik`, `data-menunggu-kasir`.
- Timer hanya dirender saat masih pending; saat menunggu kasir tampil `.status-notice`.
- Modal: `modal-total` (kilat-edit), `modal-info` (error), `modal-kasir` (petunjuk/sukses),
  `modal-kadaluarsa`.

### `resources/js/pages/order-konfirmasi.js` (changed)
- Countdown 20 menit; 00:00 -> tombol mati + `modal-kadaluarsa`.
- QRIS: `POST data-token-url` -> `snap.pay`; 410 memakai pesan server + mematikan pembayaran.
- Kasir: `POST data-kasir-url` -> modal sukses -> reload halaman (server merender keadaan menunggu).
- `onSuccess`/`onPending` cek `data-status-url`; `onClose` fallback ke `data-struk-url`.
- Saat `data-menunggu-kasir="1"` timer tidak jalan dan semua aksi pembayaran dikunci.

### `resources/css/order-konfirmasi.css` (changed)
- `.timer-bayar` + `.is-habis`, `.status-notice` untuk keadaan menunggu kasir.

### `routes/web.php` (changed)
- `GET /order/konfirmasi/{order_id}` -> `order.konfirmasi`.
- `POST /order/konfirmasi/{order_id}/kasir` -> `order.konfirmasi.kasir`.

### `tests/Feature/KonfirmasiPembayaranTest.php` (new)
- 11 tes: render data, timer tidak di-reset, aturan metode per layanan, redirect status,
  kedaluwarsa, order tak dikenal, kasir -> processing, keadaan menunggu kasir, sesi & expired.
