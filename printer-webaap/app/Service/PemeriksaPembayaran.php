<?php

namespace App\Service;

use App\Models\File;
use App\Models\PengaturanToko;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Worker berkala yang dijalankan lewat cron/scheduler (lihat command
 * `pembayaran:cek`).
 *
 * Tugasnya hanya soal waktu dan file:
 *  1. Menandai order yang lewat batas waktu sebagai gagal lalu menghapus file
 *     uploadnya (aturan sama dengan App\Service\KedaluwarsaOrder).
 *  2. Membersihkan file milik order gagal dari jalur mana pun.
 *  3. Menghapus file order lunas yang umurnya melewati pengaturan toko
 *     `lama_file_dihapus` (pengaturan "file dihapus dalam ... hari").
 *
 * Pengecekan status transaksi ke Midtrans tidak dilakukan di sini; itu
 * dijalankan manual dari halaman detail order (tombol "Refresh pembayaran").
 */
class PemeriksaPembayaran
{
    public function __construct(private readonly KedaluwarsaOrder $kedaluwarsa) {}

    /**
     * @return array{kedaluwarsa: int, file_gagal: int, file_retensi: int}
     */
    public function jalankan(): array
    {
        $hasil = [
            'kedaluwarsa' => $this->kedaluwarsa->kadaluarsakanSemua(),
            'file_gagal' => $this->bersihkanFileOrderGagal(),
            'file_retensi' => $this->hapusFileKedaluwarsa(),
        ];

        Log::info('Worker pembayaran selesai', $hasil);

        return $hasil;
    }

    /**
     * File order gagal seharusnya sudah terhapus. Ini jaring pengaman untuk
     * order yang ditandai gagal dari jalur lain (mis. token Midtrans duplikat
     * atau pembayaran yang dinyatakan gagal dari halaman detail).
     */
    private function bersihkanFileOrderGagal(): int
    {
        $orderIds = File::query()
            ->join('orders', 'orders.id', '=', 'files.order_id')
            ->where('orders.status_pembayaran', 'failed')
            ->where('orders.tanggal_pesan', '<=', now()->subMinutes(KedaluwarsaOrder::BATAS_BAYAR_MENIT))
            ->distinct()
            ->pluck('files.order_id');

        $jumlah = 0;

        foreach ($orderIds as $orderId) {
            $jumlah += File::where('order_id', $orderId)->count();
            $this->kedaluwarsa->hapusFile($orderId);
        }

        return $jumlah;
    }

    /**
     * Hapus file order lunas yang sudah melewati `lama_file_dihapus` hari.
     *
     * Baris file tetap disimpan supaya riwayat/struk tidak hilang; hanya file
     * fisiknya yang dihapus dan `files.dihapus` diubah menjadi true. File yang
     * masih menunggu dicetak (antrean pending/processing/downloading) dilewati
     * agar printer tidak kehilangan sumbernya.
     */
    private function hapusFileKedaluwarsa(): int
    {
        $lamaHari = (int) (PengaturanToko::find(1)?->lama_file_dihapus ?? 2);

        if ($lamaHari < 1) {
            $lamaHari = 1;
        }

        $batas = now()->subDays($lamaHari);

        $files = File::query()
            ->join('orders', 'orders.id', '=', 'files.order_id')
            ->where('files.dihapus', false)
            ->where('orders.status_pembayaran', 'paid')
            ->whereRaw(
                'COALESCE(orders.tanggal_bayar, orders.tanggal_pesan) <= ?',
                [$batas->toDateTimeString()]
            )
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('file_queue')
                    ->whereColumn('file_queue.file_id', 'files.id')
                    ->whereIn('file_queue.status', ['pending', 'processing', 'downloading']);
            })
            ->select('files.*')
            ->get();

        $jumlah = 0;

        foreach ($files as $file) {
            DB::transaction(function () use ($file) {
                if ($file->path) {
                    Storage::disk('local')->delete($file->path);
                }

                File::where('id', $file->id)->update(['dihapus' => true]);
            });

            $jumlah++;

            Log::info('Worker pembayaran: file retensi dihapus', [
                'file' => bin2hex($file->id),
                'order' => bin2hex($file->order_id),
                'lama_hari' => $lamaHari,
            ]);
        }

        return $jumlah;
    }
}
