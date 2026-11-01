Last updated: 2026-11-01
Working directory: `D:\laragon\www\printer-webaap`
Branch at time of writing: `fitur/konfirmasi-pembayaran` (commit `4e80017`)

This file records what has been done, what the current business rules are, and what is
still missing, so the next agent does not have to rediscover it.

## Ringkasan

**Target feature: menyambungkan halaman konfirmasi pembayaran ke data order asli dan ke popup Midtrans Snap. Belum dikerjakan.**

UI halaman `/order/konfirmasi/{order_id}` sudah ada dan bisa dipratinjau. Datanya masih
contoh (`KonfirmasiPembayaranController`), karena integrasi database/API wajib menunggu izin
Vallen. Yang belum ada: pembacaan Order + File asli, tombol CTA ke `POST /order/token`, dan
pemanggilan `window.snap.pay()`.

Aturan metode bayar per `files.layanan` (sudah diterapkan di UI):

- `kilat-langsung`: pilihan QRIS atau Bayar di Kasir.
- `kilat-edit`: hanya Bayar di Kasir. Total, subtotal cetak, dan harga per file kosong
  (`Rp. —`) karena file masih bisa diedit; tombol total membuka `<x-modal id="modal-total">`.
- `terjadwal`: hanya QRIS.

Kontrak backend pembayaran yang masih berlaku (dari `app/Service/KedaluwarsaOrder.php`):

- `POST /order/token` (`create.snap.token`) mengembalikan `200 { token, batas_bayar_menit: 20 }`,
  `410` order kedaluwarsa (file sudah dihapus), `409` status bukan `pending`, `404` session/order
  tidak cocok. Untuk non-200 tampilkan `message` dan arahkan user ke `/`.
- Order `pending` tanpa `tanggal_bayar` kedaluwarsa 60 menit setelah `orders.tanggal_pesan`;
  dengan `tanggal_bayar` kedaluwarsa 20 menit setelahnya (`tanggal_bayar` diisi saat token dibuat).
- Setelah callback Snap, polling `GET /struk/order/{orderIdHex}` (`order.struk`). Jangan pernah
  set `processing` sebelum pengecekan di `OrderController::strukOrder()`.
- Tidak ada scheduler; kedaluwarsa hanya dibersihkan saat halaman terkait dibuka.

Catatan build: CSS/JS halaman baru terdaftar di `vite.config.js`, jadi jalankan `npm run build`
setelah mengubahnya.

## Detail Perubahan

### `app/Http/Controllers/KonfirmasiPembayaranController.php` (new)
- `show(Request, string $order_id)` merakit `$order`, `$files`, `$metode` untuk view.
- Data masih placeholder; parameter `?layanan=kilat-langsung|kilat-edit|terjadwal` hanya untuk
  pratinjau ketiga alur. Ganti isi `$order`/`$files` dengan `Order` + `File` asli saat sudah
  diizinkan; struktur data ke view tidak perlu berubah.
- `$order['harga_menyesuaikan']` = true untuk `kilat-edit` dan mengatur tampilan total kosong.

### `resources/views/order-konfirmasi.blade.php` (new)
- Struktur: header (tombol kembali + judul), badge pengambilan, kartu Rincian dokumen, kartu
  Pilih Metode Pembayaran, kartu Ringkasan Pembayaran, bilah Total Bayar + CTA.
- Metode bayar dirender dari `$metode`; opsi pertama otomatis terpilih (`role="radio"`).
- `<x-modal id="modal-total">` hanya dirender untuk `kilat-edit`, dibuka lewat `data-modal-open`.
- Butuh `@stack('scripts')` karena komponen modal mem-push script-nya.

### `resources/css/order-konfirmasi.css` (new)
- Mobile-first, breakpoint 768px dan 1024px (dua kolom di desktop). Token warna/ukuran sama
  dengan halaman sketsa lain; `--color-chip` untuk latar opsi terpilih.
- Label CTA ditumpuk (`.cta-labels`) supaya lebar tombol tidak berubah saat metode diganti.

### `resources/js/pages/order-konfirmasi.js` (new)
- Klik/Enter/Space/panah pada `.pay-option` memindahkan pilihan, mengubah `aria-checked` dan
  `tabindex`, lalu menampilkan label CTA yang cocok (`data-cta`).

### `routes/web.php` (changed)
- Tambah `GET /order/konfirmasi/{order_id}` dengan nama `order.konfirmasi`.

### `vite.config.js` (changed)
- Tambah entry `resources/css/order-konfirmasi.css` dan `resources/js/pages/order-konfirmasi.js`.

### `app/Service/KedaluwarsaOrder.php` (existing, unchanged)
- Sumber aturan kedaluwarsa order; dipakai juga oleh halaman order lain. Jangan duplikasi logikanya.
