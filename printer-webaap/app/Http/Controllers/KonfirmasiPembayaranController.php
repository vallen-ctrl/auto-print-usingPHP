<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\File;
use App\Models\Paper;
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

        // Order yang sudah dibayar (atau sedang diverifikasi) langsung menuju
        // halaman status pembayaran, bukan halaman bayar lagi.
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
        $biayaAdmin = (int) $model->biaya_admin;
        $total = (int) $model->total_pembayaran;

        // Biaya penanganan tidak disimpan terpisah di orders; selisih total
        // dengan subtotal + biaya admin dianggap biaya penanganan.
        $biayaPenanganan = max(0, $total - $subtotal - $biayaAdmin);

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
