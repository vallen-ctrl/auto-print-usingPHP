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

        $order = $request->query('order');

        return view('formulir-terjadwal', compact('layanan', 'order'));
    }
}
