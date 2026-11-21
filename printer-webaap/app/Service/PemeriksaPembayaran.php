<?php

namespace App\Service;

use App\Models\File;
use App\Models\Order;
use App\Models\PengaturanToko;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Midtrans\Transaction;

/**
 * Worker pemeriksaan pembayaran yang dijalankan berkala lewat cron/scheduler
 * (lihat command `pembayaran:cek`).
 *
 * Tugasnya:
 *  1. Menanyakan status transaksi pending ke Midtrans, supaya pembayaran yang
 *     sudah lunas tidak ikut dibuang hanya karena webhook/polling browser tidak
 *     pernah terpanggil.
 *  2. Menandai order yang lewat batas waktu sebagai gagal lalu menghapus file
 *     uploadnya (aturan sama dengan App\Service\KedaluwarsaOrder).
 *  3. Membersihkan file milik order gagal dari jalur mana pun.
 *  4. Menghapus file order lunas yang umurnya melewati pengaturan toko
 *     `lama_file_dihapus` (pengaturan "file dihapus dalam ... hari").
 */
class PemeriksaPembayaran
{
    /**
     * Kalau Midtrans terus-menerus tidak bisa dihubungi (mis. server key salah),
     * order yang sudah lewat batas waktu lebih dari ini tetap dibersihkan.
     */
    public const TOLERANSI_MIDTRANS_JAM = 24;

    public function __construct(private readonly KedaluwarsaOrder $kedaluwarsa) {}

    /**
     * @return array{
     *     diperiksa: int,
     *     lunas: int,
     *     kedaluwarsa: int,
     *     dilewati: int,
     *     file_gagal: int,
     *     file_retensi: int
     * }
     */
    public function jalankan(bool $tanpaMidtrans = false): array
    {
        $hasil = [
            'diperiksa' => 0,
            'lunas' => 0,
            'kedaluwarsa' => 0,
            'dilewati' => 0,
            'file_gagal' => 0,
            'file_retensi' => 0,
        ];

        // Tanpa server key, Midtrans tidak bisa dihubungi: cukup pakai batas waktu.
        $cekMidtrans = ! $tanpaMidtrans && filled(config('midtrans.server_key'));

        foreach ($this->orderPending() as $order) {
            $hasil['diperiksa']++;
            $status = null;

            if ($cekMidtrans && $this->perluCekMidtrans($order)) {
                try {
                    $status = $this->statusMidtrans($order);
                } catch (\Throwable $e) {
                    // Midtrans tidak bisa dihubungi: jangan hapus file order ini,
                    // supaya order yang sebenarnya sudah lunas tidak ikut hilang.
                    // Kalau galatnya berlangsung lebih dari sehari, file tetap
                    // dibersihkan supaya penyimpanan tidak penuh.
                    $hasil['dilewati']++;

                    Log::warning('Worker pembayaran: gagal cek status Midtrans', [
                        'order' => bin2hex($order->id),
                        'error' => $e->getMessage(),
                    ]);

                    if (now()->lt($this->batasWaktu($order)->addHours(self::TOLERANSI_MIDTRANS_JAM))) {
                        continue;
                    }
                }

                if ($status === 'paid') {
                    if ($this->tandaiLunas($order)) {
                        $hasil['lunas']++;
                    }

                    continue;
                }

                if ($status !== null && $status !== 'pending') {
                    $jumlahFile = $this->jumlahFile($order);

                    if ($this->tandaiGagal($order, $status)) {
                        $hasil['kedaluwarsa']++;
                        $hasil['file_gagal'] += $jumlahFile;
                    }

                    continue;
                }
            }

            if (! $this->kedaluwarsa->sudahKadaluarsa($order)) {
                continue;
            }

            $jumlahFile = $this->jumlahFile($order);

            if ($this->kedaluwarsa->kadaluarsakan($order)) {
                $hasil['kedaluwarsa']++;
                $hasil['file_gagal'] += $jumlahFile;
            }
        }

        $hasil['file_gagal'] += $this->bersihkanFileOrderGagal();
        $hasil['file_retensi'] = $this->hapusFileKedaluwarsa();

        Log::info('Worker pembayaran selesai', $hasil);

        return $hasil;
    }

    /**
     * Order pending yang perlu dilihat worker: sudah pernah dibuatkan token
     * pembayaran, atau sudah melewati batas unggah.
     *
     * @return Collection<int, Order>
     */
    private function orderPending()
    {
        return Order::query()
            ->where('status_pembayaran', 'pending')
            ->where(function ($query) {
                $query->whereNotNull('tanggal_bayar')
                    ->orWhere('tanggal_pesan', '<=', now()->subMinutes(KedaluwarsaOrder::BATAS_UPLOAD_MENIT));
            })
            ->get();
    }

    /**
     * Midtrans hanya ditanya untuk order yang punya token Snap. Pembayaran
     * lewat kasir berstatus processing, jadi tidak ikut di sini.
     */
    private function perluCekMidtrans(Order $order): bool
    {
        return $order->tanggal_bayar !== null
            && $order->metode_pembayaran !== 'kasir'
            // Beri jeda satu menit supaya token yang baru dibuat tidak langsung
            // ditanyakan saat transaksinya belum tercatat di Midtrans.
            && $order->tanggal_bayar->lte(now()->subMinute());
    }

    /**
     * Status Midtrans yang sudah dipetakan ke status_pembayaran aplikasi.
     * Null berarti transaksi belum ada di Midtrans (HTTP 404).
     */
    private function statusMidtrans(Order $order): ?string
    {
        try {
            $response = (object) Transaction::status(bin2hex($order->id));
        } catch (\Throwable $e) {
            if ((int) $e->getCode() === 404) {
                return null;
            }

            throw $e;
        }

        return match ($response->transaction_status ?? null) {
            'settlement' => 'paid',
            'capture' => ($response->fraud_status ?? 'accept') === 'accept' ? 'paid' : 'pending',
            'deny', 'cancel', 'expire', 'failure' => 'failed',
            'refund', 'partial_refund' => 'refunded',
            default => 'pending',
        };
    }

    /**
     * Tandai order lunas dan masukkan filenya ke antrean cetak. Klaim atomik
     * dipakai supaya dua pemanggil (worker dan halaman struk) tidak memproses
     * order yang sama dua kali.
     */
    private function tandaiLunas(Order $order): bool
    {
        $diubah = Order::where('id', $order->id)
            ->where('status_pembayaran', 'pending')
            ->update([
                'status_pembayaran' => 'paid',
                'total_dibayar' => $order->total_pembayaran,
            ]);

        if ($diubah === 0) {
            return false;
        }

        Log::info('Worker pembayaran: order lunas menurut Midtrans', [
            'order' => bin2hex($order->id),
        ]);

        app(AntreanCetak::class)->untukOrder($order->fresh());

        return true;
    }

    /**
     * Midtrans menyatakan pembayaran gagal/kedaluwarsa/refund: tandai order
     * gagal lalu hapus file uploadnya.
     */
    private function tandaiGagal(Order $order, string $status = 'failed'): bool
    {
        $diubah = Order::where('id', $order->id)
            ->where('status_pembayaran', 'pending')
            ->update(['status_pembayaran' => $status]);

        if ($diubah === 0) {
            return false;
        }

        Log::info('Worker pembayaran: order tidak lunas menurut Midtrans', [
            'order' => bin2hex($order->id),
            'status' => $status,
        ]);

        $this->kedaluwarsa->hapusFile($order->id);

        return true;
    }

    /**
     * Batas waktu order: 20 menit setelah menekan "Bayar sekarang", atau
     * 60 menit setelah file diunggah.
     */
    private function batasWaktu(Order $order): CarbonInterface
    {
        return $order->tanggal_bayar !== null
            ? $order->tanggal_bayar->copy()->addMinutes(KedaluwarsaOrder::BATAS_BAYAR_MENIT)
            : $order->tanggal_pesan->copy()->addMinutes(KedaluwarsaOrder::BATAS_UPLOAD_MENIT);
    }

    /**
     * File order gagal seharusnya sudah terhapus. Ini jaring pengaman untuk
     * order yang ditandai gagal dari jalur lain (mis. token Midtrans duplikat).
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

    private function jumlahFile(Order $order): int
    {
        return File::where('order_id', $order->id)->count();
    }
}
