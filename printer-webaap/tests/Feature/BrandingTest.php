<?php

use App\Models\Toko;
use App\Support\Branding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/** Toko + session admin yang cocok untuk halaman/pengaturan. */
function sesiPengaturan(array $tokoUbah = []): array
{
    $token = random_bytes(16);
    $toko = Toko::factory()->create(array_merge([
        'name' => 'Toko Lama',
        'token' => $token,
    ], $tokoUbah));

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

/** Payload pengaturan lengkap (format multipart). */
function payloadPengaturan(array $ubah = []): array
{
    return array_merge([
        'nama_toko' => 'Toko Baru',
        'status_buka' => '1',
        'biaya_admin' => 4000,
        'biaya_layanan' => 1000,
        'lama_file_dihapus' => 5,
        'jam' => [[
            'nomor_hari' => 1,
            'hari' => 'Senin',
            'apakah_dibuka' => '1',
            'waktu_buka' => '08:00',
            'waktu_tutup' => '17:00',
        ]],
    ], $ubah);
}

/** PNG 1x1 valid (tanpa butuh ekstensi GD). */
function logoPng(): UploadedFile
{
    $png = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z1ZkAAAAASUVORK5CYII='
    );

    return UploadedFile::fake()->createWithContent('logo-toko.png', $png);
}

test('halaman pengaturan menampilkan nama toko dan logo yang tersimpan', function () {
    $this->withoutVite();
    Storage::fake('public');

    $session = sesiPengaturan(['name' => 'Toko Logo']);
    $toko = Toko::first();
    $toko->update(['logo_path' => 'toko/logo/logo.png']);

    $this->withSession($session)
        ->get(route('pengaturan.index'))
        ->assertOk()
        ->assertSee('value="Toko Logo"', false)
        ->assertSee('storage/toko/logo/logo.png');
});

test('menyimpan nama toko dan logo dari halaman pengaturan', function () {
    Storage::fake('public');

    $session = sesiPengaturan();

    $this->withSession($session)
        ->post(route('pengaturan.store'), payloadPengaturan([
            'logo' => logoPng(),
        ]), ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('nama_toko', 'Toko Baru')
        ->assertJsonPath('message', 'Pengaturan berhasil disimpan.');

    $toko = Toko::firstOrFail();

    expect($toko->name)->toBe('Toko Baru')
        ->and($toko->logo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($toko->logo_path);

    $branding = Branding::get();

    expect($branding['nama'])->toBe('Toko Baru')
        ->and($branding['logo_url'])->toContain($toko->logo_path);
});

test('mengganti logo menghapus file logo lama', function () {
    Storage::fake('public');
    Storage::disk('public')->put('toko/logo/lama.png', 'isi');

    $session = sesiPengaturan();
    Toko::first()->update(['logo_path' => 'toko/logo/lama.png']);

    $this->withSession($session)
        ->post(route('pengaturan.store'), payloadPengaturan([
            'nama_toko' => 'Toko Lama',
            'logo' => logoPng(),
        ]), ['Accept' => 'application/json'])
        ->assertOk();

    $toko = Toko::firstOrFail();

    expect($toko->logo_path)->not->toBe('toko/logo/lama.png');
    Storage::disk('public')->assertMissing('toko/logo/lama.png');
    Storage::disk('public')->assertExists($toko->logo_path);
});

test('header halaman customer memakai nama dan logo dari pengaturan', function () {
    Storage::fake('public');
    Storage::disk('public')->put('toko/logo/logo.png', 'isi');

    Toko::factory()->create([
        'name' => 'Print Kilat Uji',
        'logo_path' => 'toko/logo/logo.png',
    ]);

    Branding::lupakan();

    $this->get('/')
        ->assertOk()
        ->assertSee('Print Kilat Uji')
        ->assertSee('storage/toko/logo/logo.png');

    $this->get(route('scan.qr'))
        ->assertOk()
        ->assertSee('Print Kilat Uji')
        ->assertSee('storage/toko/logo/logo.png');
});

test('nama toko wajib diisi saat menyimpan pengaturan', function () {
    $session = sesiPengaturan();

    $this->withSession($session)
        ->post(route('pengaturan.store'), payloadPengaturan(['nama_toko' => '']), ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('nama_toko');
});

test('logo harus berupa gambar dengan ukuran wajar', function () {
    Storage::fake('public');

    $session = sesiPengaturan();

    $this->withSession($session)
        ->post(route('pengaturan.store'), payloadPengaturan([
            'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
        ]), ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('logo');
});
