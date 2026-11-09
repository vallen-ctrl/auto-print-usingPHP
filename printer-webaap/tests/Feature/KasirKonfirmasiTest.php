<?php

use App\Models\Computer;
use App\Models\File;
use App\Models\Order;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Session admin seperti hasil loginControler::login + token toko yang cocok. */
function sesiAdminKasir(): array
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
 * Order + pelanggan + satu file untuk halaman kasir.
 *
 * @return array{0: Order, 1: File, 2: string, 3: string} order, file, kode, nama
 */
function orderKasirUji(array $ubah = []): array
{
    $orderId = Str::uuid()->getBytes();
    $fileId = Str::uuid()->getBytes();
    $sessionPelanggan = 'uji-' . bin2hex(random_bytes(8));
    $nama = $ubah['nama'] ?? 'Pelanggan Uji';
    $kode = $ubah['kode'] ?? strtoupper(bin2hex(random_bytes(3)));
    $path = $ubah['path'] ?? 'orders/uji/uji-kasir.pdf';

    User::create([
        'sessionid' => $sessionPelanggan,
        'name' => $nama,
        'no_wa' => '081234567890',
        'create_at' => now(),
    ]);

    DB::table('orders')->insert([
        'id' => $orderId,
        'kode' => $kode,
        'tanggal_pesan' => now(),
        'tanggal_ambil' => now()->addDay(),
        'biaya_admin' => 4000,
        'total_pembayaran' => $ubah['total'] ?? 55000,
        'total_dibayar' => 0,
        'status_pembayaran' => $ubah['status'] ?? 'processing',
        'metode_pembayaran' => $ubah['metode'] ?? 'kasir',
        'session_id' => $sessionPelanggan,
        'tanggal_bayar' => now(),
    ]);

    DB::table('files')->insert([
        'id' => $fileId,
        'name' => 'uji-kasir.pdf',
        'path' => $path,
        'size' => 1024,
        'mime' => 'application/pdf',
        'total_pages' => 10,
        'order_id' => $orderId,
        'dihapus' => false,
        'subtotal' => 55000,
        'layanan' => $ubah['layanan'] ?? 'kilat-langsung',
    ]);

    return [Order::findOrFail($orderId), File::findOrFail($fileId), $kode, $nama];
}

test('halaman kasir hanya menampilkan order kasir yang menunggu konfirmasi', function () {
    [, , $kodeMenunggu, $namaMenunggu] = orderKasirUji(['nama' => 'Menunggu Bayar', 'kode' => 'KASIR1']);
    orderKasirUji(['nama' => 'Sudah Lunas', 'kode' => 'LUNAS1', 'status' => 'paid']);
    orderKasirUji(['nama' => 'Belum Pilih', 'kode' => 'BELUM1', 'status' => 'pending']);

    $this->withSession(sesiAdminKasir())
        ->get(route('kasir.index'))
        ->assertOk()
        ->assertSee('KASIR1')
        ->assertSee($namaMenunggu)
        ->assertDontSee('LUNAS1')
        ->assertDontSee('BELUM1');
});

test('kasir bisa mencari order berdasarkan nama pelanggan', function () {
    [, , $kodeBudi, $namaBudi] = orderKasirUji(['nama' => 'Budi Santoso', 'kode' => 'BUDI01']);
    [, , $kodeSiti] = orderKasirUji(['nama' => 'Siti Aminah', 'kode' => 'SITI01']);

    $this->withSession(sesiAdminKasir())
        ->get(route('kasir.index', ['search' => 'Budi']))
        ->assertOk()
        ->assertSee($kodeBudi)
        ->assertSee($namaBudi)
        ->assertDontSee($kodeSiti);
});

test('kasir bisa mencari order berdasarkan kode order', function () {
    [, , $kodeBudi] = orderKasirUji(['nama' => 'Budi Santoso', 'kode' => 'CARI01']);
    [, , $kodeSiti] = orderKasirUji(['nama' => 'Siti Aminah', 'kode' => 'CARI02']);

    $this->withSession(sesiAdminKasir())
        ->get(route('kasir.index', ['search' => 'CARI02']))
        ->assertOk()
        ->assertSee($kodeSiti)
        ->assertDontSee($kodeBudi);
});

test('kasir menandai pembayaran lunas dan file masuk antrean cetak', function () {
    Storage::fake('local');

    Computer::factory()->create();
    [$order] = orderKasirUji();

    $this->withSession(sesiAdminKasir())
        ->post(route('kasir.lunas', bin2hex($order->id)), ['total_dibayar' => 61000])
        ->assertRedirect()
        ->assertSessionHas('success');

    $fresh = $order->fresh();

    expect($fresh->status_pembayaran)->toBe('paid')
        ->and((int) $fresh->total_pembayaran)->toBe(61000)
        ->and((int) $fresh->total_dibayar)->toBe(61000);

    expect(DB::table('file_queue')->where('order_id', $order->id)->count())->toBe(1);
});

test('kasir membatalkan pembayaran lalu file pesanan dihapus', function () {
    Storage::fake('local');
    Storage::disk('local')->put('orders/uji/uji-kasir.pdf', 'isi pdf');

    [$order, $file] = orderKasirUji();

    $this->withSession(sesiAdminKasir())
        ->post(route('kasir.batal', bin2hex($order->id)))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status_pembayaran)->toBe('failed')
        ->and(File::find($file->id))->toBeNull();

    Storage::disk('local')->assertMissing('orders/uji/uji-kasir.pdf');
});

test('order yang sudah lunas tidak bisa dibatalkan dari halaman kasir', function () {
    Storage::fake('local');
    Storage::disk('local')->put('orders/uji/uji-kasir.pdf', 'isi pdf');

    [$order] = orderKasirUji(['status' => 'paid', 'kode' => 'PAID01']);

    $this->withSession(sesiAdminKasir())
        ->post(route('kasir.batal', bin2hex($order->id)))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($order->fresh()->status_pembayaran)->toBe('paid');
    Storage::disk('local')->assertExists('orders/uji/uji-kasir.pdf');
});

test('tombol kembali di konfirmasi membatalkan pesanan dan menghapus session', function () {
    Storage::fake('local');
    Storage::disk('local')->put('orders/uji/uji-kasir.pdf', 'isi pdf');

    [$order, $file] = orderKasirUji();
    $hex = bin2hex($order->id);

    DB::table('orders')->where('id', $order->id)->update(['browser_session' => 'sesi-lama']);

    $this->withSession([
        'terjadwal_order' => ['orderid' => $hex],
        'kilat_computer_id' => str_repeat('ab', 16),
    ])
        ->post(route('order.konfirmasi.batal', ['order_id' => $hex]))
        ->assertRedirect('/')
        ->assertSessionMissing('terjadwal_order')
        ->assertSessionMissing('kilat_computer_id')
        ->assertSessionHas('error');

    $fresh = $order->fresh();

    expect($fresh->status_pembayaran)->toBe('failed')
        ->and($fresh->browser_session)->toBeNull()
        ->and(File::find($file->id))->toBeNull();

    Storage::disk('local')->assertMissing('orders/uji/uji-kasir.pdf');
});

test('tombol kembali tidak mengubah order yang sudah lunas', function () {
    [$order] = orderKasirUji(['status' => 'paid', 'kode' => 'PAID02']);
    $hex = bin2hex($order->id);

    $this->post(route('order.konfirmasi.batal', ['order_id' => $hex]))
        ->assertRedirect(route('struk.render', ['id' => $hex]));

    expect($order->fresh()->status_pembayaran)->toBe('paid');
});

test('halaman konfirmasi menampilkan kode order untuk kasir', function () {
    $this->withoutVite();

    [$order] = orderKasirUji(['kode' => 'TAMPIL']);

    $this->get(route('order.konfirmasi', ['order_id' => bin2hex($order->id)]))
        ->assertOk()
        ->assertSee('Kode order')
        ->assertSee('TAMPIL');
});

test('halaman kasir butuh login admin', function () {
    $this->get(route('kasir.index'))->assertRedirect(route('admin.login'));
});
