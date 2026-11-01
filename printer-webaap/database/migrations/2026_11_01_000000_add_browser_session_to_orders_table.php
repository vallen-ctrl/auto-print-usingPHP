<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda sesi browser (Laravel session id) yang sedang mengerjakan order.
     * Dipakai halaman beranda untuk melanjutkan order berdasarkan data di
     * database, bukan dari isi session.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('browser_session', 255)->nullable();
            $table->index('browser_session');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['browser_session']);
            $table->dropColumn('browser_session');
        });
    }
};
