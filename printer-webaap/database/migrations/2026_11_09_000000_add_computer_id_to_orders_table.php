<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Komputer toko tempat customer mengedit file pada alur cetak kilat.
     *
     * Diisi dari QR komputer yang dipindai sebelum upload (lihat
     * ComputerController::scanQr). Halaman konfirmasi memakainya untuk
     * memberi tahu user harus ke komputer mana, bukan sekadar menunggu kasir.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->binary('computer_id', 16)->nullable()->after('browser_session');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('computer_id')
                ->references('id')->on('computer')
                ->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['computer_id']);
            $table->dropColumn('computer_id');
        });
    }
};
