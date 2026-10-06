<?php

use App\Models\Layanan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

test('can create layanan', function () {
    $response = postJson('/api/layanan', [
        'nama' => 'Fotocopy',
        'deskripsi' => 'Fotocopy hitam putih',
        'harga' => 500,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'nama', 'deskripsi', 'harga', 'tanggal_dibuat']);

    $this->assertDatabaseHas('layanan', [
        'nama' => 'Fotocopy',
        'harga' => 500,
    ]);
});

test('can get all layanan', function () {
    Layanan::factory()->count(3)->create();

    $response = getJson('/api/layanan');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('can get layanan by id', function () {
    $layanan = Layanan::factory()->create();

    $response = getJson("/api/layanan?id={$layanan->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $layanan->id]);
});

test('can update layanan', function () {
    $layanan = Layanan::factory()->create();

    $response = putJson("/api/layanan/{$layanan->id}", [
        'nama' => 'Print Warna',
        'deskripsi' => 'Print berkualitas',
        'harga' => 2000,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('layanan', [
        'id' => $layanan->id,
        'nama' => 'Print Warna',
    ]);
});

test('can delete layanan', function () {
    $layanan = Layanan::factory()->create();

    $response = deleteJson("/api/layanan/{$layanan->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('layanan', ['id' => $layanan->id]);
});

test('validates required fields', function () {
    $response = postJson('/api/layanan', []);

    $response->assertStatus(422);
});

