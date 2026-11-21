<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Samakan data waktu order yang terlanjur ditulis dalam dua zona berbeda.
 *
 * Sebelum perbaikan ini, `orders.tanggal_pesan` diisi MySQL dengan waktu
 * server (WIB) sementara `tanggal_ambil` / `tanggal_bayar` diisi PHP yang
 * memakai zona UTC. Akibatnya kolom yang ditulis PHP tampil 7 jam lebih awal
 * dan aturan kedaluwarsa tidak pernah tercapai tepat waktu.
 *
 * Nilai lama yang tersimpan dalam UTC selalu ~7 jam lebih awal dari
 * `tanggal_pesan`, jadi hanya nilai seperti itu yang digeser. Nilai yang sudah
 * WIB (mis. tanggal_ambil hasil formulir) tidak disentuh. Migration ini aman
 * dijalankan ulang karena setelah digeser kondisinya tidak terpenuhi lagi.
 */
return new class extends Migration
{
    private const GESER_JAM = 7;

    public function up(): void
    {
        $orders = DB::table('orders')
            ->select('id', 'tanggal_pesan', 'tanggal_ambil', 'tanggal_bayar')
            ->orderBy('tanggal_pesan')
            ->get();

        foreach ($orders as $order) {
            $pesan = Carbon::parse($order->tanggal_pesan)->subHour();
            $perubahan = [];

            foreach (['tanggal_ambil', 'tanggal_bayar'] as $kolom) {
                if ($order->{$kolom} === null) {
                    continue;
                }

                $waktu = Carbon::parse($order->{$kolom});

                if ($waktu->lt($pesan)) {
                    $perubahan[$kolom] = $waktu->copy()
                        ->addHours(self::GESER_JAM)
                        ->toDateTimeString();
                }
            }

            if ($perubahan !== []) {
                DB::table('orders')->where('id', $order->id)->update($perubahan);
            }
        }
    }

    /**
     * Koreksi data satu arah: mengembalikan nilainya berisiko menggeser data
     * yang sudah benar, jadi down() sengaja dibiarkan kosong.
     */
    public function down(): void
    {
        //
    }
};
