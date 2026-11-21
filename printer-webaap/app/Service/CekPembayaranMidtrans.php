<?php

namespace App\Service;

use App\Models\File;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Midtrans\Transaction;

/**
 * Pengecekan status pembayaran ke Midtrans untuk satu order.
 *
 * Dipakai tombol "Refresh pembayaran" pada halaman detail order di dashboard.
 * Worker cron sengaja tidak memanggil Midtrans; aturan waktu/file tetap
 * ditangani App\Service\PemeriksaPembayaran.
 */
class CekPembayaranMidtrans
{
    public function __construct(private readonly AntreanCetak $antrean) {}

    /**
     * @return array{berubah: bool, status: string|null, pesan: string}
     */
    public function periksa(Order $order): array
    {
        if ($order->status_pembayaran === 'paid') {
            return $this->hasil(false, 'paid', 'Order ini sudah lunas. Tidak ada status yang perlu diperbarui.');
        }

        if ($order->metode_pembayaran === 'kasir') {
            return $this->hasil(
                false,
                $order->status_pembayaran,
                'Order ini dibayar di kasir, jadi statusnya dikonfirmasi lewat halaman Kasir.'
            );
        }

        if ($order->tanggal_bayar === null) {
            return $this->hasil(
                false,
                $order->status_pembayaran,
                'Customer belum menekan "Bayar sekarang", jadi transaksi belum dibuat di Midtrans.'
            );
        }

        try {
            $response = (object) Transaction::status(bin2hex($order->id));
        } catch (\Throwable $e) {
            if ((int) $e->getCode() === 404) {
                return $this->hasil(
                    false,
                    $order->status_pembayaran,
                    'Transaksi belum ditemukan di Midtrans. Coba refresh lagi beberapa saat lagi.'
                );
            }

            Log::error('Refresh pembayaran: gagal menghubungi Midtrans', [
                'order' => bin2hex($order->id),
                'error' => $e->getMessage(),
            ]);

            return $this->hasil(
                false,
                $order->status_pembayaran,
                'Gagal menghubungi Midtrans. Periksa koneksi server lalu coba lagi.'
            );
        }

        $status = $this->petakanStatus($response);

        return match ($status) {
            'paid' => $this->tandaiLunas($order),
            'failed', 'refunded' => $this->tandaiGagal($order, $status),
            default => $this->hasil(
                false,
                $order->status_pembayaran,
                'Belum ada pembayaran baru. Status transaksi di Midtrans masih "'
                    .($response->transaction_status ?? 'pending').'".'
            ),
        };
    }

    private function petakanStatus(object $response): string
    {
        return match ($response->transaction_status ?? null) {
            'settlement' => 'paid',
            'capture' => ($response->fraud_status ?? 'accept') === 'accept' ? 'paid' : 'pending',
            'deny', 'cancel', 'expire', 'failure' => 'failed',
            'refund', 'partial_refund' => 'refunded',
            default => 'pending',
        };
    }

    /**
     * @return array{berubah: bool, status: string|null, pesan: string}
     */
    private function tandaiLunas(Order $order): array
    {
        $diubah = Order::where('id', $order->id)
            ->whereIn('status_pembayaran', ['pending', 'processing', 'failed', 'refunded'])
            ->update([
                'status_pembayaran' => 'paid',
                'total_dibayar' => $order->total_pembayaran,
                'browser_session' => null,
            ]);

        if ($diubah === 0) {
            return $this->hasil(false, $order->status_pembayaran, 'Status order tidak berubah.');
        }

        $order->refresh();

        $masihAdaFile = File::where('order_id', $order->id)->exists();
        $pesan = 'Pembayaran berhasil diverifikasi. Order sekarang lunas.';

        if ($masihAdaFile) {
            $antrean = $this->antrean->untukOrder($order);

            $pesan .= $antrean['berhasil'] > 0
                ? ' '.$antrean['berhasil'].' file masuk antrean cetak.'
                : ' File sudah ada di antrean cetak sebelumnya.';
        } else {
            $pesan .= ' Namun file order sudah dihapus, jadi tidak ada yang masuk antrean cetak.';
        }

        Log::info('Refresh pembayaran: order lunas', ['order' => bin2hex($order->id)]);

        return $this->hasil(true, 'paid', $pesan);
    }

    /**
     * @return array{berubah: bool, status: string|null, pesan: string}
     */
    private function tandaiGagal(Order $order, string $status): array
    {
        $diubah = Order::where('id', $order->id)
            ->whereIn('status_pembayaran', ['pending', 'processing'])
            ->update(['status_pembayaran' => $status]);

        if ($diubah === 0) {
            return $this->hasil(false, $order->status_pembayaran, 'Status order tidak berubah.');
        }

        Log::info('Refresh pembayaran: order tidak lunas', [
            'order' => bin2hex($order->id),
            'status' => $status,
        ]);

        $pesan = $status === 'refunded'
            ? 'Midtrans menyatakan pembayaran di-refund. Order ditandai refunded.'
            : 'Midtrans menyatakan pembayaran gagal atau kedaluwarsa. Order ditandai gagal; file akan dibersihkan oleh worker.';

        return $this->hasil(true, $status, $pesan);
    }

    /**
     * @return array{berubah: bool, status: string|null, pesan: string}
     */
    private function hasil(bool $berubah, ?string $status, string $pesan): array
    {
        return [
            'berubah' => $berubah,
            'status' => $status,
            'pesan' => $pesan,
        ];
    }
}
