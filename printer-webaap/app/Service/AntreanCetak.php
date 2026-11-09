<?php

namespace App\Service;

use App\Models\Computer;
use App\Models\File;
use App\Models\FileQueue;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Menambahkan file milik order yang sudah lunas ke antrean komputer toko.
 *
 * Dipakai dua jalur pembayaran: QRIS (strukOrder) dan konfirmasi kasir di
 * dashboard. Antrean dibuat per file, memilih komputer dengan antrean
 * pending/processing paling sedikit.
 */
class AntreanCetak
{
    /**
     * @return array{berhasil: int, gagal: int} Jumlah file yang masuk antrean.
     */
    public function untukOrder(Order $order): array
    {
        $hasil = ['berhasil' => 0, 'gagal' => 0];

        foreach (File::where('order_id', $order->id)->get() as $file) {
            try {
                $this->untukFile($order, $file);
                $hasil['berhasil']++;
            } catch (\Throwable $e) {
                $hasil['gagal']++;

                // Antrean gagal (mis. tidak ada komputer) tidak boleh
                // menggagalkan konfirmasi pembayaran.
                Log::warning('Gagal menambahkan file ke antrean cetak', [
                    'order' => bin2hex($order->id),
                    'file' => bin2hex($file->id),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $hasil;
    }

    private function untukFile(Order $order, File $file): void
    {
        $computer = Computer::from('computer as c')
            ->leftJoin('file_queue as f', function ($join) {
                $join->on('f.computer_id', '=', 'c.id')
                    ->whereIn('f.status', ['pending', 'processing']);
            })
            ->select('c.id', 'c.name')
            ->groupBy('c.id', 'c.name', 'c.tanggal_dibuat')
            ->orderByRaw('COUNT(f.id)')
            ->orderBy('c.tanggal_dibuat')
            ->first();

        if (! $computer) {
            throw new \RuntimeException('Tidak ada komputer yang tersedia.');
        }

        FileQueue::create([
            'path'        => $file->path,
            'order_id'    => $order->id,
            'file_id'     => $file->id,
            'computer_id' => $computer->id,
        ]);
    }
}
