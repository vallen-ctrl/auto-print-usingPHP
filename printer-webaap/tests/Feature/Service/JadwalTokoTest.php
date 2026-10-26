<?php

use App\Models\JamOperasional;
use App\Models\PengaturanToko;
use App\Service\JadwalToko;

function cekJadwal(PengaturanToko $pengaturan, string $tanggalAmbil): ?string
{
    return (new JadwalToko())->cek($pengaturan, $tanggalAmbil);
}

test('menolak order saat status_buka toko mati', function () {
    $pengaturan = tokoDenganJadwal(statusBuka: false);

    $pesan = cekJadwal($pengaturan, '2026-10-29T10:00:00+07:00');

    expect($pesan)->toContain('Toko sedang tutup');
});

test('menerima order saat toko buka dan jam sesuai jam operasional', function () {
    $pengaturan = tokoDenganJadwal();
    $kamis = hariKamis();

    $pesan = cekJadwal($pengaturan, $kamis->toIso8601String());

    expect($pesan)->toBeNull();
});

test('menolak jam sebelum toko buka', function () {
    $pengaturan = tokoDenganJadwal();

    $pesan = cekJadwal($pengaturan, '2026-10-29T07:30:00+07:00');

    expect($pesan)->toContain('di luar jam operasional')
        ->and($pesan)->toContain('08:00 - 17:00');
});

test('menolak jam setelah toko tutup', function () {
    $pengaturan = tokoDenganJadwal();

    $pesan = cekJadwal($pengaturan, '2026-10-29T17:30:00+07:00');

    expect($pesan)->toContain('di luar jam operasional');
});

test('menerima tepat saat jam buka dan jam tutup', function () {
    $pengaturan = tokoDenganJadwal();

    expect(cekJadwal($pengaturan, '2026-10-29T08:00:00+07:00'))->toBeNull()
        ->and(cekJadwal($pengaturan, '2026-10-29T17:00:00+07:00'))->toBeNull();
});

test('menolak hari yang ditandai tidak dibuka', function () {
    $kamis = hariKamis();
    $pengaturan = tokoDenganJadwal([$kamis->dayOfWeekIso => ['apakah_dibuka' => false]]);

    $pesan = cekJadwal($pengaturan, $kamis->toIso8601String());

    expect($pesan)->toContain('Toko tutup pada hari Kamis');
});

test('menolak hari yang tidak punya baris jam operasional', function () {
    $kamis = hariKamis();
    $pengaturan = tokoDenganJadwal();

    JamOperasional::where('nomor_hari', $kamis->dayOfWeekIso)->delete();

    $pesan = cekJadwal($pengaturan, $kamis->toIso8601String());

    expect($pesan)->toContain('Toko tidak buka pada hari Kamis');
});

test('menerima jam operasional yang lewat tengah malam', function () {
    $kamis = hariKamis();
    $pengaturan = tokoDenganJadwal([
        $kamis->dayOfWeekIso => ['waktu_buka' => '20:00:00', 'waktu_tutup' => '02:00:00'],
    ]);

    expect(cekJadwal($pengaturan, '2026-10-29T23:00:00+07:00'))->toBeNull()
        ->and(cekJadwal($pengaturan, '2026-10-29T01:00:00+07:00'))->toBeNull()
        ->and(cekJadwal($pengaturan, '2026-10-29T03:00:00+07:00'))->toContain('di luar jam operasional');
});

test('memakai jam lokal dari offset yang dikirim browser', function () {
    $pengaturan = tokoDenganJadwal();

    // 08:30 WIB masih di dalam 08:00-17:00 (kalau salah dikonversi ke UTC jadi 01:30 -> ditolak)
    expect(cekJadwal($pengaturan, '2026-10-29T08:30:00+07:00'))->toBeNull()
        ->and(cekJadwal($pengaturan, '2026-10-29T07:30:00+07:00'))->toContain('di luar jam operasional');
});

test('hanya mengikuti status_buka kalau jam operasional belum diatur', function () {
    $pengaturan = PengaturanToko::factory()->create(['id' => 1, 'status_buka' => true]);

    expect(cekJadwal($pengaturan, '2026-10-29T03:00:00+07:00'))->toBeNull();
});

test('menolak tanggal yang formatnya tidak dikenali', function () {
    $pengaturan = tokoDenganJadwal();

    expect(cekJadwal($pengaturan, 'besok pagi'))->toContain('tidak dikenali');
});
