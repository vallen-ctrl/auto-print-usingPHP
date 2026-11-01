<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Metode pembayaran aplikasi hanya dua: qris dan kasir. Tabel master
     * metode_pembayaran tidak dipakai lagi, jadi referensinya di orders
     * dihapus.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['id_metode_pembayaran']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('id_metode_pembayaran');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('metode_pembayaran', ['qris', 'kasir'])->nullable()->change();
        });

        Schema::dropIfExists('metode_pembayaran');
    }

    public function down(): void
    {
        Schema::create('metode_pembayaran', function (Blueprint $table) {
            $table->binary('id', 16)->primary();
            $table->string('nama')->nullable();
            $table->text('webhook')->nullable();
            $table->text('token')->nullable();
            $table->text('image_path')->nullable();
            $table->unsignedBigInteger('id_pengaturan')->nullable();
        });

        Schema::table('metode_pembayaran', function (Blueprint $table) {
            $table->foreign('id_pengaturan')
                ->references('id')->on('pengaturan_toko')
                ->onUpdate('cascade')->onDelete('set null');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('metode_pembayaran')->nullable()->change();
            $table->binary('id_metode_pembayaran', 16)->nullable();
            $table->foreign('id_metode_pembayaran')
                ->references('id')->on('metode_pembayaran')
                ->onUpdate('cascade')->onDelete('set null');
        });
    }
};
