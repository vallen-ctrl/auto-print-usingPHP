<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Worker pembayaran: cek status Midtrans, gagalkan order yang lewat batas
// waktu, dan hapus file sesuai pengaturan "lama disimpan".
Schedule::command('pembayaran:cek')
    ->everyMinute()
    ->withoutOverlapping(10);
