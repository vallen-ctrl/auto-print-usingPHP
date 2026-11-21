<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

test('aplikasi dan koneksi database memakai zona waktu Indonesia Barat', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(config('database.connections.mysql.timezone'))->toBe('+07:00')
        ->and(config('database.connections.mariadb.timezone'))->toBe('+07:00');
});

test('CURRENT_TIMESTAMP database sama dengan jam aplikasi', function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Pengecekan jam server hanya berlaku untuk MySQL/MariaDB.');
    }

    $jamDatabase = DB::selectOne('select CURRENT_TIMESTAMP as sekarang')->sekarang;

    expect(abs(now()->diffInMinutes(Carbon::parse($jamDatabase), false)))
        ->toBeLessThanOrEqual(1);
});
