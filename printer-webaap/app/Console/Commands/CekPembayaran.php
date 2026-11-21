<?php

namespace App\Console\Commands;

use App\Service\PemeriksaPembayaran;
use Illuminate\Console\Command;

/**
 * Worker yang dijalankan cron: gagalkan order yang lewat batas waktu
 * (20 menit setelah "Bayar sekarang" / 60 menit setelah upload), hapus file
 * order gagal, dan hapus file order lunas sesuai pengaturan "lama disimpan"
 * (pengaturan_toko.lama_file_dihapus).
 *
 * Pengecekan status pembayaran ke Midtrans dilakukan manual dari halaman
 * detail order (tombol "Refresh pembayaran").
 *
 * Scheduler menjalankannya tiap 5 menit (lihat routes/console.php). Kalau
 * dipanggil langsung lewat cron, sesuaikan sendiri intervalnya, misalnya:
 *   *\/5 * * * * cd /path/ke/printer-webaap && php artisan pembayaran:cek >> /dev/null 2>&1
 */
class CekPembayaran extends Command
{
    protected $signature = 'pembayaran:cek';

    protected $description = 'Gagalkan order kedaluwarsa dan hapus file sesuai pengaturan toko';

    public function handle(PemeriksaPembayaran $pemeriksa): int
    {
        $hasil = $pemeriksa->jalankan();

        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Order kedaluwarsa / gagal', $hasil['kedaluwarsa']],
                ['File order gagal dihapus', $hasil['file_gagal']],
                ['File retensi dihapus', $hasil['file_retensi']],
            ]
        );

        return self::SUCCESS;
    }
}
