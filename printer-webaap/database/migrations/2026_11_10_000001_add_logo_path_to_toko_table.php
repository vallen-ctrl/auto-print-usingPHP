<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Logo toko yang tampil di header halaman customer (menggantikan
     * placeholder "logo jasa"). Diunggah dari halaman Pengaturan.
     */
    public function up(): void
    {
        Schema::table('toko', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('toko', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
