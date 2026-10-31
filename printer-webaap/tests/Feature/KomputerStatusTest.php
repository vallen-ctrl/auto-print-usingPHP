<?php

use App\Models\Computer;
use App\Models\Toko;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Session admin seperti hasil loginControler::login + token toko yang cocok. */
function sesiDashboard(): array
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

/** Satu baris antrean untuk komputer tertentu (order_id wajib, file_id boleh null). */
function antrean(Computer $komputer, string $status, string $path): void
{
    static $orderId = null;

    $orderId ??= Str::uuid()->getBytes();

    $sudahAda = DB::table('orders')->where('id', $orderId)->exists();
    if (! $sudahAda) {
        DB::table('orders')->insert([
            'id' => $orderId,
            'tanggal_ambil' => now(),
        ]);
    }

    DB::table('file_queue')->insert([
        'order_id' => $orderId,
        'file_id' => null,
        'computer_id' => $komputer->id,
        'path' => $path,
        'status' => $status,
    ]);
}

test('status-all mengembalikan status koneksi dan jumlah antrean per komputer', function () {
    $session = sesiDashboard();
    $komputer = Computer::factory()->create(['status' => 'connected']);

    antrean($komputer, 'pending', 'a.pdf');
    antrean($komputer, 'pending', 'b.pdf');
    antrean($komputer, 'completed', 'c.pdf');
    antrean($komputer, 'failed', 'd.pdf');

    $response = $this->withSession($session)->getJson(route('komputer.status.all'));

    $response->assertOk()
        ->assertJsonPath('computers.0.id', bin2hex($komputer->id))
        ->assertJsonPath('computers.0.status', 'connected')
        ->assertJsonPath('computers.0.queue.pending', 2)
        ->assertJsonPath('computers.0.queue.processing', 0)
        ->assertJsonPath('computers.0.queue.completed', 1)
        ->assertJsonPath('computers.0.queue.failed', 1)
        ->assertJsonPath('computers.0.queue.downloading', 0);

    expect($response->json('server_time'))->not->toBeNull();
});

test('status-all ikut berubah begitu status komputer di database berubah', function () {
    $session = sesiDashboard();
    $komputer = Computer::factory()->create(['status' => 'not connected']);

    $this->withSession($session)->getJson(route('komputer.status.all'))
        ->assertJsonPath('computers.0.status', 'not connected');

    // disimulasikan seperti yang dilakukan endpoint SSE saat client tersambung
    $komputer->update(['status' => 'connected']);

    $this->withSession($session)->getJson(route('komputer.status.all'))
        ->assertJsonPath('computers.0.status', 'connected');

    $komputer->update(['status' => 'shutdown']);

    $this->withSession($session)->getJson(route('komputer.status.all'))
        ->assertJsonPath('computers.0.status', 'shutdown');
});

test('files/status mengembalikan jumlah antrean per komputer', function () {
    $session = sesiDashboard();
    $komputer = Computer::factory()->create();
    $kosong = Computer::factory()->create();

    antrean($komputer, 'pending', 'a.pdf');
    antrean($komputer, 'processing', 'b.pdf');
    antrean($komputer, 'completed', 'c.pdf');
    antrean($komputer, 'failed', 'd.pdf');

    $response = $this->withSession($session)->postJson(route('komputer.get.filestatus'));

    $response->assertOk()
        ->assertJsonPath('data.' . bin2hex($komputer->id) . '.pending', 1)
        ->assertJsonPath('data.' . bin2hex($komputer->id) . '.processing', 1)
        ->assertJsonPath('data.' . bin2hex($komputer->id) . '.completed', 1)
        ->assertJsonPath('data.' . bin2hex($komputer->id) . '.failed', 1)
        ->assertJsonPath('data.' . bin2hex($kosong->id) . '.pending', 0)
        ->assertJsonPath('data.' . bin2hex($kosong->id) . '.completed', 0);

    expect($response->json('server_time'))->not->toBeNull();
});

test('files/status butuh login admin', function () {
    $this->postJson(route('komputer.get.filestatus'))->assertUnauthorized();
});

test('isActive membaca status komputer dari id hex', function () {
    $session = sesiDashboard();
    $komputer = Computer::factory()->create(['status' => 'connected']);

    $this->withSession($session)
        ->getJson(route('komputer.status', ['comid' => bin2hex($komputer->id)]))
        ->assertOk()
        ->assertJsonPath('status', 'connected');
});

test('isActive menjawab not connected untuk id yang tidak dikenal', function () {
    $session = sesiDashboard();

    $this->withSession($session)
        ->getJson(route('komputer.status', ['comid' => str_repeat('ab', 16)]))
        ->assertOk()
        ->assertJsonPath('status', 'not connected');

    $this->withSession($session)
        ->getJson(route('komputer.status', ['comid' => 'bukan-hex']))
        ->assertOk()
        ->assertJsonPath('status', 'not connected');
});

test('halaman komputer menandai tiap baris dengan id untuk polling', function () {
    $session = sesiDashboard();
    $komputer = Computer::factory()->create(['status' => 'connected', 'name' => 'PC Kasir']);
    antrean($komputer, 'pending', 'x.pdf');

    $this->withSession($session)
        ->get(route('komputer.index'))
        ->assertOk()
        ->assertSee('data-computer-id="' . bin2hex($komputer->id) . '"', false)
        ->assertSee('data-queue="pending"', false)
        ->assertSee('PC Kasir');
});

test('endpoint status komputer butuh login admin', function () {
    $this->getJson(route('komputer.status.all'))->assertUnauthorized();
    $this->get(route('komputer.index'))->assertRedirect(route('admin.login'));
});

test('hapus komputer memindahkan antrean pending/failed ke komputer lain', function () {
    $session = sesiDashboard();
    $sumber = Computer::factory()->create();
    $tujuan = Computer::factory()->create();

    antrean($sumber, 'pending', 'a.pdf');
    antrean($sumber, 'failed', 'b.pdf');
    antrean($sumber, 'completed', 'c.pdf');

    $this->withSession($session)
        ->delete(route('komputer.destroy', bin2hex($sumber->id)))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Computer::find($sumber->id))->toBeNull();
    expect(DB::table('file_queue')->where('computer_id', $sumber->id)->count())->toBe(0);
    expect(DB::table('file_queue')->where('computer_id', $tujuan->id)->count())->toBe(2);
});

test('hapus komputer ditolak bila tidak ada komputer lain untuk memindahkan file', function () {
    $session = sesiDashboard();
    $satuSatunya = Computer::factory()->create();

    antrean($satuSatunya, 'pending', 'a.pdf');

    $this->withSession($session)
        ->delete(route('komputer.destroy', bin2hex($satuSatunya->id)))
        ->assertRedirect()
        ->assertSessionHas('warning');

    expect(Computer::find($satuSatunya->id))->not->toBeNull();
});

test('retry mengembalikan file failed dan macet ke antrean pending', function () {
    $session = sesiDashboard();
    $komputer = Computer::factory()->create(['name' => 'PC Kasir']);

    antrean($komputer, 'failed', 'a.pdf');
    antrean($komputer, 'processing', 'b.pdf');
    antrean($komputer, 'completed', 'c.pdf');

    $this->withSession($session)
        ->put(route('komputer.file.retry', bin2hex($komputer->id)))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(DB::table('file_queue')->where('computer_id', $komputer->id)->where('status', 'pending')->count())->toBe(2);
    expect(DB::table('file_queue')->where('computer_id', $komputer->id)->where('status', 'completed')->count())->toBe(1);
});

test('id komputer yang bukan hex dijawab 404, bukan error 500', function () {
    $session = sesiDashboard();

    $this->withSession($session)
        ->delete('/dashboard/komputer/bukan-hex')
        ->assertNotFound();

    $this->withSession($session)
        ->put('/dashboard/komputer/files/retry/bukan-hex')
        ->assertNotFound();
});
