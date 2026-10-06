<?php

use App\Models\PengaturanToko;
use App\Models\Toko;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;

test('can create pengaturan toko', function () {
    $toko = Toko::factory()->create();

    $response = postJson('/api/pengaturan-toko', [
        'id_toko' => bin2hex($toko->id),
        'biaya_admin' => 2000,
        'biaya_layanan' => 1000,
        'lama_hari' => 2,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'status_buka', 'biaya_admin', 'biaya_layanan', 'id_toko', 'lama_file_dihapus']);

    $this->assertDatabaseHas('pengaturan_toko', [
        'id_toko' => $toko->id,
        'biaya_admin' => 2000,
    ]);
});

test('can get pengaturan by toko id', function () {
    $toko = Toko::factory()->create();
    $pengaturan = PengaturanToko::factory()->create(['id_toko' => $toko->id]);

    $response = getJson("/api/pengaturan-toko/toko/{bin2hex($toko->id)}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $pengaturan->id]);
});

test('can get biaya admin dan layanan', function () {
    PengaturanToko::factory()->create(['id' => 1, 'biaya_admin' => 2000, 'biaya_layanan' => 1000]);

    $response = getJson('/api/pengaturan-toko/biaya');

    $response->assertStatus(200)
        ->assertJson(['biaya_admin' => 2000, 'biaya_layanan' => 1000]);
});

test('can delete pengaturan toko', function () {
    $toko = Toko::factory()->create();
    $pengaturan = PengaturanToko::factory()->create(['id_toko' => $toko->id]);

    $response = deleteJson("/api/pengaturan-toko/{$pengaturan->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('pengaturan_toko', ['id' => $pengaturan->id]);
});

test('validates required fields', function () {
    $response = postJson('/api/pengaturan-toko', []);

    $response->assertStatus(422);
});

test('validates non-negative values', function () {
    $toko = Toko::factory()->create();

    $response = postJson('/api/pengaturan-toko', [
        'id_toko' => bin2hex($toko->id),
        'biaya_admin' => -1,
        'biaya_layanan' => 1000,
        'lama_hari' => 2,
    ]);

    $response->assertStatus(422);
});

