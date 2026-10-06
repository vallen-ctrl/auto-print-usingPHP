<?php

use App\Models\JamOperasional;
use App\Models\PengaturanToko;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

test('can create jam operasional', function () {
    $pengaturan = PengaturanToko::factory()->create();

    $response = postJson('/api/jam-operasional', [
        'id_pengaturan' => $pengaturan->id,
        'hari_ke' => 1,
        'jam_buka' => '08:00:00',
        'jam_tutup' => '17:00:00',
        'apakah_buka' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'nomor_hari', 'hari', 'waktu_buka', 'waktu_tutup', 'apakah_dibuka', 'id_pengaturan']);

    $this->assertDatabaseHas('jam_operasional', [
        'id_pengaturan' => $pengaturan->id,
        'nomor_hari' => 1,
        'hari' => 'senin',
    ]);
});

test('can get all jam operasional', function () {
    $pengaturan = PengaturanToko::factory()->create();
    JamOperasional::factory()->count(3)->create(['id_pengaturan' => $pengaturan->id]);

    $response = getJson('/api/jam-operasional');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('can get jam operasional by pengaturan', function () {
    $pengaturan = PengaturanToko::factory()->create();
    JamOperasional::factory()->count(2)->create(['id_pengaturan' => $pengaturan->id]);

    $response = getJson("/api/jam-operasional/pengaturan/{$pengaturan->id}");

    $response->assertStatus(200)
        ->assertJsonCount(2);
});

test('can update jam operasional', function () {
    $pengaturan = PengaturanToko::factory()->create();
    $jam = JamOperasional::factory()->create(['id_pengaturan' => $pengaturan->id]);

    $response = putJson("/api/jam-operasional/{$jam->id}", [
        'jam_buka' => '09:00:00',
        'jam_tutup' => '18:00:00',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('jam_operasional', [
        'id' => $jam->id,
        'waktu_buka' => '09:00:00',
    ]);
});

test('can delete jam operasional', function () {
    $pengaturan = PengaturanToko::factory()->create();
    $jam = JamOperasional::factory()->create(['id_pengaturan' => $pengaturan->id]);

    $response = deleteJson("/api/jam-operasional/{$jam->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('jam_operasional', ['id' => $jam->id]);
});

test('validates time format', function () {
    $pengaturan = PengaturanToko::factory()->create();

    $response = postJson('/api/jam-operasional', [
        'id_pengaturan' => $pengaturan->id,
        'hari_ke' => 1,
        'jam_buka' => 'invalid-time',
        'jam_tutup' => '17:00:00',
        'apakah_buka' => true,
    ]);

    $response->assertStatus(422);
});

test('validates hari range', function () {
    $pengaturan = PengaturanToko::factory()->create();

    $response = postJson('/api/jam-operasional', [
        'id_pengaturan' => $pengaturan->id,
        'hari_ke' => 10,
        'jam_buka' => '08:00:00',
        'jam_tutup' => '17:00:00',
        'apakah_buka' => true,
    ]);

    $response->assertStatus(422);
});

test('updates hari name when nomor_hari changes', function () {
    $pengaturan = PengaturanToko::factory()->create();
    $jam = JamOperasional::factory()->create(['id_pengaturan' => $pengaturan->id, 'nomor_hari' => 1]);

    $response = putJson("/api/jam-operasional/{$jam->id}", [
        'nomor_hari' => 3,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('jam_operasional', [
        'id' => $jam->id,
        'nomor_hari' => 3,
        'hari' => 'rabu',
    ]);
});

