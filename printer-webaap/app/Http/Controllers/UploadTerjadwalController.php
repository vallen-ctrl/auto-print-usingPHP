<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\File;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

        // Alur terjadwal tidak memakai komputer toko. Bersihkan juga komputer
        // sisa pemindaian QR kilat supaya tidak terbawa ke order berikutnya.
        $request->session()->forget(KilatController::COMPUTER_SESSION_KEY);

        // Membuka halaman upload berarti memulai order baru. Penanda sesi pada
        // order pending lama dilepas supaya beranda tidak melanjutkan order itu.
        Order::where('browser_session', $request->session()->getId())
            ->where('status_pembayaran', 'pending')
            ->update(['browser_session' => null]);

        return view('upload-terjadwal');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            // Halaman upload kilat mengirim layanan sesuai pilihan user:
            // kilat-edit (edit di komputer) atau kilat-langsung (edit di handphone).
            'layanan'          => ['nullable', Rule::in(['terjadwal', 'kilat-langsung', 'kilat-edit'])],
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

        $layanan = $data['layanan'] ?? 'terjadwal';

        // Komputer hasil pemindaian QR hanya berlaku untuk alur kilat.
        $computerId = $layanan === 'terjadwal' ? null : $this->computerDariSession($request);

        $orderId  = Str::uuid7()->getBytes();
        $orderHex = bin2hex($orderId);

        $files = [];

        DB::transaction(function () use ($request, $orderId, $orderHex, $layanan, $computerId, &$files) {
            Order::create([
                'id'            => $orderId,
                'tanggal_ambil' => now(),
                'status_pembayaran' => 'pending',
                // Penanda sesi browser: dipakai beranda untuk melanjutkan order
                // ini kalau user tidak sengaja keluar dari alur.
                'browser_session' => $request->session()->getId(),
                // Komputer tempat user akan mengedit file (kilat-edit).
                'computer_id'   => $computerId,
            ]);
            $pdf = new Parser();
            foreach ($request->file('files') as $file) {
                $path = $file->store("orders/{$orderHex}", 'local');
                $localPath = Storage::disk('local')->path($path);
                $pages = $pdf->parseFile($localPath)->getPages();
                $fileid = Str::uuid7()->getBytes();
                $filename = $file->getClientOriginalName();

                if($pages <= 0){
                    return response()->json([
                        "error" => true,
                        "message" => "cant parse many pages on this files -> $filename"
                    ], 304);
                }

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
                    // Alur kilat menyimpan kilat-edit / kilat-langsung supaya
                    // halaman konfirmasi tahu metode bayar yang tersedia.
                    'layanan'  => $layanan,
                ]);
            }
        });

        // Order id disimpan di session supaya order ini "melekat" selama user
        // tidak membuka halaman upload lagi.
        $request->session()->put(self::SESSION_KEY, [
            "orderid" => $orderHex,
            "sessionid" => strtolower(bin2hex(random_bytes(16))),
            "files" => $files,
            "where" => $layanan === 'terjadwal' ? "formulir.terjadwal" : "formulir.kilat"
        ]);

        // Data ringkas untuk browser: id file (hex), nama, dan jumlah halaman.
        // Harus berupa list (bukan objek ber-key hex), dan id sudah berbentuk hex.
        $payload = array_values(array_map(fn ($f) => [
            'id'          => $f['id'],
            'name'        => $f['name'],
            'total_pages' => $f['total_pages'],
        ], $files));

        // Alur kilat lanjut ke formulir kilat sesuai mode yang dipilih.
        $redirect = $layanan === 'terjadwal'
            ? route('formulir.terjadwal', ['order' => $orderHex])
            : route('kilat.formulir', [
                'mode'  => $layanan === 'kilat-edit' ? 'komputer' : 'handphone',
                'order' => $orderHex,
            ]);

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => $redirect,
                'callback' => $payload,
            ]);
        }

        return redirect($redirect)->with('callback', $payload);
    }

    /**
     * Id komputer (biner) dari hasil pemindaian QR komputer di session, atau
     * null kalau belum pernah memindai / komputernya sudah tidak ada.
     */
    private function computerDariSession(Request $request): ?string
    {
        $computerHex = $request->session()->get(KilatController::COMPUTER_SESSION_KEY);

        if (!is_string($computerHex) || strlen($computerHex) !== 32 || !ctype_xdigit($computerHex)) {
            return null;
        }

        return Computer::whereKey(hex2bin($computerHex))->value('id');
    }
}
