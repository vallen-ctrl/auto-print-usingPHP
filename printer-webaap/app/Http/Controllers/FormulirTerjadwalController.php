<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Paper;
use Illuminate\Http\Request;

class FormulirTerjadwalController extends Controller
{
    public function get(Request $request)
    {
        $layanan = Layanan::where('aktif', true)
            ->orderBy('id')
            ->get();
        
        $paper = Paper::where("aktif", 1)->get();

        $order = $request->session()->get(UploadTerjadwalController::SESSION_KEY);

        // Kalau order id dibawa di URL, simpan/segarkan di session supaya
        // order ini tetap melekat selama user belum membuka halaman upload.
        if ($order) {
            $request->session()->put(UploadTerjadwalController::SESSION_KEY, $order);
        }

        return view('formulir-terjadwal', compact('layanan', 'order', 'paper'));
    }

    // public function createnewOrder(Request $request){
    //     $validate = $request->validate([])
    // }
}
