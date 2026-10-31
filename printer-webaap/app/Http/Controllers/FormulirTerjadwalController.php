<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
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

        // Sebelum formulir ditampilkan, pastikan order di session masih ada dan
        // belum lewat batas waktu (1 jam sejak upload, atau 20 menit sejak
        // menekan "Bayar sekarang"). Kalau sudah, file di server dihapus dan user
        // dikembalikan ke beranda dengan pesan untuk upload ulang.
        // Catatan: aturan waktu ini disiapkan juga untuk alur pembayaran di
        // front-end yang belum dibuat.
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

        return view('formulir-terjadwal', compact('layanan', 'order', 'paper'));
    }
}
