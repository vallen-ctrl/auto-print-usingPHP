<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Waktu user menekan "Bayar sekarang" (Snap token dibuat).
     * Dipakai sebagai awal hitung mundur batas pembayaran, terpisah dari
     * tanggal_pesan yang menjadi awal hitung mundur batas upload.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('tanggal_bayar')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tanggal_bayar');
        });
    }
};
