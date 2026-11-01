<?php

use App\Models\File;
use App\Models\Order;
use App\Service\KedaluwarsaOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Order pending/paid dengan waktu bayar yang bisa diatur. */
function buatOrderKonfirmasiPembayaran(string $status = 'pending', ?Carbon $tanggalBayar = null): Order
{
    $id = Str::uuid()->getBytes();

    DB::table('orders')->insert([
        'id' => $id,
        'tanggal_pesan' => now(),
        'tanggal_bayar' => $tanggalBayar,
        'tanggal_ambil' => now()->addDay(),
        'biaya_admin' => 2000,
        'total_pembayaran' => 57000,
        'total_dibayar' => 0,
        'status_pembayaran' => $status,
    ]);

    return Order::findOrFail($id);
}

/** Satu file milik order, untuk ditampilkan di halaman konfirmasi. */
function fileKonfirmasiPembayaran(Order $order, string $path, string $layanan = 'terjadwal'): File
{
    $fileId = Str::uuid()->getBytes();

    DB::table('files')->insert([
        'id' => $fileId,
        'name' => basename($path),
        'path' => $path,
        'size' => 409600,
        'mime' => 'application/pdf',
        'total_pages' => 50,
        'order_id' => $order->id,
        'dihapus' => false,
        'subtotal' => 55000,
        'layanan' => $layanan,
    ]);

    return File::findOrFail($fileId);
}

test('halaman konfirmasi memuat data order dan memulai hitung mundur pembayaran', function () {
    $this->withoutVite();

    $order = buatOrderKonfirmasiPembayaran();
    fileKonfirmasiPembayaran($order, 'orders/konfirmasi/dokumen-uji.pdf');

    $response = $this->get(route('order.konfirmasi', ['order_id' => bin2hex($order->id)]));

    $response->assertOk()
        ->assertSee('Konfirmasi Pembayaran')
        ->assertSee('dokumen-uji.pdf')
        ->assertSee('Rp. 55.000')
        ->assertSee('id="timer-bayar"', false)
        ->assertSee('data-sisa-detik=', false);

    expect($order->fresh()->tanggal_bayar)->not->toBeNull();
});

test('membuka ulang halaman konfirmasi tidak menambah waktu pembayaran', function () {
    $this->withoutVite();

    $order = buatOrderKonfirmasiPembayaran('pending', now()->subMinutes(5));
    fileKonfirmasiPembayaran($order, 'orders/konfirmasi/ulang.pdf');

    $sebelum = $order->fresh()->tanggal_bayar->toDateTimeString();

    $this->get(route('order.konfirmasi', ['order_id' => bin2hex($order->id)]))->assertOk();

    expect($order->fresh()->tanggal_bayar->toDateTimeString())->toBe($sebelum);
});

test('layanan terjadwal hanya menawarkan QRIS', function () {
    $this->withoutVite();

    $order = buatOrderKonfirmasiPembayaran();
    fileKonfirmasiPembayaran($order, 'orders/konfirmasi/terjadwal.pdf', 'terjadwal');

    $html = $this->get(route('order.konfirmasi', ['order_id' => bin2hex($order->id)]))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, 'data-metode="'))->toBe(1)
        ->and($html)->toContain('data-metode="qris"');
});

test('layanan kilat-langsung menawarkan QRIS dan bayar di kasir', function () {
    $this->withoutVite();

    $order = buatOrderKonfirmasiPembayaran();
    fileKonfirmasiPembayaran($order, 'orders/konfirmasi/kilat.pdf', 'kilat-langsung');

    $html = $this->get(route('order.konfirmasi', ['order_id' => bin2hex($order->id)]))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, 'data-metode="'))->toBe(2)
        ->and($html)->toContain('data-metode="qris"')
        ->and($html)->toContain('data-metode="kasir"');
});

test('order yang sudah dibayar diarahkan ke halaman status pembayaran', function () {
    $order = buatOrderKonfirmasiPembayaran('paid');

    $this->get(route('order.konfirmasi', ['order_id' => bin2hex($order->id)]))
        ->assertRedirect(route('struk.render', ['id' => bin2hex($order->id)]));
});

test('order kedaluwarsa dihapus filenya lalu kembali ke beranda dengan pesan', function () {
    Storage::fake('local');
    Storage::disk('local')->put('orders/konfirmasi/exp.pdf', 'isi pdf');

    $order = buatOrderKonfirmasiPembayaran(
        'pending',
        now()->subMinutes(KedaluwarsaOrder::BATAS_BAYAR_MENIT + 1)
    );
    $file = fileKonfirmasiPembayaran($order, 'orders/konfirmasi/exp.pdf');

    $this->get(route('order.konfirmasi', ['order_id' => bin2hex($order->id)]))
        ->assertRedirect('/')
        ->assertSessionHas('error', fn ($pesan) => str_contains($pesan, 'dihapus dari server'));

    expect($order->fresh()->status_pembayaran)->toBe('failed')
        ->and(File::find($file->id))->toBeNull();
    Storage::disk('local')->assertMissing('orders/konfirmasi/exp.pdf');
});

test('order id yang tidak dikenal kembali ke beranda', function () {
    $this->get('/order/konfirmasi/' . str_repeat('0', 32))
        ->assertRedirect('/')
        ->assertSessionHas('error');
});
