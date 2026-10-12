<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sessionid' => 'required|string',
            'name' => 'required|string',
            'no_wa' => 'required|string|min:10',
        ]);

        $noWa = str_replace('-', '', $validated['no_wa']);

        $user = User::create([
            'sessionid' => $validated['sessionid'],
            'name' => $validated['name'],
            'no_wa' => $noWa,
        ]);

        return response()->json([
            'sessionid' => $user->sessionid,
            'name' => $user->name,
            'no_wa' => $user->no_wa,
            'create_at' => $user->create_at,
        ], 201);
    }

    public function tiral(Request $request)
    {
        
        $data = DB::table("orders")
            ->join("files", 'orders.id', "=", "files.order_id")
            ->join("file_queue", 'file_queue.file_id', '=', "files.id")
            ->select(
                // Konversi ID bertipe BINARY(16) ke HEX
                DB::raw('LOWER(HEX(orders.id)) as order_id_hex'),
                DB::raw('LOWER(HEX(files.id)) as file_id_hex'),

                // ID file_queue bertipe INTEGER biasa, jadi langsung dipanggil
                'file_queue.id as file_queue_id',

                // Disesuaikan dengan nama kolom yang ada di gambar:
                'files.name as file_name',        // 'name' di tabel files
                'file_queue.status',              // 'status' di tabel file_queue
                'orders.tanggal_pesan'            // tanggal dari tabel orders
            )
            ->get();
        return response()->json($data);
    }
}
