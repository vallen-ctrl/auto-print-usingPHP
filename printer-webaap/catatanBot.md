Last updated: 2026-11-09
Working directory: `D:\laragon\www\printer-webaap`
Branch at time of writing: `fitur/info-komputer-kilat-edit` (commit `8e1433a`)

This file records what has been done, what the current business rules are, and what is
still missing, so the next agent does not have to rediscover it.

## Ringkasan

**Target feature berikutnya: langkah edit file di komputer toko untuk order `kilat-edit`,
lalu penjual mengubah `processing` menjadi `paid` setelah harga final dihitung. Belum dibuat.**

Yang sudah ada (dibuat di sesi ini):

- `orders.computer_id` (binary 16, nullable, FK `computer.id` ON DELETE SET NULL) diisi dari
  QR komputer yang dipindai. `ComputerController::scanQr` menyimpan id komputer di session
  `kilat_computer_id`; `UploadTerjadwalController::store` menyalinnya ke order saat layanan
  bukan `terjadwal`.
- Halaman konfirmasi `kilat-edit` sekarang menampilkan nama komputer tujuan (kartu
  "Komputer untuk edit", notice, dan label footer "Komputer: <nama>") untuk state `pending`
  maupun `menunggu kasir`. Tanpa `computer_id`, teks lama "Menunggu konfirmasi kasir" dipakai.
- `POST /order/konfirmasi/{id}/kasir` mengembalikan field `komputer` dan pesan yang menyebut
  komputer tujuan untuk `kilat-edit`.
- Migration baru: `2026_11_09_000000_add_computer_id_to_orders_table.php` (sudah dijalankan di
  MySQL dev + `database/database.sqlite` untuk test). Test baru: `tests/Feature/KilatKomputerTest.php`.

Yang masih kurang: halaman/klien di komputer toko untuk membuka file order `kilat-edit`,
mengedit, dan menghitung ulang harga; serta aksi penjual untuk menandai order lunas.

## Detail Perubahan

- `app/Http/Controllers/Api/ComputerController.php` — `scanQr(Request, string)` menyimpan
  `KilatController::COMPUTER_SESSION_KEY` setelah token valid; token kedaluwarsa tidak menyimpan.
- `app/Http/Controllers/KilatController.php` — konstanta `COMPUTER_SESSION_KEY`.
- `app/Http/Controllers/UploadTerjadwalController.php` — order kilat menyimpan `computer_id`;
  `show()` (alur terjadwal) membersihkan session komputer; helper `computerDariSession()`.
- `app/Http/Controllers/KonfirmasiPembayaranController.php` — `namaKomputer()` dan
  `pesanKasir()`; `show()` mengirim `komputer` + `pesan_kasir`, `bayarDiKasir()` mengirim
  `komputer` di JSON.
- `app/Models/Order.php` — `computer_id` masuk `$fillable`.
- `resources/views/order-konfirmasi.blade.php` — kartu komputer, notice, label footer, modal kasir.
- `resources/js/pages/order-konfirmasi.js` — label tombol diganti "Komputer: <nama>" setelah
  `bayarDiKasir` sukses (memakai field `komputer`).
- `resources/css/order-konfirmasi.css` — gaya `.komputer-nama`, `.komputer-icon`, `.komputer-langkah`.
- `databasescheme.md`, `docs/FLOW-UI-UX.md` — dokumentasi kolom + alur baru.
