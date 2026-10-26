<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Toko + jam operasional 7 hari (08:00-17:00, semua hari dibuka) untuk test jadwal.
 * $ubah[nomor_hari] menimpa isi baris hari tertentu.
 */
function tokoDenganJadwal(array $ubah = [], ?bool $statusBuka = true): \App\Models\PengaturanToko
{
    $pengaturan = \App\Models\PengaturanToko::factory()->create([
        'id' => 1,
        'status_buka' => $statusBuka,
    ]);

    for ($nomor = 1; $nomor <= 7; $nomor++) {
        \App\Models\JamOperasional::create(array_merge([
            'nomor_hari' => $nomor,
            'hari' => \App\Service\JadwalToko::NAMA_HARI[$nomor],
            'waktu_buka' => '08:00:00',
            'waktu_tutup' => '17:00:00',
            'apakah_dibuka' => true,
            'id_pengaturan' => $pengaturan->id,
        ], $ubah[$nomor] ?? []));
    }

    return $pengaturan;
}

/** Tanggal contoh: Kamis, 29 Oktober 2026. */
function hariKamis(): \Carbon\Carbon
{
    return \Carbon\Carbon::parse('2026-10-29 10:00:00', '+07:00');
}
