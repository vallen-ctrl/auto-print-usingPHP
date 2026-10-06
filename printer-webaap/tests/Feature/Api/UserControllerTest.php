<?php

use App\Models\User;
use function Pest\Laravel\postJson;

test('can create user', function () {
    $response = postJson('/api/user', [
        'sessionid' => 'test-session-' . uniqid(),
        'name' => 'John Doe',
        'no_wa' => '085383895994',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['sessionid', 'name', 'no_wa', 'create_at']);

    $this->assertDatabaseHas('user', [
        'name' => 'John Doe',
        'no_wa' => '085383895994',
    ]);
});

test('validates required fields', function () {
    $response = postJson('/api/user', []);

    $response->assertStatus(422);
});

test('validates no_wa minimum length', function () {
    $response = postJson('/api/user', [
        'sessionid' => 'test-session-' . uniqid(),
        'name' => 'John Doe',
        'no_wa' => '123',
    ]);

    $response->assertStatus(422);
});

test('trims no_wa hyphens', function () {
    $sessionId = 'test-session-' . uniqid();
    $response = postJson('/api/user', [
        'sessionid' => $sessionId,
        'name' => 'Jane Doe',
        'no_wa' => '0853-8389-5994',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('user', [
        'sessionid' => $sessionId,
        'no_wa' => '085383895994',
    ]);
});

