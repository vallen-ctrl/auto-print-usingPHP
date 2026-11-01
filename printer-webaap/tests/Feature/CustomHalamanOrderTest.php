<?php

use App\Http\Controllers\UploadTerjadwalController;
use App\Models\Configuration;
use App\Models\File;
use App\Models\Order;
use App\Models\Paper;
use App\Models\PengaturanToko;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Siapkan order + file 3 halaman untuk menguji custom halaman.
 *
 * @return array{0: string, 1: string, 2: array}
 */
function siapkanOrderHalaman(int $totalPages = 3): array
{
    tokoDenganJadwal();

    Paper::factory()->create([
        'id' => 1,
        'name' => 'A4',
        'aktif' => 1,
        'harga_color' => 1000,
        'harga_grayscale' => 500,
    ]);

    $orderId = Str::uuid()->getBytes();
    $fileId = Str::uuid()->getBytes();
    $orderHex = bin2hex($orderId);
    $fileHex = bin2hex($fileId);

    DB::table('orders')->insert([
        'id' => $orderId,
        'tanggal_pesan' => now(),
        'tanggal_ambil' => now()->addDay(),
        'biaya_admin' => 0,
        'total_pembayaran' => 0,
        'total_dibayar' => 0,
        'status_pembayaran' => 'pending',
    ]);

    DB::table('files')->insert([
        'id' => $fileId,
        'name' => 'contoh.pdf',
        'path' => 'orders/uji/contoh.pdf',
        'size' => 1024,
        'mime' => 'application/pdf',
        'total_pages' => $totalPages,
        'order_id' => $orderId,
        'dihapus' => false,
        'subtotal' => 0,
        'layanan' => 'terjadwal',
    ]);

    $session = [
        UploadTerjadwalController::SESSION_KEY => [
            'orderid' => $orderHex,
            'sessionid' => 'sesi-uji',
            'files' => [
                $fileHex => [
                    'id' => $fileHex,
                    'name' => 'contoh.pdf',
                    'total_pages' => $totalPages,
                ],
            ],
            'where' => 'formulir.terjadwal',
        ],
    ];

    return [$orderHex, $fileHex, $session];
}

/** Payload formulir dengan custom halaman tertentu. */
function payloadOrderHalaman(string $orderHex, string $fileHex, string $halaman): array
{
    return [
        'orderid' => $orderHex,
        'nama' => 'Pelanggan Uji',
        'no_wa' => '08123456789',
        'tanggal_ambil' => now('+07:00')->addDays(2)->setTime(10, 0)->toIso8601String(),
        'files' => [[
            'id' => $fileHex,
            'orientasi' => 'portrait',
            'warna' => 'hitam putih',
            'kualitas' => 'standar',
            'jumlah' => 1,
            'halaman' => $halaman,
            'catatan' => null,
            'id_paper' => 1,
            'paket' => [],
        ]],
    ];
}

test('custom halaman yang melebihi total halaman file ditolak', function () {
    [$orderHex, $fileHex, $session] = siapkanOrderHalaman(3);

    $response = $this->withSession($session)
        ->post(route('create.order'), payloadOrderHalaman($orderHex, $fileHex, '1,2,5'));

    $response->assertRedirect()->assertSessionHas('error');

    expect(session('error'))->toContain('melebihi total halaman file')
        ->and(Configuration::count())->toBe(0);
});

test('custom halaman yang masih di dalam total halaman diterima', function () {
    [$orderHex, $fileHex, $session] = siapkanOrderHalaman(3);

    $response = $this->withSession($session)
        ->post(route('create.order'), payloadOrderHalaman($orderHex, $fileHex, '1,2,3'));

    $response->assertRedirect(route('order.konfirmasi', ['order_id' => $orderHex]));

    expect(Configuration::first()->pages_toprint)->toBe(3);
});

test('range halaman yang melewati total halaman file ditolak', function () {
    [$orderHex, $fileHex, $session] = siapkanOrderHalaman(3);

    $response = $this->withSession($session)
        ->post(route('create.order'), payloadOrderHalaman($orderHex, $fileHex, '2-4'));

    $response->assertRedirect()->assertSessionHas('error');

    expect(session('error'))->toContain('melebihi total halaman file');
});

test('submit ulang memperbarui harga tanpa menggandakan data', function () {
    [$orderHex, $fileHex, $session] = siapkanOrderHalaman(3);

    $this->withSession($session)
        ->post(route('create.order'), payloadOrderHalaman($orderHex, $fileHex, '1,2,3'))
        ->assertRedirect(route('order.konfirmasi', ['order_id' => $orderHex]));

    // Edit: cetak halaman 1-2, jumlah 2 (harga grayscale 500 -> 500 x 2 x 2 = 2000).
    $payloadEdit = payloadOrderHalaman($orderHex, $fileHex, '1-2');
    $payloadEdit['files'][0]['jumlah'] = 2;

    $this->withSession($session)->post(route('create.order'), $payloadEdit)
        ->assertRedirect(route('order.konfirmasi', ['order_id' => $orderHex]));

    $admin = (int) PengaturanToko::findOrFail(1)->biaya_admin;
    $file = File::where('order_id', hex2bin($orderHex))->firstOrFail();
    $order = Order::findOrFail(hex2bin($orderHex));

    expect((int) $file->subtotal)->toBe(2000)
        ->and((int) $order->total_pembayaran)->toBe($admin + 2000)
        ->and(Configuration::first()->pages_toprint)->toBe(2)
        ->and(Configuration::count())->toBe(1)
        ->and(User::where('sessionid', 'sesi-uji')->count())->toBe(1);
});

test('formulir order pending yang sudah disubmit masih bisa dibuka untuk edit', function () {
    [$orderHex, $fileHex, $session] = siapkanOrderHalaman(3);

    $this->withSession($session)
        ->post(route('create.order'), payloadOrderHalaman($orderHex, $fileHex, '1,2,3'));

    // Timer pembayaran sempat berjalan saat halaman konfirmasi dibuka.
    DB::table('orders')->where('id', hex2bin($orderHex))->update(['tanggal_bayar' => now()]);

    $this->withSession($session)
        ->get(route('formulir.terjadwal', ['order' => $orderHex]))
        ->assertOk();

    // Mode edit: hitung mundur pembayaran direset, input file masih bisa diubah.
    expect(DB::table('orders')->where('id', hex2bin($orderHex))->value('tanggal_bayar'))->toBeNull();
});

test('formulir order yang sudah dibayar tidak bisa diedit', function () {
    [$orderHex, $fileHex, $session] = siapkanOrderHalaman(3);

    $this->withSession($session)
        ->post(route('create.order'), payloadOrderHalaman($orderHex, $fileHex, '1,2,3'));

    DB::table('orders')->where('id', hex2bin($orderHex))->update(['status_pembayaran' => 'paid']);

    $this->withSession($session)
        ->get(route('formulir.terjadwal', ['order' => $orderHex]))
        ->assertRedirect(route('order.konfirmasi', ['order_id' => $orderHex]));
});
