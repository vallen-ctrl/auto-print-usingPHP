<?php

namespace App\Service;

use App\Models\Computer;
use App\Models\File;
use App\Models\FileQueue;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Menambahkan file milik order yang sudah siap diproses ke `file_queue`.
 *
 * Aturan penempatan komputer:
 *  - kilat-edit  : komputer yang QR-nya dipindai customer (tempat edit file)
 *  - terjadwal + mode tukang : tanpa komputer, file menunggu admin di server
 *  - lainnya (kilat-langsung, terjadwal otomatis) : komputer dengan antrean paling sedikit
 *
 * Idempoten: file yang sudah punya baris antrean tidak ditambahkan lagi.
 */
class AntreanCetak
{
    /**
     * @return array{berhasil: int, gagal: int, dilewati: int}
     */
    public function untukOrder(Order $order): array
    {
        $hasil = ['berhasil' => 0, 'gagal' => 0, 'dilewati' => 0];

        foreach (File::where('order_id', $order->id)->where('dihapus', false)->get() as $file) {
            if (FileQueue::where('file_id', $file->id)->exists()) {
                $hasil['dilewati']++;
                continue;
            }

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

    /**
     * Masukkan semua file lunas yang belum punya baris antrean ke komputer
     * yang sekarang tersedia.
     *
     * Dipakai saat komputer pertama ditambahkan: pembayaran yang terjadi
     * sebelum toko punya komputer sempat gagal masuk antrean, jadi file-nya
     * sekarang ditempatkan ke komputer pertama tersebut.
     *
     * File yang sengaja ditahan di server (terjadwal mode tukang) tetap tidak
     * diberi komputer oleh pilihComputer().
     *
     * @return int jumlah file yang berhasil masuk antrean
     */
    public function untukFileYangBelumTerantre(): int
    {
        $orderIds = File::query()
            ->join('orders', 'orders.id', '=', 'files.order_id')
            ->where('orders.status_pembayaran', 'paid')
            ->where('files.dihapus', false)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('file_queue')
                    ->whereColumn('file_queue.file_id', 'files.id');
            })
            ->distinct()
            ->pluck('files.order_id');

        $berhasil = 0;

        foreach ($orderIds as $orderId) {
            $order = Order::find($orderId);

            if ($order === null) {
                continue;
            }

            $berhasil += $this->untukOrder($order)['berhasil'];
        }

        return $berhasil;
    }

    private function untukFile(Order $order, File $file): void
    {
        FileQueue::create([
            'path'        => $file->path,
            'order_id'    => $order->id,
            'file_id'     => $file->id,
            'computer_id' => $this->pilihComputer($order, $file),
        ]);
    }

    /**
     * Id komputer tujuan, atau null kalau file sengaja ditahan di server.
     */
    private function pilihComputer(Order $order, File $file): ?string
    {
        // Edit di komputer: file harus sampai ke komputer yang dipindai.
        if ($file->layanan === 'kilat-edit' && $order->computer_id !== null) {
            return $order->computer_id;
        }

        // Terjadwal yang dicetak tukang: jangan dikirim ke komputer mana pun.
        if ($file->layanan === 'terjadwal' && $order->mode_cetak === 'tukang') {
            return null;
        }

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

        return $computer->id;
    }
}
