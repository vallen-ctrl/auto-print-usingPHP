<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Butuh Laravel 11+ (binary(..., fixed: true) -> BINARY(16))
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->string('sessionid', 255)->primary();
            $table->string('name', 255);
            $table->string('no_wa', 20);
            $table->timestamp('create_at')->useCurrent();
        });

        Schema::create('layanan', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nama', 50);
            $table->text('deskripsi')->nullable();
            $table->bigInteger('harga')->default(0);
            $table->timestamp('tanggal_dibuat')->useCurrent();
        });

        Schema::create('toko', function (Blueprint $table) {
            $table->binary('id', 16, true)->primary();
            $table->string('name', 255);
            $table->string('username', 50);
            $table->string('password', 255);
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->timestamp('tanggal_update')->useCurrent();
            $table->binary('token', 32, true)->nullable();

            $table->unique('username', 'uq_toko_username');
        });

        Schema::create('paper', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 8);
            $table->integer('width');
            $table->integer('height');
            $table->bigInteger('harga_color')->default(0);
            $table->bigInteger('harga_grayscale')->default(0);

            $table->unique('name', 'uq_paper_name');
        });

        Schema::create('computer', function (Blueprint $table) {
            $table->binary('id', 16, true)->primary();
            $table->string('name', 100);
            $table->binary('token', 16, true);
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->binary('toko_id', 16, true);
            $table->enum('status', ['not connected', 'shutdown', 'pending', 'connected', 'warning'])
                ->default('not connected');

            $table->unique('token', 'uq_computer_token');
        });

        Schema::create('pengaturan_toko', function (Blueprint $table) {
            $table->integer('id', true);
            $table->boolean('status_buka')->nullable();
            $table->bigInteger('biaya_admin')->nullable();
            $table->bigInteger('biaya_layanan')->nullable();
            $table->binary('id_toko', 16, true);
            $table->tinyInteger('lama_file_dihapus')->default(2);

            $table->unique('id_toko', 'uq_pengaturan_toko');
        });

        Schema::create('jam_operasional', function (Blueprint $table) {
            $table->integer('id', true);
            $table->tinyInteger('nomor_hari');
            $table->string('hari', 10);
            $table->time('waktu_buka')->nullable();
            $table->time('waktu_tutup')->nullable();
            $table->boolean('apakah_dibuka');
            $table->integer('id_pengaturan');

            $table->unique(['id_pengaturan', 'nomor_hari'], 'uq_jam_hari');
        });

        Schema::create('metode_pembayaran', function (Blueprint $table) {
            $table->binary('id', 16, true)->primary();
            $table->string('nama', 255)->nullable();
            $table->text('webhook')->nullable();
            $table->text('token')->nullable();
            $table->text('image_path')->nullable();
            $table->integer('id_pengaturan')->nullable();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->binary('id', 16, true)->primary();
            $table->timestamp('tanggal_pesan')->useCurrent();
            $table->dateTime('tanggal_ambil');
            $table->bigInteger('biaya_admin')->default(0);
            $table->bigInteger('total_pembayaran')->default(0);
            $table->bigInteger('total_dibayar')->default(0);
            $table->integer('pengaturan_id');
            $table->enum('status_pembayaran', ['pending', 'proccesing ', 'succed', 'fail']);
            $table->string('metode_pembayaran', 255)->nullable();
            $table->binary('id_metode_pembayaran', 16, true)->nullable();
            $table->string('session_id', 255);
        });

        Schema::create('files', function (Blueprint $table) {
            $table->binary('id', 16, true)->primary();
            $table->text('name');
            $table->text('path');
            $table->bigInteger('size');
            $table->enum('mime', [
                'application/pdf',
                'application/msword',
                'application/octet-stream',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
            ])->default('application/octet-stream');
            $table->string('diupload_oleh', 255)->nullable();
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->smallInteger('total_pages')->nullable();
            $table->binary('order_id', 16, true);
            $table->boolean('dihapus')->default(false);
            $table->bigInteger('subtotal')->default(0);
            $table->enum('layanan', ['kilat-edit', 'kilat-langsung', 'terjadwal']);
        });

        Schema::create('layanan_terpilih', function (Blueprint $table) {
            $table->integer('id', true);
            $table->binary('files_id', 16, true);
            $table->integer('layanan_id');
            $table->string('nama_layanan', 50);
        });

        Schema::create('configuration', function (Blueprint $table) {
            $table->integer('id', true);
            $table->binary('file_id', 16, true);
            $table->enum('orientasi', ['potrait', 'landscape']);
            $table->enum('warna', ['berwarna', 'hitam putih']);
            $table->string('ukuran', 255);
            $table->enum('kualitas', ['rendah', 'standar', 'tinggi']);
            $table->string('skala', 255);
            $table->smallInteger('jumlah_cetak');
            $table->text('custom_halaman');
            $table->text('catatan_tambahan');
            $table->integer('paper_id');
            $table->smallInteger('pages_toprint')->default(0);
        });

        Schema::create('file_queue', function (Blueprint $table) {
            $table->integer('id', true);
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->enum('status', ['pending', 'processing', 'complited', 'failed', 'downloading'])
                ->default('pending');
            $table->text('path');
            $table->binary('order_id', 16, true);
            $table->binary('file_id', 16, true);
            $table->binary('computer_id', 16, true);
        });

        // ---------- Foreign keys ----------
        // Kolom NOT NULL tidak boleh SET NULL di MySQL, jadi diganti CASCADE / RESTRICT.

        Schema::table('computer', function (Blueprint $table) {
            $table->foreign('toko_id')->references('id')->on('toko')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('pengaturan_toko', function (Blueprint $table) {
            $table->foreign('id_toko')->references('id')->on('toko')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('jam_operasional', function (Blueprint $table) {
            $table->foreign('id_pengaturan')->references('id')->on('pengaturan_toko')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('metode_pembayaran', function (Blueprint $table) {
            $table->foreign('id_pengaturan')->references('id')->on('pengaturan_toko')
                ->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('pengaturan_id')->references('id')->on('pengaturan_toko')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('id_metode_pembayaran')->references('id')->on('metode_pembayaran')
                ->cascadeOnUpdate()->nullOnDelete();
            // diubah: SET NULL -> RESTRICT
            $table->foreign('session_id')->references('sessionid')->on('user')
                ->noActionOnUpdate()->restrictOnDelete();
        });

        Schema::table('files', function (Blueprint $table) {
            $table->foreign('diupload_oleh')->references('sessionid')->on('user')
                ->cascadeOnUpdate()->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')
                ->noActionOnUpdate()->restrictOnDelete();
        });

        Schema::table('layanan_terpilih', function (Blueprint $table) {
            // diubah: SET NULL -> CASCADE
            $table->foreign('files_id')->references('id')->on('files')
                ->cascadeOnUpdate()->cascadeOnDelete();
            // diubah: SET NULL -> RESTRICT
            $table->foreign('layanan_id')->references('id')->on('layanan')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('configuration', function (Blueprint $table) {
            $table->foreign('file_id')->references('id')->on('files')
                ->cascadeOnUpdate()->cascadeOnDelete();
            // diubah: SET NULL -> RESTRICT
            $table->foreign('paper_id')->references('id')->on('paper')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('file_queue', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')
                ->cascadeOnUpdate()->cascadeOnDelete();
            // diubah: SET NULL -> CASCADE
            $table->foreign('file_id')->references('id')->on('files')
                ->cascadeOnUpdate()->cascadeOnDelete();
            // diubah: SET NULL -> CASCADE
            $table->foreign('computer_id')->references('id')->on('computer')
                ->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('file_queue');
        Schema::dropIfExists('configuration');
        Schema::dropIfExists('layanan_terpilih');
        Schema::dropIfExists('files');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('metode_pembayaran');
        Schema::dropIfExists('jam_operasional');
        Schema::dropIfExists('pengaturan_toko');
        Schema::dropIfExists('computer');
        Schema::dropIfExists('paper');
        Schema::dropIfExists('toko');
        Schema::dropIfExists('layanan');
        Schema::dropIfExists('user');

        Schema::enableForeignKeyConstraints();
    }
};
