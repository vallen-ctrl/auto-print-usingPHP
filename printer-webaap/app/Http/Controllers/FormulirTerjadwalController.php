<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Layanan;
use App\Models\Order;
use App\Models\Paper;
use App\Service\KedaluwarsaOrder;
use Illuminate\Http\Request;

class FormulirTerjadwalController extends Controller
{
    public function __construct(private readonly KedaluwarsaOrder $kedaluwarsa)
    {
    }

    public function get(Request $request)
    {
        $order = $request->session()->get(UploadTerjadwalController::SESSION_KEY);
        $orderModel = null;

        // Sebelum formulir ditampilkan, pastikan order di session masih ada dan
        // belum lewat batas waktu (1 jam sejak upload, atau 20 menit sejak
        // menekan "Bayar sekarang"). Kalau sudah, file di server dihapus dan user
        // dikembalikan ke beranda dengan pesan untuk upload ulang.
        if ($order) {
            $orderModel = $this->kedaluwarsa->cariDariHex($order["orderid"] ?? null);

            if ($orderModel === null) {
                $request->session()->forget(UploadTerjadwalController::SESSION_KEY);

                return redirect('/')->with('error', 'Order tidak ditemukan. Silakan upload ulang file Anda.');
            }

            if ($this->kedaluwarsa->sudahKadaluarsa($orderModel)) {
                $pesan = $this->kedaluwarsa->pesanKadaluarsa($orderModel);
                $this->kedaluwarsa->kadaluarsakan($orderModel);
                $request->session()->forget(UploadTerjadwalController::SESSION_KEY);

                return redirect('/')->with('error', $pesan);
            }

            // Order id disimpan/segarkan di session supaya order ini tetap
            // melekat selama user belum membuka halaman upload.
            $request->session()->put(UploadTerjadwalController::SESSION_KEY, $order);
        }

        $layanan = Layanan::where('aktif', true)
            ->orderBy('id')
            ->get();

        $paper = Paper::where("aktif", 1)->get();

        $pesanFormulir = $this->pesanFormulir($orderModel, $paper);

        return view('formulir-terjadwal', compact('layanan', 'order', 'paper', 'pesanFormulir'));
    }

    /**
     * Pesan yang ditampilkan saat formulir dibuka kalau datanya tidak siap:
     * jumlah halaman file tidak terbaca, atau subtotal cetak masih Rp. 0.
     */
    private function pesanFormulir(?Order $order, $paper): ?string
    {
        if ($order === null) {
            return 'Sesi formulir tidak ditemukan. Silakan upload ulang file Anda.';
        }

        $files = File::where('order_id', $order->id)->get();

        if ($files->isEmpty()) {
            return 'File pesanan tidak ditemukan di server. Silakan upload ulang file Anda.';
        }

        $tidakTerbaca = $files->first(
            fn (File $file) => (int) $file->total_pages <= 0
        );

        if ($tidakTerbaca !== null) {
            return 'Jumlah halaman file "' . $tidakTerbaca->name . '" tidak terbaca. '
                . 'Silakan upload ulang file tersebut.';
        }

        if ($paper->isEmpty()) {
            return 'Harga kertas belum diatur, jadi subtotal cetak masih Rp. 0. Silakan hubungi admin toko.';
        }

        // Kalau semua harga kertas 0, subtotal tidak mungkin lebih dari 0.
        $hargaTertinggi = (int) $paper->max(
            fn ($kertas) => max((int) $kertas->harga_color, (int) $kertas->harga_grayscale)
        );

        if ($hargaTertinggi <= 0) {
            return 'Harga kertas masih Rp. 0, jadi subtotal cetak tidak bisa dihitung. Silakan hubungi admin toko.';
        }

        return null;
    }
}
