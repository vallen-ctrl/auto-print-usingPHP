<?php

use App\Models\Computer;
use App\Models\File;
use App\Models\Order;
use App\Models\Paper;
use App\Models\Toko;
use App\Service\AntreanCetak;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Session admin seperti hasil loginControler::login + token toko yang cocok. */
function sesiAdminCetak(): array
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

/**
 * Order + satu file untuk menguji penempatan antrean dan pilihan mode cetak.
 *
 * @return array{0: Order, 1: File}
 */
function orderModeCetak(string $layanan, array $ubah = []): array
{
    $orderId = Str::uuid()->getBytes();
    $fileId = Str::uuid()->getBytes();

    DB::table('orders')->insert([
        'id' => $orderId,
        'kode' => strtoupper(bin2hex(random_bytes(3))),
        'tanggal_pesan' => now(),
        'tanggal_ambil' => $ubah['tanggal_ambil'] ?? now(),
        'biaya_admin' => 4000,
        'total_pembayaran' => 55000,
        'total_dibayar' => 0,
        'status_pembayaran' => $ubah['status'] ?? 'pending',
        'metode_pembayaran' => $ubah['metode'] ?? null,
        'mode_cetak' => $ubah['mode_cetak'] ?? 'otomatis',
        'computer_id' => $ubah['computer_id'] ?? null,
    ]);

    DB::table('files')->insert([
        'id' => $fileId,
        'name' => 'uji-cetak.pdf',
        'path' => 'orders/uji/uji-cetak.pdf',
        'size' => 1024,
        'mime' => 'application/pdf',
        'total_pages' => 5,
        'order_id' => $orderId,
        'dihapus' => false,
        'subtotal' => 55000,
        'layanan' => $layanan,
    ]);

    return [Order::findOrFail($orderId), File::findOrFail($fileId)];
}

test('order terjadwal mode tukang hanya disimpan di server tanpa komputer', function () {
    Computer::factory()->create();
    [$order] = orderModeCetak('terjadwal', ['mode_cetak' => 'tukang', 'status' => 'paid']);

    $hasil = app(AntreanCetak::class)->untukOrder($order);

    $queue = DB::table('file_queue')->where('order_id', $order->id)->first();

    expect($hasil['berhasil'])->toBe(1)
        ->and($queue)->not->toBeNull()
        ->and($queue->computer_id)->toBeNull();
});

test('order kilat-edit diantrekan ke komputer yang dipindai customer', function () {
    $komputer = Computer::factory()->create();
    [$order] = orderModeCetak('kilat-edit', [
        'status' => 'processing',
        'metode' => 'kasir',
        'computer_id' => $komputer->id,
    ]);

    app(AntreanCetak::class)->untukOrder($order);

    $queue = DB::table('file_queue')->where('order_id', $order->id)->first();

    expect($queue->computer_id)->toBe($komputer->id);
});

test('order kilat-langsung diantrekan ke komputer yang tersedia', function () {
    $komputer = Computer::factory()->create();
    [$order] = orderModeCetak('kilat-langsung', ['status' => 'paid']);

    app(AntreanCetak::class)->untukOrder($order);

    $queue = DB::table('file_queue')->where('order_id', $order->id)->first();

    expect($queue->computer_id)->toBe($komputer->id);
});

test('antrean tidak digandakan saat dipanggil dua kali', function () {
    Computer::factory()->create();
    [$order] = orderModeCetak('kilat-langsung', ['status' => 'paid']);

    $layanan = app(AntreanCetak::class);
    $layanan->untukOrder($order);
    $kedua = $layanan->untukOrder($order);

    expect($kedua['dilewati'])->toBe(1)
        ->and(DB::table('file_queue')->where('order_id', $order->id)->count())->toBe(1);
});

test('memilih bayar di kasir untuk kilat-edit langsung mengantrekan unduhan', function () {
    $komputer = Computer::factory()->create();
    [$order] = orderModeCetak('kilat-edit', ['computer_id' => $komputer->id]);
    $hex = bin2hex($order->id);

    $this->withSession(['terjadwal_order' => ['orderid' => $hex]])
        ->postJson(route('order.konfirmasi.kasir', ['order_id' => $hex]))
        ->assertOk();

    $queue = DB::table('file_queue')->where('order_id', $order->id)->first();

    expect($queue)->not->toBeNull()
        ->and($queue->computer_id)->toBe($komputer->id);
});

test('formulir terjadwal menyimpan pilihan dicetak oleh tukang dan jam ambil', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-10 10:00:00'));

    tokoDenganJadwal();

    $paper = Paper::factory()->create([
        'name' => 'A4',
        'aktif' => 1,
        'harga_color' => 1000,
        'harga_grayscale' => 500,
    ]);

    [$order, $file] = orderModeCetak('terjadwal');
    $hex = bin2hex($order->id);
    $fileHex = bin2hex($file->id);
    $ambil = now()->addHours(6);

    $this->withSession([
        'terjadwal_order' => [
            'orderid' => $hex,
            'sessionid' => 'uji-cetak-sesi',
            'files' => [
                $fileHex => [
                    'id' => $fileHex,
                    'name' => $file->name,
                    'total_pages' => $file->total_pages,
                    'localPath' => 'orders/uji/uji-cetak.pdf',
                ],
            ],
            'where' => 'formulir.terjadwal',
        ],
    ])->post(route('create.order'), [
        'orderid' => $hex,
        'nama' => 'Nama Uji',
        'no_wa' => '081234567890',
        'tanggal_ambil' => $ambil->toIso8601String(),
        'mode_cetak' => 'tukang',
        'files' => [[
            'id' => $fileHex,
            'orientasi' => 'portrait',
            'warna' => 'hitam putih',
            'kualitas' => 'standar',
            'jumlah' => 1,
            'halaman' => '',
            'catatan' => '',
            'id_paper' => $paper->id,
        ]],
    ])->assertRedirect(route('order.konfirmasi', ['order_id' => $hex]));

    $segar = $order->fresh();

    expect($segar->mode_cetak)->toBe('tukang')
        ->and($segar->tanggal_ambil->format('Y-m-d H:i'))->toBe($ambil->format('Y-m-d H:i'));
});

test('admin bisa mengubah status file order yang dicetak tukang', function () {
    Computer::factory()->create();
    [$order] = orderModeCetak('terjadwal', ['mode_cetak' => 'tukang', 'status' => 'paid']);

    app(AntreanCetak::class)->untukOrder($order);

    $this->withSession(sesiAdminCetak())
        ->post(route('order.statusfile', bin2hex($order->id)), ['status' => 'completed'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(DB::table('file_queue')->where('order_id', $order->id)->value('status'))->toBe('completed');
});

test('status file order otomatis tidak bisa diubah manual', function () {
    [$order] = orderModeCetak('terjadwal', ['mode_cetak' => 'otomatis', 'status' => 'paid']);

    $this->withSession(sesiAdminCetak())
        ->post(route('order.statusfile', bin2hex($order->id)), ['status' => 'completed'])
        ->assertSessionHas('error');
});
