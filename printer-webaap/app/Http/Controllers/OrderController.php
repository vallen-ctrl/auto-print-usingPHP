<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function get()
    {
        return view("Dashboard.selection.order");
    }

    public function getorderData(Request $request)
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
    public function detail(string $id)
    {
        return view("Dashboard.selection.order-detail", compact("id"));
    }
}
