<?php

namespace App\Service;

use App\Models\File;
use App\Models\FileQueue;
use App\Models\LayananTerpilih;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Aturan kedaluwarsa order pada alur pembayaran.
 *
 * Catatan: bagian front-end pembayaran (halaman bayar / popup Midtrans) belum
 * dibuat. Service ini menyiapkan aturan waktunya, jadi saat front-end
 * pembayaran dibuat tinggal memakai method di sini:
 *  - user kembali ke "/" atau membuka formulir → sudahKadaluarsa() lalu kadaluarsakan()
 *  - user menekan "Bayar sekarang"               → sudahKadaluarsa() lalu tandaiBayar()
 *  - admin membuka halaman dashboard order       → kadaluarsakanSemua()
 *
 * Dua batas waktu yang dipakai:
 *  - BATAS_UPLOAD_MENIT: dihitung dari orders.tanggal_pesan (file baru diupload,
 *    belum pernah menekan "Bayar sekarang").
 *  - BATAS_BAYAR_MENIT: dihitung dari orders.tanggal_bayar (user sudah menekan
 *    "Bayar sekarang", Snap token Midtrans berlaku 15 menit).
 *
 * Order yang lewat batas waktu akan diubah status_pembayaran menjadi "failed"
 * dan file uploadnya dihapus dari server.
 */
class KedaluwarsaOrder
{
    /** Batas waktu sejak file diupload selama order masih pending (menit). */
    public const BATAS_UPLOAD_MENIT = 60;

    /**
     * Batas waktu setelah user menekan "Bayar sekarang" (menit).
     * Midtrans menganggap Snap token kedaluwarsa setelah 15 menit, jadi diberi
     * kelonggaran 5 menit untuk menyelesaikan pembayaran.
     */
    public const BATAS_BAYAR_MENIT = 20;

    /**
     * Apakah order sudah melewati batas waktunya.
     *
     * Catatan: fungsi ini disiapkan untuk alur pembayaran di front-end yang
     * belum dibuat; sekarang dipakai halaman "/", formulir, dan bayarSekarang().
     */
    public function sudahKadaluarsa(Order $order): bool
    {
        // Hanya order yang belum dibayar yang punya batas waktu.
        if ($order->status_pembayaran !== 'pending') {
            return false;
        }

        if ($order->tanggal_bayar !== null) {
            return $order->tanggal_bayar
                ->copy()
                ->addMinutes(self::BATAS_BAYAR_MENIT)
                ->isPast();
        }

        return $order->tanggal_pesan !== null
            && $order->tanggal_pesan
                ->copy()
                ->addMinutes(self::BATAS_UPLOAD_MENIT)
                ->isPast();
    }

    /**
     * Pesan untuk user ketika order-nya kedaluwarsa.
     *
     * Catatan: pesan ini ditampilkan pada alur pembayaran di front-end
     * (belum dibuat) dan pada redirect ke "/" saat ini.
     */
    public function pesanKadaluarsa(Order $order): string
    {
        if ($order->tanggal_bayar !== null) {
            return 'File Anda sudah dihapus dari server karena pembayaran tidak diselesaikan dalam '
                . self::BATAS_BAYAR_MENIT
                . ' menit setelah menekan "Bayar sekarang". Silakan upload ulang file Anda.';
        }

        return 'File Anda sudah dihapus dari server karena lebih dari '
            . self::BATAS_UPLOAD_MENIT
            . ' menit (1 jam) belum dibayar. Silakan upload ulang file Anda.';
    }

    /**
     * Tandai order menjadi gagal lalu hapus file uploadnya dari server.
     * Return true kalau order benar-benar diklaim (belum diproses pihak lain).
     *
     * Catatan: dipakai alur pembayaran front-end (belum dibuat) dan saat ini
     * dipanggil dari "/", formulir terjadwal, bayarSekarang(), dan dashboard.
     */
    public function kadaluarsakan(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            // Klaim atomik: hanya order yang memang sudah lewat batas yang diubah,
            // supaya file tidak terhapus dua kali.
            $query = Order::where('id', $order->id)
                ->where('status_pembayaran', 'pending');

            if ($order->tanggal_bayar !== null) {
                $query->where('tanggal_bayar', '<=', now()->subMinutes(self::BATAS_BAYAR_MENIT));
            } else {
                $query->whereNull('tanggal_bayar')
                    ->where('tanggal_pesan', '<=', now()->subMinutes(self::BATAS_UPLOAD_MENIT));
            }

            if ($query->update(['status_pembayaran' => 'failed']) === 0) {
                return false;
            }

            $this->hapusFile($order->id);

            return true;
        });
    }

    /**
     * Sapu semua order pending yang sudah lewat batas waktu.
     * Dipanggil saat halaman order di dashboard dibuka.
     *
     * Catatan: juga disiapkan untuk alur pembayaran di front-end (belum dibuat),
     * misalnya pengecekan berkala saat halaman pembayaran aktif.
     *
     * @return int jumlah order yang dikadaluarsakan
     */
    public function kadaluarsakanSemua(): int
    {
        $batasUpload = now()->subMinutes(self::BATAS_UPLOAD_MENIT);
        $batasBayar = now()->subMinutes(self::BATAS_BAYAR_MENIT);

        $orderIds = Order::query()
            ->where('status_pembayaran', 'pending')
            ->where(function ($query) use ($batasUpload, $batasBayar) {
                $query->where(function ($q) use ($batasUpload) {
                    $q->whereNull('tanggal_bayar')
                        ->where('tanggal_pesan', '<=', $batasUpload);
                })->orWhere(function ($q) use ($batasBayar) {
                    $q->whereNotNull('tanggal_bayar')
                        ->where('tanggal_bayar', '<=', $batasBayar);
                });
            })
            ->pluck('id');

        $jumlah = 0;

        foreach ($orderIds as $orderId) {
            $order = Order::find($orderId);

            if ($order && $this->kadaluarsakan($order)) {
                $jumlah++;
            }
        }

        return $jumlah;
    }

    /**
     * Catat waktu user menekan "Bayar sekarang" sebagai awal hitung mundur
     * pembayaran (BATAS_BAYAR_MENIT).
     *
     * Catatan: dipanggil dari bayarSekarang() yang akan dipakai front-end
     * pembayaran (belum dibuat).
     */
    public function tandaiBayar(Order $order): void
    {
        $sekarang = now();

        Order::where('id', $order->id)->update(['tanggal_bayar' => $sekarang]);

        // Sinkronkan model di memori supaya pengecekan berikutnya memakai nilai baru.
        $order->tanggal_bayar = $sekarang;
    }

    /**
     * Hapus file upload milik order dari storage beserta relasinya,
     * tanpa menghapus baris order-nya.
     *
     * Catatan: dipakai aksi hapus order, kedaluwarsa pembayaran (front-end
     * pembayaran belum dibuat), dan pembersihan di dashboard.
     */
    public function hapusFile(string $orderId): void
    {
        // Hapus antrean milik order ini lebih dulu.
        FileQueue::where('order_id', $orderId)->delete();

        foreach (File::where('order_id', $orderId)->get() as $file) {
            if ($file->path) {
                Storage::disk('local')->delete($file->path);
            }

            // Layanan terpilih memakai onDelete set null; hapus eksplisit.
            LayananTerpilih::where('files_id', $file->id)->delete();

            // Menghapus file ikut menghapus configuration (cascade).
            $file->delete();
        }
    }

    /**
     * Cari order dari id hex (32 karakter) yang disimpan di session.
     * Null kalau id tidak valid atau order sudah tidak ada.
     *
     * Catatan: dipakai alur pembayaran di front-end (belum dibuat) dan
     * pengecekan session terjadwal saat ini.
     */
    public function cariDariHex(?string $orderIdHex): ?Order
    {
        if ($orderIdHex === null || strlen($orderIdHex) !== 32 || !ctype_xdigit($orderIdHex)) {
            return null;
        }

        return Order::find(hex2bin($orderIdHex));
    }
}
