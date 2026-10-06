<?php

use App\Models\Toko;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

test('can create toko', function () {
    $response = postJson('/api/toko', [
        'name' => 'Toko Print Sejahtera',
        'username' => 'tokoprint123',
        'password' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'message']);

    $this->assertDatabaseHas('toko', [
        'name' => 'Toko Print Sejahtera',
        'username' => 'tokoprint123',
    ]);
});

test('can login toko and get token', function () {
    $toko = Toko::factory()->create([
        'username' => 'tokoprint123',
        'password' => 'password123',
    ]);

    $response = postJson('/api/toko/login', [
        'username' => 'tokoprint123',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['valid', 'token'])
        ->assertJson(['valid' => true]);

    $token = $response->json('token');
    expect($token)->not->toBeNull();
});

test('rejects invalid login', function () {
    Toko::factory()->create([
        'username' => 'tokoprint123',
        'password' => 'password123',
    ]);

    $response = postJson('/api/toko/login', [
        'username' => 'tokoprint123',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson(['valid' => false]);
});

test('can validate token', function () {
    $toko = Toko::factory()->create();
    $token = bin2hex(random_bytes(32));
    $toko->update(['token' => hex2bin($token)]);

    $response = postJson('/api/toko/validate-token', [
        'toko_id' => bin2hex($toko->id),
        'token' => $token,
    ]);

    $response->assertStatus(200)
        ->assertJson(['valid' => true]);
});

test('rejects invalid token', function () {
    $toko = Toko::factory()->create();
    $toko->update(['token' => hex2bin(bin2hex(random_bytes(32)))]);

    $response = postJson('/api/toko/validate-token', [
        'toko_id' => bin2hex($toko->id),
        'token' => 'invalid-token',
    ]);

    $response->assertStatus(200)
        ->assertJson(['valid' => false]);
});

test('can get toko info', function () {
    Toko::factory()->count(2)->create();

    $response = getJson('/api/toko/info');

    $response->assertStatus(200)
        ->assertJsonCount(2);
});

test('can get toko ids', function () {
    Toko::factory()->count(2)->create();

    $response = getJson('/api/toko/ids');

    $response->assertStatus(200)
        ->assertJsonCount(2);
});

test('can set token', function () {
    $toko = Toko::factory()->create();

    $response = postJson("/api/toko/{bin2hex($toko->id)}/token");

    $response->assertStatus(200)
        ->assertJsonStructure(['token']);
});

test('can update toko', function () {
    $toko = Toko::factory()->create();

    $response = putJson("/api/toko/{bin2hex($toko->id)}", [
        'name' => 'Toko Baru',
        'username' => 'tokobaru123',
        'password' => 'newpassword123',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('toko', [
        'id' => $toko->id,
        'name' => 'Toko Baru',
    ]);
});

test('check max returns boolean', function () {
    $response = getJson('/api/toko/max');

    $response->assertStatus(200)
        ->assertJsonStructure(['max']);
});

test('validates required fields on create', function () {
    $response = postJson('/api/toko', []);

    $response->assertStatus(422);
});

test('validates unique username', function () {
    Toko::factory()->create(['username' => 'tokoprint123']);

    $response = postJson('/api/toko', [
        'name' => 'Toko Lain',
        'username' => 'tokoprint123',
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
});

