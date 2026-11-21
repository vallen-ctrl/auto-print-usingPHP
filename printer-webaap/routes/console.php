<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Worker pembayaran: gagalkan order yang lewat batas waktu, lalu hapus file
// order gagal dan file order lunas yang melewati pengaturan "lama disimpan".
// Pengecekan status ke Midtrans dilakukan manual dari halaman detail order.
Schedule::command('pembayaran:cek')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);
