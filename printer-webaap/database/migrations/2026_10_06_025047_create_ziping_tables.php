<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->string('sessionid')->primary();
            $table->string('name');
            $table->string('no_wa', 20);
            $table->timestamp('create_at')->useCurrent();
        });

        Schema::create('layanan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50);
            $table->text('deskripsi')->nullable();
            $table->bigInteger('harga')->default(0);
            $table->timestamp('tanggal_dibuat')->useCurrent();
        });

        Schema::create('toko', function (Blueprint $table) {
            $table->binary('id', 16)->primary();
            $table->string('name');
            $table->string('username', 50)->unique('uq_toko_username');
            $table->string('password');
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->timestamp('tanggal_update')->useCurrent()->useCurrentOnUpdate();
            $table->binary('token', 32)->nullable();
        });

        Schema::create('paper', function (Blueprint $table) {
            $table->id();
            $table->string('name', 8)->unique('uq_paper_name');
            $table->integer('width');
            $table->integer('height');
            $table->bigInteger('harga_color')->default(0);
            $table->bigInteger('harga_grayscale')->default(0);
        });

        Schema::create('computer', function (Blueprint $table) {
            $table->binary('id', 16)->primary();
            $table->string('name', 100);
            $table->binary('token', 16)->unique('uq_computer_token');
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->binary('toko_id', 16);
            $table->enum('status', ['not connected', 'shutdown', 'pending', 'connected', 'warning'])->default('not connected');
        });

        Schema::create('pengaturan_toko', function (Blueprint $table) {
            $table->id();
            $table->boolean('status_buka')->nullable();
            $table->bigInteger('biaya_admin')->nullable();
            $table->bigInteger('biaya_layanan')->nullable();
            $table->binary('id_toko', 16)->unique('uq_pengaturan_toko');
            $table->tinyInteger('lama_file_dihapus')->default(2);
        });

        Schema::create('jam_operasional', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('nomor_hari')->unique('uq_jam_Operasional');
            $table->string('hari', 10);
            $table->time('waktu_buka')->nullable();
            $table->time('waktu_tutup')->nullable();
            $table->boolean('apakah_dibuka');
            $table->unsignedBigInteger('id_pengaturan');
        });

        Schema::create('metode_pembayaran', function (Blueprint $table) {
            $table->binary('id', 16)->primary();
            $table->string('nama')->nullable();
            $table->text('webhook')->nullable();
            $table->text('token')->nullable();
            $table->text('image_path')->nullable();
            $table->unsignedBigInteger('id_pengaturan')->nullable();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->binary('id', 16)->primary();
            $table->timestamp('tanggal_pesan')->useCurrent();
            $table->dateTime('tanggal_ambil');
            $table->bigInteger('biaya_admin')->default(0);
            $table->bigInteger('total_pembayaran')->default(0);
            $table->bigInteger('total_dibayar')->default(0);
            $table->enum('status_pembayaran', ['pending', 'processing', 'success', 'fail'])->default('pending');
            $table->string('metode_pembayaran')->nullable();
            $table->binary('id_metode_pembayaran', 16)->nullable();
            $table->string('session_id')->nullable();
        });

        Schema::create('files', function (Blueprint $table) {
            $table->binary('id', 16)->primary();
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
            $table->string('diupload_oleh')->nullable();
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->smallInteger('total_pages')->nullable();
            $table->binary('order_id', 16)->nullable();
            $table->boolean('dihapus')->default(false);
            $table->bigInteger('subtotal')->default(0);
            $table->enum('layanan', ['kilat-edit', 'kilat-langsung', 'terjadwal']);
        });

        Schema::create('layanan_terpilih', function (Blueprint $table) {
            $table->id();
            $table->binary('files_id', 16)->nullable();
            $table->unsignedBigInteger('layanan_id')->nullable();
            $table->string('nama_layanan', 50);
        });

        Schema::create('configuration', function (Blueprint $table) {
            $table->id();
            $table->binary('file_id', 16);
            $table->enum('orientasi', ['potrait', 'landscape']);
            $table->enum('warna', ['berwarna', 'hitam putih']);
            $table->string('ukuran');
            $table->enum('kualitas', ['rendah', 'standar', 'tinggi']);
            $table->string('skala');
            $table->smallInteger('jumlah_cetak');
            $table->text('custom_halaman')->nullable();
            $table->text('catatan_tambahan')->nullable();
            $table->unsignedBigInteger('paper_id')->nullable();
            $table->smallInteger('pages_toprint')->default(0);
        });

        Schema::create('file_queue', function (Blueprint $table) {
            $table->id();
            $table->timestamp('tanggal_dibuat')->useCurrent();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'downloading'])->default('pending');
            $table->text('path');
            $table->binary('order_id', 16);
            $table->binary('file_id', 16)->nullable();
            $table->binary('computer_id', 16)->nullable();
        });

        // ---- Foreign keys (setelah semua tabel ada) ----

        Schema::table('computer', function (Blueprint $table) {
            $table->foreign('toko_id')->references('id')->on('toko')->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('pengaturan_toko', function (Blueprint $table) {
            $table->foreign('id_toko')->references('id')->on('toko')->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('jam_operasional', function (Blueprint $table) {
            $table->foreign('id_pengaturan')->references('id')->on('pengaturan_toko')->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('metode_pembayaran', function (Blueprint $table) {
            $table->foreign('id_pengaturan')->references('id')->on('pengaturan_toko')->onUpdate('cascade')->onDelete('set null');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('id_metode_pembayaran')->references('id')->on('metode_pembayaran')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('session_id')->references('sessionid')->on('user')->onUpdate('cascade')->onDelete('set null');
        });

        Schema::table('files', function (Blueprint $table) {
            $table->foreign('diupload_oleh')->references('sessionid')->on('user')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('layanan_terpilih', function (Blueprint $table) {
            $table->foreign('files_id')->references('id')->on('files')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('layanan_id')->references('id')->on('layanan')->onUpdate('cascade')->onDelete('set null');
        });

        Schema::table('configuration', function (Blueprint $table) {
            $table->foreign('file_id')->references('id')->on('files')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('paper_id')->references('id')->on('paper')->onUpdate('cascade')->onDelete('set null');
        });

        Schema::table('file_queue', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('file_id')->references('id')->on('files')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('computer_id')->references('id')->on('computer')->onUpdate('cascade')->onDelete('set null');
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