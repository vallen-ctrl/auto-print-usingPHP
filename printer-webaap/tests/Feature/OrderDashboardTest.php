<?php

use App\Models\Order;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Sesi admin dashboard (dipakai VerifyTokenMiddleware). */
function sesiOrderDashboard(): array
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

function buatPelangganDashboard(string $nama = 'Budi Santoso', string $noWa = '08123456789'): User
{
    return User::factory()->create([
        'sessionid' => 'sesi-'.Str::uuid(),
        'name' => $nama,
        'no_wa' => $noWa,
    ]);
}

function buatOrderDashboard(
    User $pelanggan,
    string $status = 'pending',
    ?Carbon $tanggalPesan = null,
    ?string $kode = null,
    ?Carbon $tanggalBayar = null
): Order {
    $id = Str::uuid()->getBytes();

    DB::table('orders')->insert([
        'id' => $id,
        'kode' => $kode,
        'tanggal_pesan' => $tanggalPesan ?? now(),
        'tanggal_bayar' => $tanggalBayar,
        'tanggal_ambil' => now()->addDay(),
        'biaya_admin' => 0,
        'total_pembayaran' => 10000,
        'total_dibayar' => 0,
        'status_pembayaran' => $status,
        'session_id' => $pelanggan->sessionid,
    ]);

    return Order::findOrFail($id);
}

function fileOrderDashboard(Order $order, string $nama, string $isi): void
{
    Storage::disk('local')->put('orders/'.$nama, $isi);

    DB::table('files')->insert([
        'id' => Str::uuid()->getBytes(),
        'name' => $nama,
        'path' => 'orders/'.$nama,
        'size' => strlen($isi),
        'mime' => 'application/pdf',
        'total_pages' => 1,
        'order_id' => $order->id,
        'dihapus' => false,
        'subtotal' => 0,
        'layanan' => 'terjadwal',
    ]);
}

test('halaman order menampilkan nama pemesan dan bisa mencari berdasarkan nama', function () {
    $sesi = sesiOrderDashboard();

    $budi = buatPelangganDashboard('Budi Santoso');
    $siti = buatPelangganDashboard('Siti Aminah');

    buatOrderDashboard($budi);
    buatOrderDashboard($siti);

    $this->withSession($sesi)->get(route('order.index'))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('Siti Aminah');

    $this->withSession($sesi)->get(route('order.index', ['search' => 'Siti']))
        ->assertOk()
        ->assertSee('Siti Aminah')
        ->assertDontSee('Budi Santoso');
});

test('detail order menampilkan status pembayaran sebagai badge', function () {
    $sesi = sesiOrderDashboard();
    $order = buatOrderDashboard(buatPelangganDashboard(), 'paid');

    $this->withSession($sesi)->get(route('order.detail', bin2hex($order->id)))
        ->assertOk()
        ->assertSee('bg-success-soft')
        ->assertSee('paid');
});

test('detail order menampilkan link WhatsApp pelanggan', function () {
    $sesi = sesiOrderDashboard();
    $pelanggan = buatPelangganDashboard('Budi Santoso', '0812-3456-789');
    $order = buatOrderDashboard($pelanggan);

    $this->withSession($sesi)->get(route('order.detail', bin2hex($order->id)))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('https://wa.me/628123456789', false);
});

test('detail order tanpa nomor WhatsApp tidak menampilkan link', function () {
    $sesi = sesiOrderDashboard();
    $pelanggan = buatPelangganDashboard('Siti Aminah', '');
    $order = buatOrderDashboard($pelanggan);

    $this->withSession($sesi)->get(route('order.detail', bin2hex($order->id)))
        ->assertOk()
        ->assertDontSee('wa.me');
});

test('refresh pembayaran memberi tahu kalau customer belum menekan bayar sekarang', function () {
    $sesi = sesiOrderDashboard();
    $order = buatOrderDashboard(buatPelangganDashboard());

    $this->withSession($sesi)
        ->postJson(route('order.refreshpembayaran', bin2hex($order->id)))
        ->assertOk()
        ->assertJson(['berubah' => false, 'status' => 'pending'])
        ->assertJsonPath('pesan', fn ($pesan) => str_contains($pesan, 'Bayar sekarang'));

    expect($order->fresh()->status_pembayaran)->toBe('pending');
});

test('refresh pembayaran pada order lunas tidak mengubah apa pun', function () {
    $sesi = sesiOrderDashboard();
    $order = buatOrderDashboard(buatPelangganDashboard(), 'paid');

    $this->withSession($sesi)
        ->postJson(route('order.refreshpembayaran', bin2hex($order->id)))
        ->assertOk()
        ->assertJson(['berubah' => false, 'status' => 'paid'])
        ->assertJsonPath('pesan', fn ($pesan) => str_contains($pesan, 'sudah lunas'));
});

test('unduh semua menggabungkan seluruh file order menjadi satu ZIP', function () {
    Storage::fake('local');
    $sesi = sesiOrderDashboard();
    $order = buatOrderDashboard(buatPelangganDashboard(), kode: 'ABC123');

    fileOrderDashboard($order, 'a.pdf', 'isi-a');
    fileOrderDashboard($order, 'b.pdf', 'isi-b');

    $this->withSession($sesi)
        ->get(route('order.downloadsemua', bin2hex($order->id)))
        ->assertOk()
        ->assertDownload('order-ABC123.zip');
});

test('unduh semua tanpa file yang tersedia mengembalikan 404', function () {
    Storage::fake('local');
    $sesi = sesiOrderDashboard();
    $order = buatOrderDashboard(buatPelangganDashboard());

    $this->withSession($sesi)
        ->get(route('order.downloadsemua', bin2hex($order->id)))
        ->assertNotFound();
});
