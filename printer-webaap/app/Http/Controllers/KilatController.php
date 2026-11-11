<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Titik masuk alur cetak kilat setelah QR komputer dipindai.
 *
 * Kedua mode memakai halaman upload yang sama; yang berbeda hanya nilai
 * files.layanan yang disimpan:
 *  - komputer  -> kilat-edit     (file masih bisa diedit di komputer toko)
 *  - handphone -> kilat-langsung (file langsung masuk antrean cetak)
 *
 * Penyimpanan file tetap memakai UploadTerjadwalController::store supaya
 * aturan upload (validasi PDF, batas 10 file, session, dsb) tidak diduplikasi.
 */
class KilatController extends Controller
{
    /**
     * Kunci session berisi id komputer (hex) yang QR-nya dipindai customer.
     * Diisi ComputerController::scanQr, lalu dipakai saat order dibuat agar
     * halaman konfirmasi tahu user harus mengedit file di komputer mana.
     */
    public const COMPUTER_SESSION_KEY = 'kilat_computer_id';

    /**
     * Mode yang didukung beserta layanan yang disimpan di tabel files.
     */
    public const MODE = [
        'komputer' => [
            'layanan' => 'kilat-edit',
            'label' => 'Komputer',
        ],
        'handphone' => [
            'layanan' => 'kilat-langsung',
            'label' => 'Handphone',
        ],
    ];

    /**
     * Halaman upload untuk salah satu mode kilat.
     */
    public function upload(Request $request, string $mode)
    {
        $config = self::MODE[$mode] ?? null;

        abort_if($config === null, 404);

        // Membuka halaman upload berarti memulai order baru: lepas alur yang
        // tersimpan di session dan penanda sesi order pending milik browser ini.
        $request->session()->forget(UploadTerjadwalController::SESSION_KEY);

        Order::where('browser_session', $request->session()->getId())
            ->where('status_pembayaran', 'pending')
            ->update(['browser_session' => null]);

        return view('kilat-upload', [
            'mode' => $mode,
            'layanan' => $config['layanan'],
            'label' => $config['label'],
        ]);
    }
}
