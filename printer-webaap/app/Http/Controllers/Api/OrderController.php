<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Rules\SkalaRule;
use App\Models\Paper;
use App\Models\Layanan;
use App\Models\LayananTerpilih;
use App\Models\MetodePembayaran;
use App\Rules\CustomHalamanRule;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\PengaturanToko;

class OrderController extends Controller
{
    private function parseHalaman(string $input): array
    {
        $result = [];
        foreach (explode(',', $input) as $part) {
            if (str_contains($part, '-')) {
                [$start, $end] = explode('-', $part);
                $result = array_merge($result, range((int) $start, (int) $end));
            } else {
                $result[] = (int) $part;
            }
        }
        return array_unique($result);
    }

    public function store(Request $request)
    {
        $orderid = request()->validate([
            "orderid" => "required"
        ])["orderid"];

        $orderIdSession = Cache::get("client:$orderid", null);
        if ($orderIdSession === null) return response()->json([
            "message" => "your session is expierd, please reupload the file again",
            "eror" => true
        ], 401);

        $validated = $request->validate([
            'nama' => 'required|min:1|max:255',
            'no_wa' => 'required|min:10|max:14',
            'tanggal_ambil' => 'required|date_format:Y-m-d\TH:i:sP',
            'metode_pembayaran' => "required|string",
            'files' => 'required|array|min:1|max:10',

            'files.*.id' => 'required|string',
            'files.*.orientasi' => ['required', Rule::in(["portrait", "landscape"])],
            'files.*.warna' => ['required', Rule::in(['berwarna', 'hitam putih'])],
            'files.*.kualitas' => ['required', Rule::in(["rendah", "standar", "tinggi"])],
            'files.*.skala' => [
                'required',
                'string',
                new SkalaRule()
            ],
            'files.*.jumlah_cetak' => 'required|numeric|min:1',
            'files.*.custom_halaman' => ['required', new CustomHalamanRule()],
            'files.*.catatan_tambahan' => 'required',
            'files.*.paper_id' => 'required',
            'files.*.layanan_id' => 'required|array|min:1',
            'files.*.layanan_id.*' => 'numeric'
        ]);

        if (count($validated["files"]) !== count($orderIdSession["files"])) {
            return response()->json([
                "message" => "Uploaded file count does not match the expected number of files.",
                "error" => true
            ], 400);
        }


        DB::transaction(function () use (&$validated, $orderIdSession, $orderid) {
            $userSession = bin2hex(random_bytes(8));
            $totalHarga = 0;
            User::create([
                "sessionid" => $userSession,
                "name" => $validated["nama"],
                "no_wa" => $validated["no_wa"]
            ]);
            $getBiaya = PengaturanToko::findOrFail(1);
            foreach ($validated["files"] as $index => $value) {
                $subTotal = 0;
                $filesId = $value["id"];
                $filesOnSession = $orderIdSession["files"][$index]["id"];

                if ($filesId !== $filesOnSession) {
                    return response()->json([
                        "message" => "File ID does not match the stored ID",
                        "error" => true
                    ], 400);
                };

                $paper = Paper::findOrFail($value["paper_id"]);
                $parseBanyakHalaman = $this->parseHalaman($value["custom_halaman"]);
                $jumlahCetak = $value["jumlah_cetak"];
                $banyakhalaman = count($parseBanyakHalaman) * $jumlahCetak;

                switch ($value["warna"]) {
                    case "berwarna":
                        $subTotal += ($paper->harga_color * $banyakhalaman);
                        break;
                    case "hitam putih":
                        $subTotal += ($paper->harga_grayscale * $banyakhalaman);
                        break;
                }

                foreach ($value["layanan_id"] as $idLayanan) {
                    $layanan = Layanan::findOrFail($idLayanan);
                    $namaLayanan = $layanan->nama;
                    $subTotal += $layanan->harga;
                    LayananTerpilih::create([
                        "files_id" => hex2bin($value["id"]),
                        "layanan_id" => $layanan->id,
                        "nama_layanan" => $namaLayanan
                    ]);
                }

                $validated["files"][$index]["pages_toprint"] = $banyakhalaman;
                $validated["files"][$index]["subtotal"] = $subTotal;
                $totalHarga += $subTotal;
            }

            $getMetodePembayaranName = MetodePembayaran::findOrFail(hex2bin($validated["metode_pembayaran"]))->nama;
            Order::create([
                "id" => hex2bin($orderid),
                "tanggal_ambil" => $validated["tanggal_ambil"],
                "biaya_admin" => $getBiaya->biaya_admin,
                "total_pembayaran" => $totalHarga,
                "total_dibayar" => 0,
                "status_pembayaran" => "pending",
                "metode_pembayaran" => $getMetodePembayaranName,
                "id_metode_pembayaran" => hex2bin($validated["metode_pembayaran"]),
                "session_id" => $userSession,
            ]);

            foreach ($validated["files"] as $index => $value) {
                $files = File::findOrFail(hex2bin($value["id"]));
                $files->update([
                    "order_id" => hex2bin($orderid)
                ]);
            }
            $validated["total_harga"] = $totalHarga;
        });

        return response()->json(['res' => $validated], 201);
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
