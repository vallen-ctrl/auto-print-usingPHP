<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cara mencetak order terjadwal:
     *  - otomatis : file dikirim ke komputer toko dan dicetak saat jadwal tiba
     *  - tukang   : file tetap di server, menunggu admin/tukang memprosesnya
     *
     * Alur kilat tidak memakai kolom ini (event-nya ditentukan oleh
     * files.layanan: kilat-langsung = print, kilat-edit = download).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('mode_cetak', 20)->default('otomatis')->after('metode_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('mode_cetak');
        });
    }
};
