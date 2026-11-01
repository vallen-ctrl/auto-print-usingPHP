<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Halaman konfirmasi pembayaran: /order/konfirmasi/{order_id}
 *
 * PENTING: data order di bawah masih data contoh (placeholder). Integrasi ke
 * database/API baru boleh dikerjakan setelah ada izin dari Vallen. Saat izin
 * sudah ada, ganti isi $order dan $files dengan pembacaan Order + File asli;
 * struktur data yang dikirim ke view tidak perlu berubah.
 */
class KonfirmasiPembayaranController extends Controller
{
    private const DAFTAR_LAYANAN = ['kilat-langsung', 'kilat-edit', 'terjadwal'];

    public function show(Request $request, string $order_id)
    {
        // Untuk pratinjau ketiga alur:
        //   /order/konfirmasi/{id}?layanan=kilat-langsung  (QRIS + kasir)
        //   /order/konfirmasi/{id}?layanan=kilat-edit      (kasir saja)
        //   /order/konfirmasi/{id}?layanan=terjadwal       (QRIS saja)
        $layanan = (string) $request->query('layanan', 'kilat-langsung');

        if (!in_array($layanan, self::DAFTAR_LAYANAN, true)) {
            $layanan = 'kilat-langsung';
        }

        // kilat-edit: file masih bisa diedit, jadi harga cetak belum pasti.
        $hargaMenyesuaikan = $layanan === 'kilat-edit';

        $order = [
            'id'                 => $order_id,
            'layanan'            => $layanan,
            'label_ambil'        => $layanan === 'terjadwal' ? 'Terjadwal' : 'Ambil di tempat',
            'harga_menyesuaikan' => $hargaMenyesuaikan,
            'subtotal'           => 110000,
            'biaya_admin'        => 2000,
            'biaya_penanganan'   => 1000,
            'total'              => 113000,
        ];

        $files = [
            [
                'name'     => 'Laporan Akhir.pdf',
                'pages'    => 50,
                'size'     => '0.4 MB',
                'warna'    => 'berwarna',
                'subtotal' => 55000,
            ],
            [
                'name'     => 'Lampiran Skripsi.pdf',
                'pages'    => 50,
                'size'     => '0.4 MB',
                'warna'    => 'berwarna',
                'subtotal' => 55000,
            ],
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
        };

        return view('order-konfirmasi', compact('order', 'files', 'metode'));
    }
}
