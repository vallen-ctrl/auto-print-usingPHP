<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use Illuminate\Http\Request;

class FormulirTerjadwalController extends Controller
{
    public function get(Request $request)
    {
        $layanan = Layanan::where('aktif', true)
            ->orderBy('id')
            ->get();

        $order = $request->query('order') ?? $request->session()->get(UploadTerjadwalController::SESSION_KEY);

        // Kalau order id dibawa di URL, simpan/segarkan di session supaya
        // order ini tetap melekat selama user belum membuka halaman upload.
        if ($order) {
            $request->session()->put(UploadTerjadwalController::SESSION_KEY, $order);
        }

        return view('formulir-terjadwal', compact('layanan', 'order'));
    }
}
