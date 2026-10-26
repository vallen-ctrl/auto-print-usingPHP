<?php

use App\Http\Controllers\UploadTerjadwalController;
use App\Models\Configuration;

/** Payload formulir terjadwal yang lolos validasi (1 file, tanpa custom halaman). */
function payloadOrderTerjadwal(string $tanggalAmbil, string $orderid = 'aabbccdd'): array
{
    return [
        'orderid' => $orderid,
        'nama' => 'Pelanggan Uji',
        'no_wa' => '08123456789',
        'tanggal_ambil' => $tanggalAmbil,
        'files' => [[
            'id' => '11223344556677889900aabbccddeeff',
            'orientasi' => 'portrait',
            'warna' => 'hitam putih',
            'kualitas' => 'standar',
            'jumlah' => 1,
            'halaman' => null,
            'catatan' => null,
            'id_paper' => 1,
            'paket' => [],
        ]],
    ];
}

/** Session order terjadwal yang cocok dengan payload di atas. */
function sessionOrderTerjadwal(string $orderid = 'aabbccdd'): array
{
    return [
        UploadTerjadwalController::SESSION_KEY => [
            'orderid' => $orderid,
            'sessionid' => 'sesi-uji',
            'files' => [
                '11223344556677889900aabbccddeeff' => [
                    'id' => '11223344556677889900aabbccddeeff',
                    'name' => 'contoh.pdf',
                    'total_pages' => 1,
                ],
            ],
            'where' => 'formulir.terjadwal',
        ],
    ];
}

test('createorder_terjadwal ditolak kalau toko sedang tutup', function () {
    tokoDenganJadwal(statusBuka: false);

    $tanggal = now('+07:00')->addDay()->setTime(10, 0)->toIso8601String();

    $response = $this->withSession(sessionOrderTerjadwal())
        ->post(route('create.order'), payloadOrderTerjadwal($tanggal));

    $response->assertRedirect()->assertSessionHas('error');

    expect(session('error'))->toContain('Toko sedang tutup')
        ->and(Configuration::count())->toBe(0);
});

test('createorder_terjadwal ditolak kalau pukul pengambilan di luar jam operasional', function () {
    tokoDenganJadwal();

    // 02:00 dini hari, di luar jam operasional 08:00-17:00 (tetap lolos aturan minimal H+5 jam)
    $tanggal = now('+07:00')->addDays(2)->setTime(2, 0)->toIso8601String();

    $response = $this->withSession(sessionOrderTerjadwal())
        ->post(route('create.order'), payloadOrderTerjadwal($tanggal));

    $response->assertRedirect()->assertSessionHas('error');

    expect(session('error'))->toContain('di luar jam operasional')
        ->and(Configuration::count())->toBe(0);
});

test('createorder_terjadwal tidak ditolak kalau jadwal sesuai', function () {
    tokoDenganJadwal();

    $tanggal = now('+07:00')->addDays(2)->setTime(10, 0)->toIso8601String();

    $response = $this->withSession(sessionOrderTerjadwal())
        ->post(route('create.order'), payloadOrderTerjadwal($tanggal));

    // lolos pengecekan jadwal, lalu gagal di proses order (data order uji belum lengkap),
    // jadi yang dipastikan di sini: pesannya bukan soal jadwal/toko.
    $response->assertRedirect();

    expect(session('error'))->not->toContain('Toko sedang tutup')
        ->and(session('error'))->not->toContain('jam operasional');
});

test('createorder_terjadwal tetap menolak order id yang tidak sesuai session', function () {
    tokoDenganJadwal();

    $tanggal = now('+07:00')->addDays(2)->setTime(10, 0)->toIso8601String();

    $response = $this->withSession(sessionOrderTerjadwal('aabbccdd'))
        ->post(route('create.order'), payloadOrderTerjadwal($tanggal, 'deadbeef'));

    $response->assertRedirect()->assertSessionHas('error');

    expect(session('error'))->toBe('cant find order id');
});
