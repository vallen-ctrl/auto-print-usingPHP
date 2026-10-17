<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\File;

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
use App\Models\User;
use App\Models\PengaturanToko;

class OrderController extends Controller
{
    public function get()
    {
        return view("Dashboard.selection.order");
    }

    public function setValidation(array $unsetArrayKey = [], array $changeValidation = [])
    {
        $validation = [
            'nama' => 'required|min:1|max:255',
            'no_wa' => 'required|min:10|max:14',
            'tanggal_ambil' => 'required|date_format:Y-m-d\TH:i:sP',
            // 'metode_pembayaran' => "required|string",
            'files' => 'required|array|min:1|max:10',

            'files.*.id' => 'required|string',
            'files.*.orientasi' => ['required', Rule::in(["portrait", "landscape"])],
            'files.*.warna' => ['required', Rule::in(['berwarna', 'hitam putih'])],
            'files.*.kualitas' => ['required', Rule::in(["rendah", "standar", "tinggi"])],
            //ingat bagian skala harus di hapus, rechek lagi bagian migration untuk skala dapat di hapus
            'files.*.jumlah' => 'required|numeric|min:1',
            'files.*.halaman' => ['nullable' , new CustomHalamanRule()],
            'files.*.catatan' => 'nullable',
            'files.*.id_paper' => 'required',
            'files.*.paket' => 'nullable|array|min:0',
            'files.*.paket.*' => 'numeric',

            ...$changeValidation
        ];

        $clear = array_filter($validation);

        foreach ($unsetArrayKey as $key) {
            unset($clear[$key]);
        }

        return $clear;
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

    public function createorder_terjadwal(Request $request)
    {
        $orderid = $request->validate([
            "orderid" => "required"
        ])["orderid"];

        // return redirect()->route("dashboard");

        $orderSession = $request->session()->get(UploadTerjadwalController::SESSION_KEY);
        
        if ($orderSession === null) return redirect()->back()->with("error", "hehe");
        // return redirect()->back()->with("error", json_encode($orderSession));
        if ($orderid !== $orderSession["orderid"]) {
            return redirect()->back()->with("error", "hehe");
        }

        $files = $orderSession["files"] ?? null;


        $validated = $request->validate($this->setValidation());

        try {
            DB::transaction(function () use (&$TotalSemua, &$validated, $files, $request, $orderSession) {
                $filesOnRequest = $validated["files"];

                $getBiaya = PengaturanToko::findOrFail(1);
                $TotalSemua = 0 + $getBiaya->biaya_admin + $getBiaya->biaya_layanan;

                if (!is_array($files) || count($files) !== count($filesOnRequest)) {
                    throw new \Exception("Jumlah file yang di upload dengan yang tersimpan berbeda");
                }

                foreach ($filesOnRequest as $index => $value) {
                    $subTotal = 0;
                    $filesId = $value["id"];
                    $filesOnSession = $orderSession["files"][$filesId] ?? null;

                    if ($filesOnSession["id"] == null) {
                        return redirect()->back()->with("error", "Id di filessession yang tersimpan tidak sama");
                    }

                    $totalPages = $filesOnSession["total_pages"];

                    $totalPagesToPrint = count($this->parseHalaman($value["halaman"] ?? "1-$totalPages"));

                    if ($totalPagesToPrint > $totalPages) {
                        return redirect()->back()->with("error", "Jumlah pages yang anda isi di costume halaman tidak sesuai dengan total pages yang akan anda kirim");
                    }

                    $paper = Paper::findOrFail($value["id_paper"]);

                    $banyakHalaman = $totalPagesToPrint * $value["jumlah"];

                    switch ($value["warna"]) {
                        case "berwarna":
                            $subTotal += ($paper->harga_color * $banyakHalaman);
                            break;
                        case "hitam putih":
                            $subTotal += ($paper->harga_grayscale * $banyakHalaman);
                            break;
                    }

                    foreach (($value["paket"] ?? []) as $idLayanan) {
                        $layanan = Layanan::findOrFail($idLayanan);
                        $namaLayanan = $layanan->nama;
                        $subTotal += $layanan->harga;
                        LayananTerpilih::create([
                            "files_id" => hex2bin($value["id"]),
                            "layanan_id" => $layanan->id,
                            "nama_layanan" => $namaLayanan
                        ]);
                    }

                    Configuration::create([
                            'file_id' => hex2bin($value["id"]),
                            'orientasi' => $value["orientasi"],
                            'warna' => $value["warna"],
                            'ukuran' => $paper->name,
                            'kualitas' => $value["kualitas"],
                            'jumlah_cetak' => $value["jumlah"],
                            'custom_halaman' => $value["halaman"],
                            'catatan_tambahan' => $value["catatan"],
                            'paper_id' => $value["id_paper"],
                            'pages_toprint' => $totalPagesToPrint,
                        ]);
                        

                    // Order::findOrFail()

                    $TotalSemua += $subTotal;
                    $validated["files"][$index]["subtotal"] = $subTotal;
                }

                User::create([
                    "sessionid" => $request->session()->getId(),
                    "name" => $validated["nama"],
                    "no_wa" => $validated["no_wa"]
                ]);
            });

            if ($request->expectsJson()) {
                return response()->json([
                    "message" => "succed create new order",
                    "data" => $validated
                ]);
            }
            return redirect()->back()->with("success", "hehe");
        } catch (\Exception $e) {
            return redirect()->back()->with("error", $e->getMessage());
        }
    }
}
