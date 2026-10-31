# ERD Database

```mermaid
erDiagram
    USER {
        string sessionid PK
        string name
        string no_wa
        timestamp create_at
    }

    LAYANAN {
        bigint id PK
        string nama
        text deskripsi
        bigint harga
        timestamp tanggal_dibuat
    }

    TOKO {
        binary id PK
        string name
        string username UK
        string password
        timestamp tanggal_dibuat
        timestamp tanggal_update
        binary token
    }

    PAPER {
        bigint id PK
        string name UK
        int width
        int height
        bigint harga_color
        bigint harga_grayscale
    }

    COMPUTER {
        binary id PK
        string name
        binary token UK
        timestamp tanggal_dibuat
        binary toko_id FK
        enum status
    }

    PENGATURAN_TOKO {
        bigint id PK
        boolean status_buka
        bigint biaya_admin
        bigint biaya_layanan
        binary id_toko FK, UK
        tinyint lama_file_dihapus
    }

    JAM_OPERASIONAL {
        bigint id PK
        tinyint nomor_hari UK
        string hari
        time waktu_buka
        time waktu_tutup
        boolean apakah_dibuka
        bigint id_pengaturan FK
    }

    METODE_PEMBAYARAN {
        binary id PK
        string nama
        text webhook
        text token
        text image_path
        bigint id_pengaturan FK
    }

    ORDERS {
        binary id PK
        timestamp tanggal_pesan
        timestamp tanggal_bayar
        datetime tanggal_ambil
        bigint biaya_admin
        bigint total_pembayaran
        bigint total_dibayar
        enum status_pembayaran
        string metode_pembayaran
        binary id_metode_pembayaran FK
        string session_id FK
    }

    FILES {
        binary id PK
        text name
        text path
        bigint size
        enum mime
        string diupload_oleh FK
        timestamp tanggal_dibuat
        smallint total_pages
        binary order_id FK
        boolean dihapus
        bigint subtotal
        enum layanan
    }

    LAYANAN_TERPILIH {
        bigint id PK
        binary files_id FK
        bigint layanan_id FK
        string nama_layanan
    }

    CONFIGURATION {
        bigint id PK
        binary file_id FK
        enum orientasi
        enum warna
        string ukuran
        enum kualitas
        string skala
        smallint jumlah_cetak
        text custom_halaman
        text catatan_tambahan
        bigint paper_id FK
        smallint pages_toprint
    }

    FILE_QUEUE {
        bigint id PK
        timestamp tanggal_dibuat
        enum status
        text path
        binary order_id FK
        binary file_id FK
        binary computer_id FK
    }

    TOKO ||--o{ COMPUTER : "memiliki"
    TOKO ||--|| PENGATURAN_TOKO : "punya pengaturan"
    PENGATURAN_TOKO ||--o{ JAM_OPERASIONAL : "mengatur"
    PENGATURAN_TOKO ||--o{ METODE_PEMBAYARAN : "menyediakan"
    METODE_PEMBAYARAN |o--o{ ORDERS : "dipakai di"
    USER |o--o{ ORDERS : "membuat"
    USER |o--o{ FILES : "upload"
    ORDERS |o--o{ FILES : "berisi"
    ORDERS ||--o{ FILE_QUEUE : "masuk antrian"
    FILES |o--o{ FILE_QUEUE : "diantrikan"
    COMPUTER |o--o{ FILE_QUEUE : "memproses"
    FILES ||--o{ CONFIGURATION : "dikonfigurasi"
    PAPER |o--o{ CONFIGURATION : "ukuran kertas"
    FILES |o--o{ LAYANAN_TERPILIH : "memilih"
    LAYANAN |o--o{ LAYANAN_TERPILIH : "dipilih"
```

## Daftar Relasi (Foreign Key)

| Tabel Anak | Kolom FK | Tabel Induk | On Update | On Delete |
|---|---|---|---|---|
| computer | toko_id | toko.id | cascade | cascade |
| pengaturan_toko | id_toko | toko.id | cascade | cascade |
| jam_operasional | id_pengaturan | pengaturan_toko.id | cascade | cascade |
| metode_pembayaran | id_pengaturan | pengaturan_toko.id | cascade | set null |
| orders | id_metode_pembayaran | metode_pembayaran.id | cascade | set null |
| orders | session_id | user.sessionid | cascade | set null |
| files | diupload_oleh | user.sessionid | cascade | set null |
| files | order_id | orders.id | cascade | restrict |
| layanan_terpilih | files_id | files.id | cascade | set null |
| layanan_terpilih | layanan_id | layanan.id | cascade | set null |
| configuration | file_id | files.id | cascade | cascade |
| configuration | paper_id | paper.id | cascade | set null |
| file_queue | order_id | orders.id | cascade | cascade |
| file_queue | file_id | files.id | cascade | set null |
| file_queue | computer_id | computer.id | cascade | set null |

## Nilai Enum

| Tabel.Kolom | Nilai |
|---|---|
| computer.status | not connected, shutdown, pending, connected, warning |
| orders.status_pembayaran | pending, processing, success, fail |
| files.mime | pdf, doc, docx, xls, xlsx, jpeg, png, octet-stream |
| files.layanan | kilat-edit, kilat-langsung, terjadwal |
| configuration.orientasi | potrait, landscape |
| configuration.warna | berwarna, hitam putih |
| configuration.kualitas | rendah, standar, tinggi |
| file_queue.status | pending, processing, completed, failed, downloading |

## Catatan

- `jam_operasional.nomor_hari` bersifat `UNIQUE` secara global, bukan per `id_pengaturan`. Kalau nanti ada lebih dari satu toko, tiap toko tidak bisa punya jam untuk hari yang sama. Sebaiknya diganti unique komposit `(id_pengaturan, nomor_hari)`.
- `configuration.file_id` tidak unique, jadi secara skema satu file bisa punya banyak konfigurasi. Kalau maksudnya 1:1, tambahkan `unique`.