<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Storage;

class UploadTerjadwalController extends Controller
{
    /**
     * Kunci session tempat order id terjadwal disimpan.
     */
    public const SESSION_KEY = 'terjadwal_order';

    /**
     * Tampilkan halaman upload. Membuka halaman ini dianggap memulai order
     * baru (termasuk saat user menekan tombol back dari halaman formulir),
     * jadi order id yang tersimpan di session dihapus.
     */
    public function show(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);

        return view('upload-terjadwal');
    }

    public function store(Request $request)
    {
        $request->validate([
            'files'            => 'required|array|min:1|max:10',
            'files.*'          => 'required|file|mimes:pdf',
        ], [
            'files.required'   => 'Minimal satu file PDF wajib diupload.',
            'files.array'      => 'Format upload file tidak valid.',
            'files.min'        => 'Minimal satu file PDF wajib diupload.',
            'files.max'        => 'Maksimal 10 file PDF dalam satu kali upload.',
            'files.*.required' => 'File tidak valid.',
            'files.*.file'     => 'File tidak valid.',
            'files.*.mimes'    => 'Setiap file wajib berformat PDF.',
        ]);

        $orderId  = Str::uuid7()->getBytes();
        $orderHex = bin2hex($orderId);

        $files = [];

        DB::transaction(function () use ($request, $orderId, $orderHex, &$files) {
            Order::create([
                'id'            => $orderId,
                'tanggal_ambil' => now(),
                'status_pembayaran' => 'pending',
            ]);
            $pdf = new Parser();
            foreach ($request->file('files') as $file) {
                $path = $file->store("orders/{$orderHex}", 'local');
                $localPath = Storage::disk('local')->path($path);
                $pages = $pdf->parseFile($localPath)->getPages();
                $fileid = Str::uuid7()->getBytes();
                $filename = $file->getClientOriginalName();

                $files[bin2hex($fileid)] = [
                    "id" => bin2hex($fileid),
                    "name" => $filename,
                    "total_pages" => count($pages),
                    "localPath" => $localPath
                ];

                File::create([
                    'id'       => $fileid,
                    'name'     => $filename,
                    'path'     => $path,
                    'total_pages' => count($pages),
                    'size'     => $file->getSize(),
                    'mime'     => 'application/pdf',
                    'order_id' => $orderId,
                    'layanan'  => 'terjadwal',
                ]);
            }
        });

        // Order id disimpan di session supaya order ini "melekat" selama user
        // tidak membuka halaman upload lagi.
        $request->session()->put(self::SESSION_KEY, [
            "orderid" => $orderHex,
            "sessionid" => strtolower(bin2hex(random_bytes(16))),
            "files" => $files,
            "where" => "formulir.terjadwal"
        ]);

        // Data ringkas untuk browser: id file (hex), nama, dan jumlah halaman.
        // Harus berupa list (bukan objek ber-key hex), dan id sudah berbentuk hex.
        $payload = array_values(array_map(fn ($f) => [
            'id'          => $f['id'],
            'name'        => $f['name'],
            'total_pages' => $f['total_pages'],
        ], $files));

        // Upload lewat fetch(): balas JSON supaya daftar file bisa disimpan di
        // browser (localStorage) sebelum pindah halaman.
        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route('formulir.terjadwal', ['order' => $orderHex]),
                'callback' => $payload,
            ]);
        }

        return redirect()->route('formulir.terjadwal', ['order' => $orderHex])->with('callback', $payload);
    }
}
