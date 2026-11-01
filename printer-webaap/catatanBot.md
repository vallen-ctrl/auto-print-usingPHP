Last updated: 2026-11-01
Working directory: `D:\laragon\www\printer-webaap`
Branch at time of writing: `fitur/konfirmasi-pembayaran` (commit `d0abf27`)

This file records what has been done, what the current business rules are, and what is
still missing, so the next agent does not have to rediscover it.

## Ringkasan

**Target feature berikutnya: alur order kilat (backend formulir kilat), halaman tujuan setelah
"Bayar di Kasir", dan alur konfirmasi penjual. Belum dikerjakan.**

Alur kilat sengaja dikosongkan dulu: `GET /formulir/kilat` masih `Route::view()` statis dan belum
ada endpoint yang membuat order kilat. `POST /order/token` masih mensyaratkan session
`terjadwal_order`; alur kilat nanti butuh mekanisme sesi sendiri.

Beranda kini melanjutkan order dari DATABASE, bukan dari isi session:

- `orders.browser_session` (Laravel session id) diisi sejak file diupload.
- `GET /` mencari order terbaru dengan penanda itu: belum submit formulir -> formulir terjadwal;
  sudah submit (`total_pembayaran > 0`) -> halaman konfirmasi/metode pembayaran; menunggu kasir ->
  halaman konfirmasi; pembayaran online -> `/struk/{id}`; paid/gagal -> beranda + penanda dilepas.
- Session `terjadwal_order` dibangun ulang dari DB (daftar file + total_pages) saat melanjutkan,
  jadi tombol bayar/formulir tetap bekerja walau tab sempat tertutup.

Konfirmasi pembayaran:

- Timer 20 menit mulai saat halaman pertama dibuka (`orders.tanggal_bayar`, tidak di-reset reload).
- QRIS: `POST /order/token` -> `snap.pay`; `onSuccess`/`onPending` menunggu status tercatat
  (retry singkat) lalu pindah ke `/struk/{id}`; `localStorage.clear()` saat sukses. `onClose`
  hanya cadangan. Finish Redirect URL tetap bisa diatur di dashboard Midtrans.
- Kasir: `POST /order/konfirmasi/{id}/kasir` -> `status_pembayaran = 'processing'`,
  `metode_pembayaran = 'kasir'`. Halaman reload menampilkan "Pesanan berhasil" + "Silakan bayar
  di kasir" (kilat-edit: harga masih perkiraan; kilat-langsung: harga sudah sesuai).
- `strukOrder()` saat `paid` melepas session alur + `orders.browser_session`.

Skema pembayaran (migration 2026_11_01):
- `orders.metode_pembayaran` sekarang ENUM(`qris`, `kasir`) NULL.
- `orders.id_metode_pembayaran` dihapus dan tabel `metode_pembayaran` di-drop.
- Model `App\Models\MetodePembayaran` + factory-nya sudah tidak terpakai (tabelnya tidak ada).

Yang masih kurang:

- Konfirmasi penjual: belum ada UI/endpoint `processing` -> `paid` untuk kasir. Saat dibuat,
  file WAJIB dimasukkan ke `file_queue` (pakai `OrderController::addFileQueue()`, masih private).
- Halaman/lokasi setelah user memilih bayar di kasir (diminta dibuat nanti).
- Scheduler pembersihan order kedaluwarsa belum ada; masih dicek saat halaman dibuka.

Menjalankan: `php artisan migrate` (kolom `browser_session`), `npm run build` setelah ubah CSS/JS.
Tes: `php artisan test --filter="KonfirmasiPembayaran|OrderKadaluarsaPembayaran"` (30 tes).

## Detail Perubahan

### `app/Http/Controllers/BerandaController.php` (new)
- Menggantikan closure `/` di `routes/web.php`.
- Cari `Order::where('browser_session', $request->session()->getId())` terbaru.
- Urutan pengecekan: kedaluwarsa -> `kadaluarsakan()` + lepas penanda; `paid` -> beranda;
  `processing` + kasir -> konfirmasi; `processing` lain -> `/struk/{id}`; failed/refunded ->
  beranda; `pending` + total > 0 -> konfirmasi; `pending` + total 0 -> formulir.
- `pulihkanSession()` membangun ulang session `terjadwal_order` (orderid, sessionid, files,
  localPath) dari tabel `files`.
- `lupakanOrder()` menghapus session `terjadwal_order` dan meng-null-kan `browser_session`.

### `database/migrations/2026_11_01_000000_add_browser_session_to_orders_table.php` (new)
- Menambah `orders.browser_session` VARCHAR(255) NULL + index. Sudah dijalankan di DB dev.

### `database/migrations/2026_11_01_000001_remove_metode_pembayaran_reference.php` (new)
- `orders.metode_pembayaran` diubah menjadi ENUM(`qris`, `kasir`) NULL.
- Drop FK + kolom `orders.id_metode_pembayaran`, lalu `Schema::dropIfExists('metode_pembayaran')`.
- Sudah dijalankan di DB dev. Data lama `metode_pembayaran` semuanya NULL, jadi aman.

### `app/Models/Order.php` (changed)
- `browser_session` masuk `$fillable`; `id_metode_pembayaran` dikeluarkan.

### `app/Http/Controllers/UploadTerjadwalController.php` (changed)
- `store()` mengisi `browser_session` dengan `$request->session()->getId()` saat order dibuat.
- `show()` (mulai order baru) meng-null-kan `browser_session` pada order pending milik sesi ini.

### `app/Http/Controllers/OrderController.php` (changed)
- `createorder_terjadwal()` redirect ke halaman konfirmasi.
- Formulir terjadwal menampilkan modal error kalau file tidak punya `total_pages` (tidak terbaca)
  atau semua harga kertas 0 (subtotal Rp. 0). JS formulir juga membuka modal yang sama kalau
  subtotal awal terhitung 0. Menutup modal langsung mengarahkan ke `/upload/terjadwal`.
- `bayarSekarang()`: key `message` (bukan `messgae`), tanpa `callbacks.finish`; setelah token jadi
  mengisi `orders.metode_pembayaran = 'qris'`.
- `strukOrder(Request, string $orderId)`: jika status akhir `paid`, `session()->forget()`
  `UploadTerjadwalController::SESSION_KEY` dan `browser_session = null`.
- `addFileQueue()` masih `private`; perlu dipakai juga oleh alur konfirmasi kasir nanti.

### `app/Service/KedaluwarsaOrder.php` (changed)
- `tandaiBayar()` idempoten (tidak menimpa `tanggal_bayar`), jadi reload tidak menambah waktu.
- Order `processing`/`paid` tidak dianggap kedaluwarsa (hanya `pending`).

### `app/Http/Controllers/FormulirTerjadwalController.php` (changed)
- `pesanFormulir()` mengirim `$pesanFormulir` ke view saat: sesi/order kosong, file kosong,
  ada `files.total_pages <= 0`, tidak ada paper aktif, atau semua harga paper 0.

### `resources/views/formulir-terjadwal.blade.php` (changed)
- Modal `modal-formulir` (terbuka otomatis, pesan dari server) dan `modal-subtotal`.

### `resources/js/pages/formulir-terjadwal.js` (changed)
- Setelah subtotal awal dihitung: kalau hasilnya 0 dan `modal-formulir` belum terbuka,
  `bukaModal('modal-subtotal')`.
- Modal dengan atribut `data-upload-redirect` (modal-formulir & modal-subtotal) mengarahkan ke
  `/upload/terjadwal` saat ditutup (`modal-close`).

### `resources/js/pages/order-konfirmasi.js` (changed)
- Countdown 20 menit; 00:00 -> tombol mati + `modal-kadaluarsa`.
- QRIS: `POST data-token-url` -> `snap.pay`; 410 memakai pesan server + mematikan pembayaran.
  `onSuccess`/`onPending` -> `tungguStatusTercatat()` (5x @900ms ke `data-status-url`) lalu
  `window.location.href = data-struk-url`. `onSuccess` juga `localStorage.clear()`.
- Kasir: `POST data-kasir-url` -> modal sukses ("Pesanan berhasil") -> reload halaman.
- Saat `data-menunggu-kasir="1"` timer tidak jalan dan semua aksi pembayaran dikunci.

### `resources/views/test_struk.blade.php` (changed)
- Saat status `paid`, `localStorage.clear()` (cadangan kalau user mendarat langsung di sini).

### `resources/views/order-konfirmasi.blade.php` (changed)
- Script Snap sesuai `config('midtrans.is_production')`, `meta csrf-token`.
- `main#halaman-konfirmasi` membawa `data-order-id`, `data-token-url`, `data-kasir-url`,
  `data-status-url`, `data-struk-url`, `data-sisa-detik`, `data-menunggu-kasir`.
- Timer hanya dirender saat pending; saat menunggu kasir tampil `.status-notice`.
- Modal: `modal-total` (kilat-edit), `modal-info` (error), `modal-kasir` (petunjuk/sukses),
  `modal-kadaluarsa`.

### `routes/web.php` (changed)
- `GET /` -> `BerandaController`.
- `GET /order/konfirmasi/{order_id}` -> `order.konfirmasi`.
- `POST /order/konfirmasi/{order_id}/kasir` -> `order.konfirmasi.kasir`.

### `tests/Feature/KonfirmasiPembayaranTest.php`, `OrderKadaluarsaPembayaranTest.php`, `FormulirTerjadwalTest.php`
- 33 tes gabungan. Tes `/` memakai cookie `config('session.cookie')` + helper
  `tandaiSesiBeranda()` supaya id sesi request sama dengan `browser_session` di DB.
- `FormulirTerjadwalTest`: pesan halaman tidak terbaca, harga kertas 0, dan data siap.
