<?php

use App\Models\Order;
use App\Models\Toko;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Sesi admin dashboard (dipakai VerifyTokenMiddleware). */
function sesiOrderQr(): array
{
    $token = random_bytes(16);
    $toko = Toko::factory()->create(['token' => $token]);

    Cache::put('admin', [
        'token' => bin2hex($token),
        'tokoid' => bin2hex($toko->id),
    ]);

    return [
        'session' => [
            'valid' => true,
            'token' => bin2hex($token),
            'tokoid' => bin2hex($toko->id),
        ],
    ];
}

function buatOrderQr(): Order
{
    $id = Str::uuid()->getBytes();

    DB::table('orders')->insert([
        'id' => $id,
        'tanggal_pesan' => now(),
        'tanggal_ambil' => now()->addDay(),
        'biaya_admin' => 0,
        'total_pembayaran' => 10000,
        'total_dibayar' => 0,
        'status_pembayaran' => 'pending',
    ]);

    return Order::findOrFail($id);
}

test('QR berisi id order hex membuka detail order', function () {
    $sesi = sesiOrderQr();
    $order = buatOrderQr();
    $hex = bin2hex($order->id);

    $this->withSession($sesi)
        ->postJson(route('order.validasiqr'), ['kode' => $hex])
        ->assertOk()
        ->assertJson([
            'valid' => true,
            'url' => route('order.detail', $hex),
        ]);
});

test('QR berisi URL struk juga dikenali', function () {
    $sesi = sesiOrderQr();
    $order = buatOrderQr();
    $hex = bin2hex($order->id);

    $this->withSession($sesi)
        ->postJson(route('order.validasiqr'), [
            'kode' => 'https://toko-contoh.test/struk/' . $hex,
        ])
        ->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('url', route('order.detail', $hex));
});

test('QR yang bukan id order ditolak dengan pesan', function () {
    $sesi = sesiOrderQr();

    $this->withSession($sesi)
        ->postJson(route('order.validasiqr'), ['kode' => 'bukan-qr-order'])
        ->assertOk()
        ->assertJsonPath('valid', false)
        ->assertJsonPath('message', fn ($pesan) => str_contains($pesan, 'QR tidak dikenali'));
});

test('id order yang tidak ada menghasilkan pesan order tidak ditemukan', function () {
    $sesi = sesiOrderQr();

    $this->withSession($sesi)
        ->postJson(route('order.validasiqr'), ['kode' => str_repeat('ab', 16)])
        ->assertOk()
        ->assertJsonPath('valid', false)
        ->assertJsonPath('message', 'Order tidak ditemukan.');
});

test('halaman order menampilkan tombol dan modal scan QR', function () {
    $this->withoutVite();
    $sesi = sesiOrderQr();
    buatOrderQr();

    $this->withSession($sesi)
        ->get(route('order.index'))
        ->assertOk()
        ->assertSee('Scan QR')
        ->assertSee('id="modal-scan-qr"', false)
        ->assertSee('html5-qrcode', false);
});
