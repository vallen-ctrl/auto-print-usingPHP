<?php

use App\Models\File;
use App\Models\Order;
use App\Models\Toko;
use App\Service\KedaluwarsaOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Order untuk pengujian worker pembayaran. */
function buatOrderWorker(
    string $status = 'pending',
    ?Carbon $tanggalPesan = null,
    ?Carbon $tanggalBayar = null,
    string $metode = 'qris'
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
        'metode_pembayaran' => $metode,
    ]);

    return Order::findOrFail($id);
}

/** File upload + satu baris antrean milik order. */
function fileOrderWorker(Order $order, string $path, string $statusAntrean = 'pending'): File
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

/** Ubah pengaturan "file dihapus dalam ... hari" (pengaturan_toko id 1). */
function aturLamaSimpanFile(int $hari): void
{
    if (DB::table('pengaturan_toko')->where('id', 1)->exists()) {
        DB::table('pengaturan_toko')->where('id', 1)->update(['lama_file_dihapus' => $hari]);

        return;
    }

    DB::table('pengaturan_toko')->insert([
        'id' => 1,
        'status_buka' => true,
        'biaya_admin' => 0,
        'biaya_layanan' => 0,
        'id_toko' => Toko::factory()->create()->id,
        'lama_file_dihapus' => $hari,
    ]);
}

test('worker menggagalkan order yang lewat batas unggah dan menghapus filenya', function () {
    Storage::fake('local');

    $order = buatOrderWorker('pending', now()->subMinutes(KedaluwarsaOrder::BATAS_UPLOAD_MENIT + 5));
    Storage::disk('local')->put('worker/unggah-lama.pdf', 'isi pdf');
    $file = fileOrderWorker($order, 'worker/unggah-lama.pdf');

    $this->artisan('pembayaran:cek')->assertSuccessful();

    expect($order->fresh()->status_pembayaran)->toBe('failed');
    expect(File::find($file->id))->toBeNull();
    expect(DB::table('file_queue')->where('file_id', $file->id)->count())->toBe(0);
    Storage::disk('local')->assertMissing('worker/unggah-lama.pdf');
});

test('worker menggagalkan order 20 menit setelah menekan bayar', function () {
    Storage::fake('local');

    $order = buatOrderWorker(
        'pending',
        now()->subMinutes(40),
        now()->subMinutes(KedaluwarsaOrder::BATAS_BAYAR_MENIT + 1)
    );
    Storage::disk('local')->put('worker/bayar-lama.pdf', 'isi pdf');
    $file = fileOrderWorker($order, 'worker/bayar-lama.pdf');

    $this->artisan('pembayaran:cek')->assertSuccessful();

    expect($order->fresh()->status_pembayaran)->toBe('failed');
    expect(File::find($file->id))->toBeNull();
    Storage::disk('local')->assertMissing('worker/bayar-lama.pdf');
});

test('worker membiarkan order yang masih dalam batas waktu', function () {
    Storage::fake('local');

    $order = buatOrderWorker('pending', now()->subMinutes(30));
    Storage::disk('local')->put('worker/masih-baru.pdf', 'isi pdf');
    $file = fileOrderWorker($order, 'worker/masih-baru.pdf');

    $this->artisan('pembayaran:cek')->assertSuccessful();

    expect($order->fresh()->status_pembayaran)->toBe('pending');
    expect(File::find($file->id))->not->toBeNull();
    Storage::disk('local')->assertExists('worker/masih-baru.pdf');
});

test('worker membersihkan file order gagal yang masih tertinggal', function () {
    Storage::fake('local');

    $order = buatOrderWorker('failed', now()->subMinutes(40));
    Storage::disk('local')->put('worker/sisa-gagal.pdf', 'isi pdf');
    $file = fileOrderWorker($order, 'worker/sisa-gagal.pdf');

    $this->artisan('pembayaran:cek')->assertSuccessful();

    expect(File::find($file->id))->toBeNull();
    Storage::disk('local')->assertMissing('worker/sisa-gagal.pdf');
});

test('worker menghapus file order lunas yang melewati lama penyimpanan', function () {
    Storage::fake('local');
    aturLamaSimpanFile(2);

    $order = buatOrderWorker('paid', now()->subDays(5), now()->subDays(5));
    Storage::disk('local')->put('worker/lunas-lama.pdf', 'isi pdf');
    $file = fileOrderWorker($order, 'worker/lunas-lama.pdf', 'completed');

    $this->artisan('pembayaran:cek')->assertSuccessful();

    // Baris file tetap ada (riwayat/struk), file fisiknya yang dihapus.
    expect(File::find($file->id))->not->toBeNull()
        ->and((bool) File::find($file->id)->dihapus)->toBeTrue();
    Storage::disk('local')->assertMissing('worker/lunas-lama.pdf');
});

test('worker tidak menghapus file order lunas yang belum melewati lama penyimpanan', function () {
    Storage::fake('local');
    aturLamaSimpanFile(5);

    $order = buatOrderWorker('paid', now()->subDays(2), now()->subDays(2));
    Storage::disk('local')->put('worker/lunas-baru.pdf', 'isi pdf');
    $file = fileOrderWorker($order, 'worker/lunas-baru.pdf', 'completed');

    $this->artisan('pembayaran:cek')->assertSuccessful();

    expect((bool) File::find($file->id)->dihapus)->toBeFalse();
    Storage::disk('local')->assertExists('worker/lunas-baru.pdf');
});

test('worker tidak menghapus file yang masih menunggu dicetak', function () {
    Storage::fake('local');
    aturLamaSimpanFile(1);

    $order = buatOrderWorker('paid', now()->subDays(10), now()->subDays(10));
    Storage::disk('local')->put('worker/masih-antre.pdf', 'isi pdf');
    $file = fileOrderWorker($order, 'worker/masih-antre.pdf', 'pending');

    $this->artisan('pembayaran:cek')->assertSuccessful();

    expect((bool) File::find($file->id)->dihapus)->toBeFalse();
    Storage::disk('local')->assertExists('worker/masih-antre.pdf');
});
