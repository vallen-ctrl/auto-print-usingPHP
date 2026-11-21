<?php

namespace App\Console\Commands;

use App\Service\PemeriksaPembayaran;
use Illuminate\Console\Command;

/**
 * Worker yang dijalankan cron: cek pembayaran pending, gagalkan yang lewat
 * batas waktu (20 menit setelah "Bayar sekarang" / 60 menit setelah upload),
 * hapus file order gagal, dan hapus file order lunas sesuai pengaturan
 * "lama disimpan" (pengaturan_toko.lama_file_dihapus).
 *
 * Scheduler menjalankannya tiap 5 menit (lihat routes/console.php). Kalau
 * dipanggil langsung lewat cron, sesuaikan sendiri intervalnya, misalnya:
 *   *\/5 * * * * cd /path/ke/printer-webaap && php artisan pembayaran:cek >> /dev/null 2>&1
 */
class CekPembayaran extends Command
{
    protected $signature = 'pembayaran:cek
                            {--tanpa-midtrans : Lewati pengecekan status transaksi ke Midtrans}';

    protected $description = 'Periksa pembayaran pending, gagalkan yang kedaluwarsa, dan hapus file sesuai pengaturan toko';

    public function handle(PemeriksaPembayaran $pemeriksa): int
    {
        $hasil = $pemeriksa->jalankan((bool) $this->option('tanpa-midtrans'));

        $this->table(
            ['Keterangan', 'Jumlah'],
            [
                ['Order pending diperiksa', $hasil['diperiksa']],
                ['Order lunas menurut Midtrans', $hasil['lunas']],
                ['Order kedaluwarsa / gagal', $hasil['kedaluwarsa']],
                ['Order dilewati (Midtrans tidak terjangkau)', $hasil['dilewati']],
                ['File order gagal dihapus', $hasil['file_gagal']],
                ['File retensi dihapus', $hasil['file_retensi']],
            ]
        );

        return self::SUCCESS;
    }
}
