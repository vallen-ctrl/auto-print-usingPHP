<?php

use App\Http\Controllers\UploadTerjadwalController;
use App\Models\File;
use App\Models\Order;
use App\Models\Toko;
use App\Service\KedaluwarsaOrder;
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

/** Order dengan tanggal_pesan / tanggal_bayar yang bisa diatur. */
function buatOrderPembayaran(
    string $status = 'pending',
    ?Carbon $tanggalPesan = null,
    ?Carbon $tanggalBayar = null
): Order {
    $id = Str::uuid()->getBytes();

    DB::table('orders')->insert([
        'id' => $id,
        'tanggal_pesan' => $tanggalPesan ?? now(),
        'tanggal_bayar' => $tanggalBayar,
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

/** Session "terjadwal_order" seperti hasil UploadTerjadwalController::store(). */
function sesiOrderTerjadwal(Order $order): array
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

test('halaman order menandai order pending lebih dari 1 jam sebagai failed dan menghapus filenya', function () {
    Storage::fake('local');
    $session = sesiDashboardOrder();

    $order = buatOrderPembayaran('pending', now()->subMinutes(KedaluwarsaOrder::BATAS_UPLOAD_MENIT + 5));
    Storage::disk('local')->put('orders/lama/a.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/lama/a.pdf');

    $this->withSession($session)->get(route('order.index'))->assertOk();

    expect($order->fresh()->status_pembayaran)->toBe('failed');
    expect(File::find($file->id))->toBeNull();
    expect(DB::table('file_queue')->where('file_id', $file->id)->count())->toBe(0);
    Storage::disk('local')->assertMissing('orders/lama/a.pdf');
});

test('order pending yang belum 1 jam dibiarkan apa adanya', function () {
    Storage::fake('local');
    $session = sesiDashboardOrder();

    $order = buatOrderPembayaran('pending', now()->subMinutes(30));
    Storage::disk('local')->put('orders/baru/b.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/baru/b.pdf');

    $this->withSession($session)->get(route('order.index'))->assertOk();

    expect($order->fresh()->status_pembayaran)->toBe('pending');
    expect(File::find($file->id))->not->toBeNull();
    Storage::disk('local')->assertExists('orders/baru/b.pdf');
});

test('order yang sudah menekan bayar kedaluwarsa 20 menit setelahnya', function () {
    Storage::fake('local');
    $session = sesiDashboardOrder();

    $order = buatOrderPembayaran(
        'pending',
        now()->subMinutes(40),
        now()->subMinutes(KedaluwarsaOrder::BATAS_BAYAR_MENIT + 1)
    );
    Storage::disk('local')->put('orders/bayar/c.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/bayar/c.pdf');

    $this->withSession($session)->get(route('order.index'))->assertOk();

    expect($order->fresh()->status_pembayaran)->toBe('failed');
    expect(File::find($file->id))->toBeNull();
    Storage::disk('local')->assertMissing('orders/bayar/c.pdf');
});

test('order yang baru menekan bayar tidak dikadaluarsakan', function () {
    Storage::fake('local');
    $session = sesiDashboardOrder();

    $order = buatOrderPembayaran('pending', now()->subMinutes(40), now()->subMinutes(5));
    Storage::disk('local')->put('orders/bayar/d.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/bayar/d.pdf');

    $this->withSession($session)->get(route('order.index'))->assertOk();

    expect($order->fresh()->status_pembayaran)->toBe('pending');
    expect(File::find($file->id))->not->toBeNull();
    Storage::disk('local')->assertExists('orders/bayar/d.pdf');
});

test('order yang sudah dibayar tidak ikut dikadaluarsakan', function () {
    Storage::fake('local');
    $session = sesiDashboardOrder();

    $order = buatOrderPembayaran('paid', now()->subHours(2));
    Storage::disk('local')->put('orders/lunas/e.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/lunas/e.pdf', 'completed');

    $this->withSession($session)->get(route('order.index'))->assertOk();

    expect($order->fresh()->status_pembayaran)->toBe('paid');
    expect(File::find($file->id))->not->toBeNull();
    Storage::disk('local')->assertExists('orders/lunas/e.pdf');
});

test('tandaiBayar mencatat waktu klik bayar dan menggeser batas waktunya', function () {
    $order = buatOrderPembayaran('pending', now()->subMinutes(50));

    $kedaluwarsa = app(KedaluwarsaOrder::class);

    // Belum 60 menit sejak upload: masih boleh dibayar.
    expect($kedaluwarsa->sudahKadaluarsa($order))->toBeFalse();

    $kedaluwarsa->tandaiBayar($order);

    expect($order->fresh()->tanggal_bayar)->not->toBeNull()
        ->and($kedaluwarsa->sudahKadaluarsa($order->fresh()))->toBeFalse();

    // Setelah 20 menit sejak klik bayar, order dianggap kedaluwarsa.
    $order->fresh()->update([
        'tanggal_bayar' => now()->subMinutes(KedaluwarsaOrder::BATAS_BAYAR_MENIT + 1),
    ]);

    expect($kedaluwarsa->sudahKadaluarsa($order->fresh()))->toBeTrue();
});

test('bayarSekarang menolak order yang lewat 1 jam sejak upload dan menghapus file', function () {
    Storage::fake('local');

    $order = buatOrderPembayaran('pending', now()->subMinutes(KedaluwarsaOrder::BATAS_UPLOAD_MENIT + 1));
    Storage::disk('local')->put('orders/exp/f.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/exp/f.pdf');

    $response = $this->withSession(sesiOrderTerjadwal($order))
        ->postJson(route('create.snap.token'), ['orderid' => bin2hex($order->id)]);

    $response->assertStatus(410)->assertJsonPath('error', true);

    expect($response->json('message'))->toContain('dihapus dari server');
    expect($order->fresh()->status_pembayaran)->toBe('failed');
    expect(File::find($file->id))->toBeNull();
    Storage::disk('local')->assertMissing('orders/exp/f.pdf');
});

test('bayarSekarang menolak order yang lewat 20 menit setelah klik bayar', function () {
    Storage::fake('local');

    $order = buatOrderPembayaran(
        'pending',
        now()->subMinutes(40),
        now()->subMinutes(KedaluwarsaOrder::BATAS_BAYAR_MENIT + 1)
    );
    Storage::disk('local')->put('orders/exp/g.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/exp/g.pdf');

    $response = $this->withSession(sesiOrderTerjadwal($order))
        ->postJson(route('create.snap.token'), ['orderid' => bin2hex($order->id)]);

    $response->assertStatus(410);
    expect($response->json('message'))->toContain('20 menit');
    expect($order->fresh()->status_pembayaran)->toBe('failed');
    expect(File::find($file->id))->toBeNull();
});

test('bayarSekarang menolak order yang sudah berstatus failed', function () {
    $order = buatOrderPembayaran('failed', now()->subMinutes(2));

    $response = $this->withSession(sesiOrderTerjadwal($order))
        ->postJson(route('create.snap.token'), ['orderid' => bin2hex($order->id)]);

    $response->assertStatus(409);
    expect($response->json('message'))->toContain('tidak bisa dibayar');
});

test('bayarSekarang tanpa sesi terjadwal dijawab json kadaluarsa', function () {
    $response = $this->postJson(route('create.snap.token'), ['orderid' => str_repeat('ab', 16)]);

    $response->assertStatus(410);
    expect($response->json('message'))->toContain('kadaluarsa');
});

test('membuka "/" dengan order kedaluwarsa menghapus file dan kembali ke beranda dengan pesan', function () {
    $this->withoutVite();
    Storage::fake('local');

    $order = buatOrderPembayaran('pending', now()->subMinutes(KedaluwarsaOrder::BATAS_UPLOAD_MENIT + 5));
    Storage::disk('local')->put('orders/root/h.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/root/h.pdf');

    $this->withSession(sesiOrderTerjadwal($order))
        ->get('/')
        ->assertRedirect('/')
        ->assertSessionHas('error', fn ($pesan) => str_contains($pesan, 'dihapus dari server'));

    expect($order->fresh()->status_pembayaran)->toBe('failed');
    expect(File::find($file->id))->toBeNull();
    Storage::disk('local')->assertMissing('orders/root/h.pdf');

    // Session order sudah dibersihkan: membuka "/" lagi menampilkan beranda.
    $this->get('/')->assertOk();
});

test('membuka "/" dengan order yang masih berlaku diarahkan ke formulir', function () {
    $order = buatOrderPembayaran('pending', now()->subMinutes(10));

    $this->withSession(sesiOrderTerjadwal($order))
        ->get('/')
        ->assertRedirect(route('formulir.terjadwal', ['order' => bin2hex($order->id)]));
});

test('membuka formulir dengan order kedaluwarsa diarahkan ke beranda dan file dihapus', function () {
    Storage::fake('local');

    $order = buatOrderPembayaran('pending', now()->subMinutes(45), now()->subMinutes(25));
    Storage::disk('local')->put('orders/form/i.pdf', 'isi pdf');
    $file = fileOrderPembayaran($order, 'orders/form/i.pdf');

    $this->withSession(sesiOrderTerjadwal($order))
        ->get(route('formulir.terjadwal', ['order' => bin2hex($order->id)]))
        ->assertRedirect('/')
        ->assertSessionHas('error', fn ($pesan) => str_contains($pesan, '20 menit'));

    expect($order->fresh()->status_pembayaran)->toBe('failed');
    expect(File::find($file->id))->toBeNull();
});
