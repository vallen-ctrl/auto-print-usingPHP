<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Worker pembayaran: cek status Midtrans, gagalkan order yang lewat batas
// waktu, dan hapus file sesuai pengaturan "lama disimpan".
// Tiap 5 menit sudah cukup: konfirmasi pembayaran normal ditangani halaman
// struk, worker hanya jaring pengaman kalau browser customer ditutup.
Schedule::command('pembayaran:cek')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);
