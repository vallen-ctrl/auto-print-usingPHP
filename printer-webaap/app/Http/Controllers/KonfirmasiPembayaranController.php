<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\File;
use App\Models\Order;
use App\Models\Paper;
use App\Models\PengaturanToko;
use App\Service\KedaluwarsaOrder;
use Illuminate\Http\Request;

/**
 * Halaman konfirmasi pembayaran: /order/konfirmasi/{order_id}
 *
 * Halaman ini adalah titik masuk pembayaran:
 *  - data order, file, dan biaya dibaca langsung dari database,
 *  - hitung mundur 20 menit (KedaluwarsaOrder::BATAS_BAYAR_MENIT) dimulai
 *    saat halaman pertama kali dibuka,
 *  - tombol CTA memanggil POST /order/token lalu membuka popup Midtrans Snap,
 *  - setelah pembayaran, user diarahkan ke halaman status /struk/{order_id}.
 */
class KonfirmasiPembayaranController extends Controller
{
    public function __construct(private readonly KedaluwarsaOrder $kedaluwarsa)
    {
    }

    public function show(Request $request, string $order_id)
    {
        $model = $this->kedaluwarsa->cariDariHex($order_id);

        if ($model === null) {
            return redirect('/')->with('error', 'Order tidak ditemukan. Silakan upload ulang file Anda.');
        }

        $orderHex = bin2hex($model->id);

        // Order yang sudah memilih bayar di kasir tetap boleh melihat halaman
        // ini dalam keadaan menunggu konfirmasi penjual (belum ada halaman
        // tujuan lain). Order lain yang sudah diproses langsung ke halaman
        // status pembayaran.
        $menungguKasir = $model->status_pembayaran === 'processing'
            && $model->metode_pembayaran === 'kasir';

        if ($menungguKasir) {
            $sisaDetik = 0;
        } else {
            if (in_array($model->status_pembayaran, ['paid', 'processing'], true)) {
                return redirect()->route('struk.render', ['id' => $orderHex]);
            }

            if ($model->status_pembayaran !== 'pending') {
                return redirect('/')->with(
                    'error',
                    'Order ini sudah tidak bisa dibayar karena statusnya "' . $model->status_pembayaran . '".'
                );
            }

            // Order yang sudah lewat batas waktu tidak boleh dibayar: file sudah
            // dihapus dari server, jadi user dikembalikan ke beranda.
            if ($this->kedaluwarsa->sudahKadaluarsa($model)) {
                $pesan = $this->kedaluwarsa->pesanKadaluarsa($model);
                $this->kedaluwarsa->kadaluarsakan($model);
                $request->session()->forget(UploadTerjadwalController::SESSION_KEY);

                return redirect('/')->with('error', $pesan);
            }

            // Hitung mundur 20 menit dimulai saat halaman konfirmasi dibuka.
            // tandaiBayar() tidak menimpa waktu yang sudah tercatat, jadi reload
            // halaman tidak menambah waktu bayar.
            $this->kedaluwarsa->tandaiBayar($model);
            $model->refresh();

            $batasBayar = $model->tanggal_bayar
                ? $model->tanggal_bayar->copy()->addMinutes(KedaluwarsaOrder::BATAS_BAYAR_MENIT)
                : null;

            $sisaDetik = $batasBayar ? max(0, $batasBayar->getTimestamp() - now()->getTimestamp()) : 0;

            if ($sisaDetik <= 0) {
                $pesan = $this->kedaluwarsa->pesanKadaluarsa($model);
                $this->kedaluwarsa->kadaluarsakan($model);

                return redirect('/')->with('error', $pesan);
            }
        }

        $files = File::where('order_id', $model->id)
            ->orderBy('tanggal_dibuat')
            ->get()
            ->map(function (File $file) {
                $config = Configuration::where('file_id', $file->id)->first();
                $paper = ($config && $config->paper_id) ? Paper::find($config->paper_id) : null;

                return [
                    'name'     => $file->name,
                    'pages'    => $file->total_pages ?? $config?->pages_toprint ?? 0,
                    'size'     => $this->formatUkuran((int) $file->size),
                    'warna'    => $config?->warna ?? '-',
                    'subtotal' => (int) $file->subtotal,
                    'layanan'  => $file->layanan,
                    'ukuran'   => $paper?->name,
                ];
            });

        // Satu order berasal dari satu alur, jadi layanan diambil dari file
        // pertama. kilat-edit membuat harga cetak belum pasti.
        $layanan = (string) ($files->first()['layanan'] ?? 'kilat-langsung');
        $hargaMenyesuaikan = $layanan === 'kilat-edit';

        $subtotal = (int) $files->sum('subtotal');

        // Biaya admin mengikuti pengaturan toko (bukan snapshot di orders),
        // supaya perubahan biaya di dashboard langsung terpakai.
        $pengaturan = PengaturanToko::find(1);
        $biayaAdmin = $pengaturan !== null
            ? (int) $pengaturan->biaya_admin
            : (int) $model->biaya_admin;

        $total = $subtotal + $biayaAdmin;

        // Biaya penanganan tidak disimpan terpisah; total saat ini selalu
        // subtotal + biaya admin.
        $biayaPenanganan = max(0, $total - $subtotal - $biayaAdmin);

        // Sinkronkan order supaya nominal Midtrans sama dengan yang tampil.
        // kilat-edit belum punya harga pasti, jadi order-nya tidak diubah.
        if (! $hargaMenyesuaikan
            && ((int) $model->biaya_admin !== $biayaAdmin || (int) $model->total_pembayaran !== $total)) {
            Order::where('id', $model->id)->update([
                'biaya_admin' => $biayaAdmin,
                'total_pembayaran' => $total,
            ]);
        }

        $order = [
            'id'                 => $orderHex,
            'layanan'            => $layanan,
            'label_ambil'        => $layanan === 'terjadwal' ? 'Terjadwal' : 'Ambil di tempat',
            'harga_menyesuaikan' => $hargaMenyesuaikan,
            'subtotal'           => $subtotal,
            'biaya_admin'        => $biayaAdmin,
            'biaya_penanganan'   => $biayaPenanganan,
            'total'              => $total,
            'sisa_detik'         => $sisaDetik,
            'tanggal_ambil'      => $model->tanggal_ambil?->translatedFormat('d M Y, H:i'),
            'menunggu_kasir'     => $menungguKasir,
            'metode_terpilih'    => $menungguKasir ? 'kasir' : null,
        ];

        $semuaMetode = [
            'qris' => [
                'id'          => 'qris',
                'nama'        => 'QRIS Kilat',
                'deskripsi'   => 'GoPay, OVO, Dana, ShopeePay, BNI Mobile',
                'rekomendasi' => true,
                'cta'         => 'Bayar Sekarang Via QRIS',
            ],
            'kasir' => [
                'id'          => 'kasir',
                'nama'        => 'Bayar Di Kasir',
                'deskripsi'   => 'Pembayaran langsung bisa dilakukan setelah cetak QR di komputer',
                'rekomendasi' => false,
                'cta'         => 'Bayar di Kasir',
            ],
        ];

        // kilat-langsung: QRIS atau kasir.
        // kilat-edit: hanya kasir, karena harga menyesuaikan setelah edit.
        // terjadwal: hanya QRIS.
        $metode = match ($layanan) {
            'kilat-langsung' => [$semuaMetode['qris'], $semuaMetode['kasir']],
            'kilat-edit'     => [$semuaMetode['kasir']],
            'terjadwal'      => [$semuaMetode['qris']],
            default          => [$semuaMetode['qris'], $semuaMetode['kasir']],
        };

        return view('order-konfirmasi', compact('order', 'files', 'metode'));
    }

    /**
     * Pilihan "Bayar di Kasir": order langsung dipindahkan ke status
     * "processing" dan pembayarannya dikonfirmasi langsung oleh penjual.
     *
     * Halaman/tujuan setelah ini belum dibuat; untuk sekarang cukup status
     * order yang berubah.
     */
    public function bayarDiKasir(Request $request, string $order_id)
    {
        $model = $this->kedaluwarsa->cariDariHex($order_id);

        if ($model === null) {
            return response()->json(['message' => 'Order tidak ditemukan.'], 404);
        }

        $session = $request->session()->get(UploadTerjadwalController::SESSION_KEY);

        if (!is_array($session) || ($session['orderid'] ?? null) !== $order_id) {
            return response()->json(
                ['message' => 'Sesi pembayaran tidak cocok. Silakan upload ulang file Anda.'],
                403
            );
        }

        // Pesan dibedakan per layanan: kilat-edit masih bisa diedit sehingga
        // harga cetaknya hanya perkiraan, sedangkan kilat-langsung sudah pasti.
        $layanan = (string) File::where('order_id', $model->id)->value('layanan');

        $pesanKasir = $layanan === 'kilat-edit'
            ? 'Silakan bayar di kasir. Harga cetak masih perkiraan karena file masih bisa diedit, jadi totalnya bisa berubah saat dihitung di kasir.'
            : 'Silakan bayar di kasir. Harga sudah sesuai rincian pesanan dan pembayaran dikonfirmasi langsung oleh penjual.';

        // Sudah dipilih sebelumnya: jangan ubah apa-apa lagi.
        if ($model->status_pembayaran === 'processing' && $model->metode_pembayaran === 'kasir') {
            return response()->json([
                'status' => 'processing',
                'message' => $pesanKasir,
            ]);
        }

        if ($this->kedaluwarsa->sudahKadaluarsa($model)) {
            $pesan = $this->kedaluwarsa->pesanKadaluarsa($model);
            $this->kedaluwarsa->kadaluarsakan($model);

            return response()->json(['message' => 'Tidak bisa memilih bayar di kasir. ' . $pesan], 410);
        }

        if ($model->status_pembayaran !== 'pending') {
            return response()->json([
                'message' => 'Order ini tidak bisa dibayar karena statusnya "' . $model->status_pembayaran . '".',
            ], 409);
        }

        // Klaim atomik: hanya order yang masih pending yang diubah.
        $diubah = Order::where('id', $model->id)
            ->where('status_pembayaran', 'pending')
            ->update([
                'status_pembayaran' => 'processing',
                'metode_pembayaran' => 'kasir',
                'tanggal_bayar' => $model->tanggal_bayar ?? now(),
            ]);

        if ($diubah === 0) {
            return response()->json(
                ['message' => 'Status order sudah berubah. Silakan muat ulang halaman.'],
                409
            );
        }

        return response()->json([
            'status' => 'processing',
            'message' => $pesanKasir,
        ]);
    }

    /**
     * Ubah ukuran file (byte) menjadi teks singkat, mis. "0,4 MB".
     */
    private function formatUkuran(int $bytes): string
    {
        if ($bytes <= 0) {
            return '-';
        }

        $satuan = ['B', 'KB', 'MB', 'GB'];
        $pangkat = min((int) floor(log($bytes, 1024)), count($satuan) - 1);
        $nilai = $bytes / (1024 ** $pangkat);

        return number_format($nilai, $nilai >= 10 || $pangkat === 0 ? 0 : 1, ',', '.')
            . ' ' . $satuan[$pangkat];
    }
}
