<?php

use App\Models\Paper;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

test('can create paper', function () {
    $response = postJson('/api/paper', [
        'name' => 'A4',
        'width' => 210,
        'height' => 297,
        'harga_color' => 1000,
        'harga_grayscale' => 500,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'name', 'width', 'height', 'harga_color', 'harga_grayscale']);

    $this->assertDatabaseHas('paper', [
        'name' => 'A4',
        'width' => 210,
    ]);
});

test('can get all paper', function () {
    Paper::factory()->count(3)->create();

    $response = getJson('/api/paper');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('can get paper by id', function () {
    $paper = Paper::factory()->create();

    $response = getJson("/api/paper?id={$paper->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $paper->id]);
});

test('can update paper', function () {
    $paper = Paper::factory()->create();

    $response = putJson("/api/paper/{$paper->id}", [
        'name' => 'A3',
        'width' => 297,
        'height' => 420,
        'harga_color' => 2000,
        'harga_grayscale' => 1000,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('paper', [
        'id' => $paper->id,
        'name' => 'A3',
    ]);
});

test('can delete paper', function () {
    $paper = Paper::factory()->create();

    $response = deleteJson("/api/paper/{$paper->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('paper', ['id' => $paper->id]);
});

test('validates required fields', function () {
    $response = postJson('/api/paper', []);

    $response->assertStatus(422);
});

test('validates unique name', function () {
    Paper::factory()->create(['name' => 'A4']);

    $response = postJson('/api/paper', [
        'name' => 'A4',
        'width' => 210,
        'height' => 297,
        'harga_color' => 1000,
        'harga_grayscale' => 500,
    ]);

    $response->assertStatus(422);
});

