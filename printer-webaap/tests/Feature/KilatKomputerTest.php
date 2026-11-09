<?php

use App\Http\Controllers\KilatController;
use App\Http\Controllers\UploadTerjadwalController;
use App\Models\Computer;
use App\Models\Order;
use App\Service\tokenComputer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * PDF satu halaman minimal yang bisa dibaca Smalot\PdfParser. Dipakai untuk
 * menguji alur upload tanpa bergantung pada file contoh di storage.
 */
function pdfContohKilat(): string
{
    $objek = [
        "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
        "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
        "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n",
    ];

    $pdf = "%PDF-1.4\n";
    $offset = [];

    foreach ($objek as $i => $isi) {
        $offset[$i + 1] = strlen($pdf);
        $pdf .= $isi;
    }

    $posisiXref = strlen($pdf);

    $pdf .= "xref\n0 " . (count($objek) + 1) . "\n0000000000 65535 f \n";

    for ($i = 1; $i <= count($objek); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offset[$i]);
    }

    return $pdf
        . "trailer\n<< /Size " . (count($objek) + 1) . " /Root 1 0 R >>\n"
        . "startxref\n{$posisiXref}\n%%EOF";
}

/** Order + satu file dengan layanan tertentu, untuk halaman konfirmasi. */
function orderKilatUji(string $layanan, ?Computer $komputer, string $status = 'pending'): Order
{
    $orderId = Str::uuid()->getBytes();

    DB::table('orders')->insert([
        'id' => $orderId,
        'tanggal_pesan' => now(),
        'tanggal_ambil' => now()->addDay(),
        'biaya_admin' => 2000,
        'total_pembayaran' => 57000,
        'total_dibayar' => 0,
        'status_pembayaran' => $status,
        'metode_pembayaran' => $status === 'processing' ? 'kasir' : null,
        'computer_id' => $komputer?->id,
    ]);

    DB::table('files')->insert([
        'id' => Str::uuid()->getBytes(),
        'name' => 'dokumen-kilat.pdf',
        'path' => 'orders/uji/dokumen-kilat.pdf',
        'size' => 409600,
        'mime' => 'application/pdf',
        'total_pages' => 12,
        'order_id' => $orderId,
        'dihapus' => false,
        'subtotal' => 55000,
        'layanan' => $layanan,
    ]);

    return Order::findOrFail($orderId);
}

test('QR komputer yang valid diingat di session setelah dipindai', function () {
    $komputer = Computer::factory()->create();
    $token = (new tokenComputer())->createToken(['computerid' => bin2hex($komputer->id)]);

    $this->getJson(route('verified.token', ['token' => $token]))
        ->assertOk()
        ->assertJsonPath('computerid', bin2hex($komputer->id))
        ->assertSessionHas(KilatController::COMPUTER_SESSION_KEY, bin2hex($komputer->id));
});

test('QR komputer kedaluwarsa tidak mengubah komputer di session', function () {
    $komputer = Computer::factory()->create();
    $token = (new tokenComputer())->createToken(['computerid' => bin2hex($komputer->id)], -10);

    $this->getJson(route('verified.token', ['token' => $token]))
        ->assertStatus(404)
        ->assertSessionMissing(KilatController::COMPUTER_SESSION_KEY);
});

test('upload kilat-edit menyimpan komputer hasil scan QR ke order', function () {
    Storage::fake('local');

    $komputer = Computer::factory()->create();

    $this->withSession([KilatController::COMPUTER_SESSION_KEY => bin2hex($komputer->id)])
        ->post(
            route('upload.terjadwal.store'),
            [
                'layanan' => 'kilat-edit',
                'files' => [UploadedFile::fake()->createWithContent('dokumen.pdf', pdfContohKilat())],
            ],
            ['Accept' => 'application/json']
        )
        ->assertOk();

    $orderId = session(UploadTerjadwalController::SESSION_KEY . '.orderid');
    $order = Order::findOrFail(hex2bin($orderId));

    expect($order->computer_id)->not->toBeNull()
        ->and(bin2hex($order->computer_id))->toBe(bin2hex($komputer->id));
});

test('upload terjadwal mengabaikan komputer sisa pemindaian QR kilat', function () {
    Storage::fake('local');

    $komputer = Computer::factory()->create();

    $this->withSession([KilatController::COMPUTER_SESSION_KEY => bin2hex($komputer->id)])
        ->post(
            route('upload.terjadwal.store'),
            [
                'layanan' => 'terjadwal',
                'files' => [UploadedFile::fake()->createWithContent('dokumen.pdf', pdfContohKilat())],
            ],
            ['Accept' => 'application/json']
        )
        ->assertOk();

    $orderId = session(UploadTerjadwalController::SESSION_KEY . '.orderid');
    $order = Order::findOrFail(hex2bin($orderId));

    expect($order->computer_id)->toBeNull();
});

test('konfirmasi kilat-edit menampilkan komputer tujuan yang dipindai', function () {
    $this->withoutVite();

    $komputer = Computer::factory()->create(['name' => 'PC Edit 1']);
    $order = orderKilatUji('kilat-edit', $komputer);

    $this->get(route('order.konfirmasi', ['order_id' => bin2hex($order->id)]))
        ->assertOk()
        ->assertSee('Komputer untuk edit')
        ->assertSee('PC Edit 1')
        ->assertSee('Bayar di Kasir');
});

test('menunggu kasir kilat-edit menyebut nama komputer, bukan hanya menunggu kasir', function () {
    $this->withoutVite();

    $komputer = Computer::factory()->create(['name' => 'PC Edit 1']);
    $order = orderKilatUji('kilat-edit', $komputer, 'processing');

    $this->get(route('order.konfirmasi', ['order_id' => bin2hex($order->id)]))
        ->assertOk()
        ->assertSee('Silakan menuju')
        ->assertSee('PC Edit 1')
        ->assertSee('Komputer: PC Edit 1')
        ->assertDontSee('Menunggu konfirmasi kasir');
});

test('menunggu kasir kilat-edit tanpa data komputer tetap memakai pesan lama', function () {
    $this->withoutVite();

    $order = orderKilatUji('kilat-edit', null, 'processing');

    $this->get(route('order.konfirmasi', ['order_id' => bin2hex($order->id)]))
        ->assertOk()
        ->assertSee('Silakan bayar di kasir')
        ->assertSee('Menunggu konfirmasi kasir')
        ->assertDontSee('Komputer untuk edit');
});

test('pesan bayar di kasir kilat-edit menyebut komputer tujuan dan harga perkiraan', function () {
    $komputer = Computer::factory()->create(['name' => 'PC Edit 1']);
    $order = orderKilatUji('kilat-edit', $komputer);
    $hex = bin2hex($order->id);

    $this->withSession(['terjadwal_order' => ['orderid' => $hex]])
        ->postJson(route('order.konfirmasi.kasir', ['order_id' => $hex]))
        ->assertOk()
        ->assertJsonPath('status', 'processing')
        ->assertJsonPath('komputer', 'PC Edit 1')
        ->assertJsonPath('message', function (string $pesan) {
            return str_contains($pesan, 'PC Edit 1') && str_contains($pesan, 'perkiraan');
        });
});

test('pesan bayar di kasir kilat-langsung tidak menyebut komputer', function () {
    $komputer = Computer::factory()->create(['name' => 'PC Edit 1']);
    $order = orderKilatUji('kilat-langsung', $komputer);
    $hex = bin2hex($order->id);

    $this->withSession(['terjadwal_order' => ['orderid' => $hex]])
        ->postJson(route('order.konfirmasi.kasir', ['order_id' => $hex]))
        ->assertOk()
        ->assertJsonPath('komputer', null)
        ->assertJsonPath('message', fn (string $pesan) => !str_contains($pesan, 'PC Edit 1'));
});
