<?php

use App\Http\Controllers\UploadTerjadwalController;
use App\Models\File;
use App\Models\Order;
use App\Models\Toko;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Sesi admin dashboard (dipakai VerifyTokenMiddleware). */
function sesiDashboardOrder(): array
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

/** Order dengan tanggal_pesan yang bisa diatur (default: sekarang). */
function buatOrderPembayaran(string $status = 'pending', ?Carbon $tanggalPesan = null): Order
{
    $id = Str::uuid()->getBytes();

    DB::table('orders')->insert([
        'id' => $id,
        'tanggal_pesan' => $tanggalPesan ?? now(),
        'tanggal_ambil' => now()->addDay(),
        'biaya_admin' => 0,
        'total_pembayaran' => 10000,
        'total_dibayar' => 0,
        'status_pembayaran' => $status,
    ]);

    return Order::findOrFail($id);
}

/** File upload + satu baris antrean milik sebuah order. */
function fileOrderPembayaran(Order $order, string $path, string $statusAntrean = 'pending'): File
{
    $fileId = Str::uuid()->getBytes();

    DB::table('files')->insert([
        'id' => $fileId,
        'name' => basename($path),
        'path' => $path,
        'size' => 1024,
        'mime' => 'application/pdf',
        'total_pages' => 1,
        'order_id' => $order->id,
        'dihapus' => false,
        'subtotal' => 0,
        'layanan' => 'terjadwal',
    ]);

    DB::table('file_queue')->insert([
        'status' => $statusAntrean,
        'path' => $path,
        'order_id' => $order->id,
        'file_id' => $fileId,
        'computer_id' => null,
    ]);

    return File::findOrFail($fileId);
}

test('halaman order menandai order lewat 15 menit sebagai failed dan menghapus filenya', function () {
    Storage::fake('local');
    $session = sesiDashboardOrder();

    $order = buatOrderPembayaran('pending', now()->subMinutes(20));
    Storage::disk('local')->put('orders/lama/a.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/lama/a.pdf');

    $this->withSession($session)->get(route('order.index'))->assertOk();

    expect($order->fresh()->status_pembayaran)->toBe('failed');
    expect(File::find($file->id))->toBeNull();
    expect(DB::table('file_queue')->where('file_id', $file->id)->count())->toBe(0);
    Storage::disk('local')->assertMissing('orders/lama/a.pdf');
});

test('order pending yang belum 15 menit dibiarkan apa adanya', function () {
    Storage::fake('local');
    $session = sesiDashboardOrder();

    $order = buatOrderPembayaran('pending', now()->subMinutes(5));
    Storage::disk('local')->put('orders/baru/b.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/baru/b.pdf');

    $this->withSession($session)->get(route('order.index'))->assertOk();

    expect($order->fresh()->status_pembayaran)->toBe('pending');
    expect(File::find($file->id))->not->toBeNull();
    Storage::disk('local')->assertExists('orders/baru/b.pdf');
});

test('order yang sudah dibayar tidak ikut dikadaluarsakan', function () {
    Storage::fake('local');
    $session = sesiDashboardOrder();

    $order = buatOrderPembayaran('paid', now()->subHours(2));
    Storage::disk('local')->put('orders/lunas/c.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/lunas/c.pdf', 'completed');

    $this->withSession($session)->get(route('order.index'))->assertOk();

    expect($order->fresh()->status_pembayaran)->toBe('paid');
    expect(File::find($file->id))->not->toBeNull();
    Storage::disk('local')->assertExists('orders/lunas/c.pdf');
});

test('bayarSekarang menolak pembayaran setelah 15 menit dan menghapus file', function () {
    Storage::fake('local');

    $order = buatOrderPembayaran('pending', now()->subMinutes(16));
    Storage::disk('local')->put('orders/exp/d.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/exp/d.pdf');

    $response = $this->withSession([
        UploadTerjadwalController::SESSION_KEY => [
            'orderid' => bin2hex($order->id),
            'sessionid' => 'sesi-uji',
            'files' => [],
        ],
    ])->postJson(route('create.snap.token'), ['orderid' => bin2hex($order->id)]);

    $response->assertStatus(410)->assertJsonPath('error', true);

    expect($response->json('message'))->toContain('kadaluarsa');
    expect($order->fresh()->status_pembayaran)->toBe('failed');
    expect(File::find($file->id))->toBeNull();
    Storage::disk('local')->assertMissing('orders/exp/d.pdf');
});

test('bayarSekarang menolak order yang sudah berstatus failed', function () {
    $order = buatOrderPembayaran('failed', now()->subMinutes(2));

    $response = $this->withSession([
        UploadTerjadwalController::SESSION_KEY => [
            'orderid' => bin2hex($order->id),
            'sessionid' => 'sesi-uji',
            'files' => [],
        ],
    ])->postJson(route('create.snap.token'), ['orderid' => bin2hex($order->id)]);

    $response->assertStatus(409);
    expect($response->json('message'))->toContain('tidak bisa dibayar');
});

test('bayarSekarang tanpa sesi terjadwal dijawab json kadaluarsa', function () {
    $response = $this->postJson(route('create.snap.token'), ['orderid' => str_repeat('ab', 16)]);

    $response->assertStatus(410);
    expect($response->json('message'))->toContain('kadaluarsa');
});
