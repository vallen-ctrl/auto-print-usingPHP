<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Order;
use App\Service\KedaluwarsaOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Beranda sekaligus penentu langkah user berikutnya.
 *
 * Alur order tidak lagi disimpan di session. Setiap order menyimpan
 * orders.browser_session (Laravel session id) sejak file diupload, jadi saat
 * user membuka "/" lagi kita bisa melihat di database sampai mana ordernya:
 *
 *  - belum submit formulir  -> kembali ke formulir terjadwal
 *  - sudah submit formulir  -> kembali ke halaman konfirmasi pembayaran
 *  - bayar di kasir         -> halaman konfirmasi (keadaan menunggu kasir)
 *  - pembayaran online      -> halaman status pembayaran
 *  - lunas / gagal / batal  -> alur selesai, tampilkan beranda
 */
class BerandaController extends Controller
{
    public function __construct(private readonly KedaluwarsaOrder $kedaluwarsa)
    {
    }

    public function __invoke(Request $request)
    {
        $order = Order::where('browser_session', $request->session()->getId())
            ->orderByDesc('tanggal_pesan')
            ->first();

        if ($order === null) {
            return view('beranda');
        }

        $orderHex = bin2hex($order->id);

        // Order yang sudah lewat batas waktu: file dihapus, penanda sesi
        // dilepas, lalu beranda menampilkan pesan dari redirect ini.
        if ($this->kedaluwarsa->sudahKadaluarsa($order)) {
            $pesan = $this->kedaluwarsa->pesanKadaluarsa($order);
            $this->kedaluwarsa->kadaluarsakan($order);
            $this->lupakanOrder($request, $order);

            return redirect('/')->with('error', $pesan);
        }

        switch ($order->status_pembayaran) {
            // Lunas: alur selesai, jangan mengarahkan user ke halaman lama.
            case 'paid':
                $this->lupakanOrder($request, $order);

                return view('beranda');

            // Pembayaran online sedang diverifikasi Midtrans.
            case 'processing':
                if ($order->metode_pembayaran === 'kasir') {
                    $this->pulihkanSession($request, $order, 'konfirmasi');

                    return redirect()->route('order.konfirmasi', ['order_id' => $orderHex]);
                }

                return redirect()->route('struk.render', ['id' => $orderHex]);

            case 'failed':
            case 'refunded':
                $this->lupakanOrder($request, $order);

                return view('beranda');

            case 'pending':
                // Formulir sudah disubmit (total sudah dihitung): user
                // melanjutkan di halaman pemilihan metode pembayaran.
                if ((int) $order->total_pembayaran > 0) {
                    $this->pulihkanSession($request, $order, 'konfirmasi');

                    return redirect()->route('order.konfirmasi', ['order_id' => $orderHex]);
                }

                // Baru upload file: lanjutkan pengisian formulir.
                $this->pulihkanSession($request, $order, 'formulir.terjadwal');

                return redirect()->route('formulir.terjadwal', ['order' => $orderHex]);

            default:
                $this->lupakanOrder($request, $order);

                return view('beranda');
        }
    }

    /**
     * Bangun ulang isi session "terjadwal_order" dari database, supaya
     * formulir/pembayaran tetap bisa dilanjutkan setelah session hilang.
     */
    private function pulihkanSession(Request $request, Order $order, string $where): void
    {
        $files = File::where('order_id', $order->id)
            ->get()
            ->mapWithKeys(function (File $file) {
                $fileHex = bin2hex($file->id);

                return [$fileHex => [
                    'id' => $fileHex,
                    'name' => $file->name,
                    'total_pages' => $file->total_pages,
                    'localPath' => Storage::disk('local')->path($file->path),
                ]];
            })
            ->all();

        $sessionId = $order->session_id
            ? bin2hex($order->session_id)
            : strtolower(bin2hex(random_bytes(16)));

        $request->session()->put(UploadTerjadwalController::SESSION_KEY, [
            'orderid' => bin2hex($order->id),
            'sessionid' => $sessionId,
            'files' => $files,
            'where' => $where,
        ]);
    }

    /**
     * Alur order sudah selesai: lepas session dan penanda di database.
     */
    private function lupakanOrder(Request $request, Order $order): void
    {
        $request->session()->forget(UploadTerjadwalController::SESSION_KEY);

        Order::where('id', $order->id)->update(['browser_session' => null]);
    }
}
