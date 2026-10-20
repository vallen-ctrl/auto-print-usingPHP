<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\File;
use App\Models\FileQueue;
use App\Models\Order;
use Illuminate\Validation\Rule;
use App\Models\Paper;
use App\Models\Layanan;
use App\Models\LayananTerpilih;
use App\Rules\CustomHalamanRule;
use App\Models\User;
use App\Models\PengaturanToko;
use Illuminate\Support\Facades\Cache;

\Midtrans\Config::$serverKey    = config('midtrans.server_key');
\Midtrans\Config::$isProduction = config('midtrans.is_production');
\Midtrans\Config::$isSanitized  = config('midtrans.is_sanitized');

class OrderController extends Controller
{
    public function get(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $query = Order::query()
            ->leftJoin('files', 'files.order_id', '=', 'orders.id')
            ->leftJoin('file_queue', 'file_queue.file_id', '=', 'files.id')
            ->select([
                'orders.id',
                'orders.tanggal_pesan',
                'orders.tanggal_ambil',
                'orders.total_pembayaran',
                'orders.total_dibayar',
                'orders.status_pembayaran',
                'orders.metode_pembayaran',
                DB::raw('LOWER(HEX(orders.id)) as id_hex'),
                DB::raw('GROUP_CONCAT(DISTINCT files.layanan) as layanan'),
                DB::raw('GROUP_CONCAT(DISTINCT file_queue.status) as status_file'),
                DB::raw('COUNT(DISTINCT files.id) as jumlah_file'),
            ])
            ->groupBy(
                'orders.id',
                'orders.tanggal_pesan',
                'orders.tanggal_ambil',
                'orders.total_pembayaran',
                'orders.total_dibayar',
                'orders.status_pembayaran',
                'orders.metode_pembayaran'
            );

        // Pencarian: order id (hex), status bayar, metode bayar, atau nama file.
        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($q) use ($like, $search) {
                $q->where('orders.status_pembayaran', 'like', $like)
                    ->orWhere('orders.metode_pembayaran', 'like', $like)
                    ->orWhereRaw('LOWER(HEX(orders.id)) LIKE ?', ['%' . strtolower($search) . '%'])
                    ->orWhere('files.name', 'like', $like);
            });
        }

        $orders = $query->orderByDesc('orders.tanggal_pesan')
            ->paginate(10)
            ->withQueryString();

        return view("Dashboard.selection.order", compact('orders', 'search'));
    }

    public function destroy(string $id)
    {
        $order = Order::findOrFail(hex2bin($id));

        DB::transaction(function () use ($order) {
            // Hapus antrean milik order ini lebih dulu.
            FileQueue::where('order_id', $order->id)->delete();

            foreach (File::where('order_id', $order->id)->get() as $file) {
                if ($file->path) {
                    Storage::disk('local')->delete($file->path);
                }

                // Layanan terpilih memakai onDelete set null; hapus eksplisit.
                LayananTerpilih::where('files_id', $file->id)->delete();

                // Menghapus file ikut menghapus configuration (cascade).
                $file->delete();
            }

            // Baris order dihapus setelah file-nya (files.order_id = restrict).
            $order->delete();
        });

        return redirect()->route('order.index')->with('success', 'Order berhasil dihapus.');
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
            'files.*.halaman' => ['nullable', new CustomHalamanRule()],
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
        $orderId = @hex2bin($id);
        abort_if($orderId === false, 404);

        $order = Order::findOrFail($orderId);

        $files = File::where('order_id', $orderId)
            ->orderBy('tanggal_dibuat')
            ->get()
            ->map(function ($file) {
                $config = Configuration::where('file_id', $file->id)->first();

                $layanan = LayananTerpilih::where('files_id', $file->id)
                    ->get()
                    ->map(function ($item) {
                        $harga = $item->layanan_id
                            ? optional(Layanan::find($item->layanan_id))->harga
                            : null;

                        return [
                            'nama'  => $item->nama_layanan,
                            'harga' => (int) $harga,
                        ];
                    });

                $paper = ($config && $config->paper_id) ? Paper::find($config->paper_id) : null;

                $hargaPerHalaman = null;

                if ($paper) {
                    $hargaPerHalaman = $config->warna === 'berwarna'
                        ? $paper->harga_color
                        : $paper->harga_grayscale;
                }

                return [
                    'file'              => $file,
                    'config'            => $config,
                    'layanan'           => $layanan,
                    'paper'             => $paper,
                    'harga_per_halaman' => $hargaPerHalaman,
                    'id_hex'            => bin2hex($file->id),
                ];
            });

        return view("Dashboard.selection.order-detail", compact('id', 'order', 'files'));
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

        $orderSession = $request->session()->get(UploadTerjadwalController::SESSION_KEY);

        if ($orderSession === null) return redirect()->back()->with("error", "Order sudah kadaluarsa, mohon dapat berpindah ke halaman upload kembali");

        if ($orderid !== $orderSession["orderid"]) {
            return redirect()->back()->with("error", "cant find order id");
        }

        $files = $orderSession["files"] ?? null;


        $validated = $request->validate($this->setValidation());

        try {
            DB::transaction(function () use (&$TotalSemua, &$validated, $files, $request, $orderSession) {
                $filesOnRequest = $validated["files"];

                $getBiaya = PengaturanToko::findOrFail(1);
                $TotalSemua = 0 + $getBiaya->biaya_admin;

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

                    File::findOrFail(hex2bin($value["id"]))
                        ->update([
                            "subtotal" => $subTotal,
                        ]);

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

                    $TotalSemua += $subTotal;
                    $validated["files"][$index]["subtotal"] = $subTotal;
                }

                User::create([
                    "sessionid" => $orderSession["sessionid"],
                    "name" => $validated["nama"],
                    "no_wa" => $validated["no_wa"]
                ]);

                Order::findOrFail(hex2bin($orderSession["orderid"]))
                    ->update([
                        "biaya_admin" => $getBiaya->biaya_admin,
                        "total_pembayaran" => $TotalSemua,
                        "total_dibayar" => 0,
                        "session_id" => $orderSession["sessionid"]
                    ]);
            });
            return redirect()->back()->with("success", "hehe");
        } catch (\Exception $e) {
            return redirect()->back()->with("error", $e->getMessage());
        }
    }

    public function bayarSekarang(Request $request)
    {
        $validated = $request->validate([
            "orderid" => 'required',
            // "id_metode_pembayaran" => "required"
        ]);

        $session = $request->session()->get(UploadTerjadwalController::SESSION_KEY, null);

        if ($session === null) {
            return redirect()->back()->with("error", "Sesi anda sudah habis.");
        }

        if ($session["orderid"] !== $validated["orderid"]) {
            return redirect()->back()->with("error", "tidak dapat mencari order id yang sesuai");
        }

        $order = Order::whereKey(hex2bin($validated["orderid"]))->first();

        $params = array(
            'transaction_details' => array(
                'order_id' => bin2hex($order->id),
                'gross_amount' => $order->total_pembayaran,
            ),
        );

        $snapToken = \Midtrans\Snap::getSnapToken($params);
        return response()->json([
            "token" => $snapToken,
            "messgae" => "sukes generated snap token"
        ]);
    }

    public function strukOrder(string $orderId){
        if(empty($orderId)){
            // redirect()->route()
            logger("Eror while get order");
        }
    }
}
