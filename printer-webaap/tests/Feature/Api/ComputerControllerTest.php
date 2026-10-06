<?php

use App\Models\Computer;
use App\Models\Toko;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

uses()->group('computer');

test('can create computer', function () {
    $toko = Toko::factory()->create();

    $response = postJson('/api/computer', [
        'name' => 'PC-001',
        'toko_id' => bin2hex($toko->id),
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['token', 'id']);

    $this->assertDatabaseHas('computer', [
        'name' => 'PC-001',
        'toko_id' => $toko->id,
    ]);
});

test('can get all computers', function () {
    $toko = Toko::factory()->create();
    Computer::factory()->count(3)->create(['toko_id' => $toko->id]);

    $response = getJson('/api/computer');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('can get computer by id', function () {
    $toko = Toko::factory()->create();
    $computer = Computer::factory()->create(['toko_id' => $toko->id]);
    $id = bin2hex($computer->id);

    $response = getJson("/api/computer/{$id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $id]);
});

test('can get computer by token', function () {
    $toko = Toko::factory()->create();
    $computer = Computer::factory()->create(['toko_id' => $toko->id]);
    $token = bin2hex($computer->token);

    $response = getJson("/api/computer/token?token={$token}");

    $response->assertStatus(200)
        ->assertJsonFragment(['token' => $token]);
});

test('can update computer status', function () {
    $toko = Toko::factory()->create();
    $computer = Computer::factory()->create(['toko_id' => $toko->id]);
    $id = bin2hex($computer->id);

    $response = putJson("/api/computer/{$id}/status", [
        'status' => 2,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('computer', [
        'id' => $computer->id,
        'status' => 'connected',
    ]);
});

test('can delete computer', function () {
    $toko = Toko::factory()->create();
    $computer = Computer::factory()->create(['toko_id' => $toko->id]);
    $id = bin2hex($computer->id);

    $response = deleteJson("/api/computer/{$id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('computer', ['id' => $computer->id]);
});

test('validates required fields', function () {
    $response = postJson('/api/computer', []);

    $response->assertStatus(422);
});

test('validates status range', function () {
    $toko = Toko::factory()->create();
    $computer = Computer::factory()->create(['toko_id' => $toko->id]);
    $id = bin2hex($computer->id);

    $response = putJson("/api/computer/{$id}/status", [
        'status' => 10,
    ]);

    $response->assertStatus(422);
});

