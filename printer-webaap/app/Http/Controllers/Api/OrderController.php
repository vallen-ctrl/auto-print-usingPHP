<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_ambil' => 'required|date_format:Y-m-d\TH:i:sP',
            'biaya_admin' => 'required|integer',
            'total_pembayaran' => 'required|integer',
            'status_pembayaran' => 'required|string|in:pending,proccesing ,succed,fail',
            'session_id' => 'required|string',
        ]);

        $id = Str::uuid();
        
        $order = Order::create([
            'id' => $id->getBytes(),
            'tanggal_ambil' => $validated['tanggal_ambil'],
            'biaya_admin' => $validated['biaya_admin'],
            'total_pembayaran' => $validated['total_pembayaran'],
            'status_pembayaran' => $validated['status_pembayaran'],
            'session_id' => $validated['session_id'],
            'pengaturan_id' => 1,
        ]);

        return response()->json(['id' => $id->toString()], 201);
    }

    public function updateTotalDibayar(Request $request, string $id)
    {
        $validated = $request->validate([
            'total_dibayar' => 'required|integer|min:0',
        ]);

        $order = Order::findOrFail(hex2bin($id));
        $order->update([
            'total_dibayar' => $validated['total_dibayar'],
        ]);

        return response()->json($order);
    }

    public function updateMetodePembayaran(Request $request, string $id)
    {
        $validated = $request->validate([
            'metode_pembayaran' => 'required|string',
            'id_metode_pembayaran' => 'required|string',
        ]);

        $order = Order::findOrFail(hex2bin($id));
        $order->update([
            'metode_pembayaran' => $validated['metode_pembayaran'],
            'id_metode_pembayaran' => hex2bin($validated['id_metode_pembayaran']),
        ]);

        return response()->json($order);
    }

    public function updateStatusPembayaran(Request $request, string $id)
    {
        $validated = $request->validate([
            'status_pembayaran' => 'required|string|in:pending,proccesing ,succed,fail',
        ]);

        $order = Order::findOrFail(hex2bin($id));
        $order->update([
            'status_pembayaran' => $validated['status_pembayaran'],
        ]);

        return response()->json($order);
    }
}

