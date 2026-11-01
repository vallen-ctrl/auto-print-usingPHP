<?php

use App\Http\Controllers\UploadTerjadwalController;
use App\Models\Order;
use App\Models\Paper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Order + satu file untuk halaman formulir. */
function buatOrderFormulir(?int $totalPages = 1): Order
{
    $orderId = Str::uuid()->getBytes();

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
        'id' => Str::uuid()->getBytes(),
        'name' => 'uji-formulir.pdf',
        'path' => 'orders/uji/uji-formulir.pdf',
        'size' => 1024,
        'mime' => 'application/pdf',
        'total_pages' => $totalPages,
        'order_id' => $orderId,
        'dihapus' => false,
        'subtotal' => 0,
        'layanan' => 'terjadwal',
    ]);

    return Order::findOrFail($orderId);
}

/** Session "terjadwal_order" yang mengarah ke order di atas. */
function sesiFormulir(Order $order): array
{
    return [
        UploadTerjadwalController::SESSION_KEY => [
            'orderid' => bin2hex($order->id),
            'sessionid' => 'sesi-uji',
            'files' => [],
            'where' => 'formulir.terjadwal',
        ],
    ];
}

test('formulir memberi pesan kalau jumlah halaman file tidak terbaca', function () {
    $this->withoutVite();

    $order = buatOrderFormulir(0);

    $this->withSession(sesiFormulir($order))
        ->get(route('formulir.terjadwal', ['order' => bin2hex($order->id)]))
        ->assertOk()
        ->assertSee('Formulir tidak bisa diproses')
        ->assertSee('Jumlah halaman file "uji-formulir.pdf" tidak terbaca');
});

test('formulir memberi pesan kalau semua harga kertas masih nol', function () {
    $this->withoutVite();

    $order = buatOrderFormulir(5);

    Paper::factory()->create([
        'name' => 'A4',
        'aktif' => 1,
        'harga_color' => 0,
        'harga_grayscale' => 0,
    ]);

    $this->withSession(sesiFormulir($order))
        ->get(route('formulir.terjadwal', ['order' => bin2hex($order->id)]))
        ->assertOk()
        ->assertSee('Formulir tidak bisa diproses')
        ->assertSee('Harga kertas masih Rp. 0');
});

test('formulir tidak menampilkan pesan kesalahan kalau data siap', function () {
    $this->withoutVite();

    $order = buatOrderFormulir(5);

    Paper::factory()->create([
        'name' => 'A4',
        'aktif' => 1,
        'harga_color' => 1000,
        'harga_grayscale' => 500,
    ]);

    $this->withSession(sesiFormulir($order))
        ->get(route('formulir.terjadwal', ['order' => bin2hex($order->id)]))
        ->assertOk()
        ->assertDontSee('Formulir tidak bisa diproses');
});
