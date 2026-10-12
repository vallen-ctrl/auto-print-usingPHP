<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        DB::transaction(function () use ($request, $orderId, $orderHex) {
            Order::create([
                'id'            => $orderId,
                'tanggal_ambil' => now(),
                'status_pembayaran' => 'pending',
            ]);

            foreach ($request->file('files') as $file) {
                $path = $file->store("orders/{$orderHex}", 'local');

                File::create([
                    'id'       => Str::uuid7()->getBytes(),
                    'name'     => $file->getClientOriginalName(),
                    'path'     => $path,
                    'size'     => $file->getSize(),
                    'mime'     => 'application/pdf',
                    'order_id' => $orderId,
                    'layanan'  => 'terjadwal',
                ]);
            }
        });

        // Order id disimpan di session supaya order ini "melekat" selama user
        // tidak membuka halaman upload lagi.
        $request->session()->put(self::SESSION_KEY, $orderHex);

        return redirect()->route('formulir.terjadwal', ['order' => $orderHex]);
    }
}
