<?php

use App\Http\Controllers\UploadTerjadwalController;
use App\Models\File;
use App\Models\Order;
use App\Models\Paper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Order + satu file milik alur kilat, untuk halaman formulir kilat. */
function buatOrderFormulirKilat(string $layanan): Order
{
    $orderId = Str::uuid()->getBytes();

    DB::table('orders')->insert([
        'id' => $orderId,
        'kode' => strtoupper(bin2hex(random_bytes(3))),
        'tanggal_pesan' => now(),
        'tanggal_ambil' => now(),
        'biaya_admin' => 0,
        'total_pembayaran' => 0,
        'total_dibayar' => 0,
        'status_pembayaran' => 'pending',
    ]);

    DB::table('files')->insert([
        'id' => Str::uuid()->getBytes(),
        'name' => 'uji-kilat.pdf',
        'path' => 'orders/uji/uji-kilat.pdf',
        'size' => 1024,
        'mime' => 'application/pdf',
        'total_pages' => 5,
        'order_id' => $orderId,
        'dihapus' => false,
        'subtotal' => 0,
        'layanan' => $layanan,
    ]);

    return Order::findOrFail($orderId);
}

/** Session formulir yang mengarah ke order kilat di atas. */
function sesiFormulirKilat(Order $order): array
{
    $file = File::where('order_id', $order->id)->firstOrFail();
    $fileHex = bin2hex($file->id);

    return [
        UploadTerjadwalController::SESSION_KEY => [
            'orderid' => bin2hex($order->id),
            'sessionid' => 'uji-kilat-' . bin2hex(random_bytes(4)),
            'files' => [
                $fileHex => [
                    'id' => $fileHex,
                    'name' => $file->name,
                    'total_pages' => $file->total_pages,
                    'localPath' => 'orders/uji/uji-kilat.pdf',
                ],
            ],
            'where' => 'formulir.kilat',
        ],
    ];
}

test('formulir kilat edit komputer hanya menampilkan nama dan subtotal ditentukan kasir', function () {
    $this->withoutVite();

    Paper::factory()->create([
        'name' => 'A4',
        'aktif' => 1,
        'harga_color' => 1000,
        'harga_grayscale' => 500,
    ]);

    $order = buatOrderFormulirKilat('kilat-edit');

    $this->withSession(sesiFormulirKilat($order))
        ->get(route('kilat.formulir', ['mode' => 'komputer', 'order' => bin2hex($order->id)]))
        ->assertOk()
        ->assertSee('Formulir Cetak Kilat')
        ->assertDontSee('Formulir Cetak Terjadwal')
        ->assertSee('Edit di Komputer')
        ->assertSee('konfigurasi-cetak is-hidden', false)
        ->assertSee('SUB TOTAL: akan ditentukan di kasir')
        ->assertDontSee('id="tanggal"', false)
        ->assertDontSee('id="pukul"', false)
        ->assertDontSee('id="sub-total"', false);
});

test('formulir kilat edit handphone menyembunyikan tanggal dan pukul, pengaturan tetap ada', function () {
    $this->withoutVite();

    Paper::factory()->create([
        'name' => 'A4',
        'aktif' => 1,
        'harga_color' => 1000,
        'harga_grayscale' => 500,
    ]);

    $order = buatOrderFormulirKilat('kilat-langsung');

    $this->withSession(sesiFormulirKilat($order))
        ->get(route('kilat.formulir', ['mode' => 'handphone', 'order' => bin2hex($order->id)]))
        ->assertOk()
        ->assertSee('Edit di Handphone')
        ->assertDontSee('konfigurasi-cetak is-hidden', false)
        ->assertDontSee('akan ditentukan di kasir')
        ->assertDontSee('id="tanggal"', false)
        ->assertDontSee('id="pukul"', false)
        ->assertSee('id="sub-total"', false);
});

test('order kilat bisa dibuat tanpa mengirim tanggal pengambilan', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-09 10:00:00'));

    tokoDenganJadwal();

    $paper = Paper::factory()->create([
        'name' => 'A4',
        'aktif' => 1,
        'harga_color' => 1000,
        'harga_grayscale' => 500,
    ]);

    $order = buatOrderFormulirKilat('kilat-edit');
    $file = File::where('order_id', $order->id)->firstOrFail();
    $hex = bin2hex($order->id);

    $response = $this->withSession(sesiFormulirKilat($order))
        ->post(route('create.order'), [
            'orderid' => $hex,
            'nama' => 'Nama Penyetak',
            'files' => [[
                'id' => bin2hex($file->id),
                'orientasi' => 'portrait',
                'warna' => 'hitam putih',
                'kualitas' => 'standar',
                'jumlah' => 1,
                'halaman' => '',
                'catatan' => '',
                'id_paper' => $paper->id,
            ]],
        ]);

    $response
        ->assertRedirect(route('order.konfirmasi', ['order_id' => $hex]))
        ->assertSessionHasNoErrors();
});
