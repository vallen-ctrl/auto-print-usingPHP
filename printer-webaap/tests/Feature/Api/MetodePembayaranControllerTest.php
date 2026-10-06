<?php

use App\Models\MetodePembayaran;
use App\Models\PengaturanToko;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

test('can create metode pembayaran', function () {
    $pengaturan = PengaturanToko::factory()->create();

    $response = postJson('/api/metode-pembayaran', [
        'pengaturan' => $pengaturan->id,
        'name' => 'QRIS',
        'webhook' => 'https://webhook.example.com',
        'token' => 'secret-token',
        'imagepath' => '/images/qris.png',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id']);

    $this->assertDatabaseHas('metode_pembayaran', [
        'nama' => 'QRIS',
        'id_pengaturan' => $pengaturan->id,
    ]);
});

test('can get all metode pembayaran', function () {
    $pengaturan = PengaturanToko::factory()->create();
    MetodePembayaran::factory()->count(3)->create(['id_pengaturan' => $pengaturan->id]);

    $response = getJson('/api/metode-pembayaran');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('can get metode pembayaran by id', function () {
    $pengaturan = PengaturanToko::factory()->create();
    $metode = MetodePembayaran::factory()->create(['id_pengaturan' => $pengaturan->id]);

    $response = getJson("/api/metode-pembayaran/{bin2hex($metode->id)}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => bin2hex($metode->id)]);
});

test('can update metode pembayaran', function () {
    $pengaturan = PengaturanToko::factory()->create();
    $metode = MetodePembayaran::factory()->create(['id_pengaturan' => $pengaturan->id]);

    $response = putJson("/api/metode-pembayaran/{bin2hex($metode->id)}", [
        'nama' => 'GoPay',
        'webhook' => 'https://webhook.gopay.com',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('metode_pembayaran', [
        'id' => $metode->id,
        'nama' => 'GoPay',
    ]);
});

test('can delete metode pembayaran', function () {
    $pengaturan = PengaturanToko::factory()->create();
    $metode = MetodePembayaran::factory()->create(['id_pengaturan' => $pengaturan->id]);

    $response = deleteJson("/api/metode-pembayaran/{bin2hex($metode->id)}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('metode_pembayaran', ['id' => $metode->id]);
});

test('validates required fields', function () {
    $response = postJson('/api/metode-pembayaran', []);

    $response->assertStatus(422);
});

test('validates id length', function () {
    $response = getJson('/api/metode-pembayaran/invalid-id');

    $response->assertStatus(500);
});

