<?php

use App\Models\File;
use App\Models\Order;
use App\Models\User;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

test('can create file', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);

    $response = postJson('/api/file', [
        'name' => 'document.pdf',
        'path' => '/storage/files/document.pdf',
        'size' => 1024000,
        'mime' => 'application/pdf',
        'order_id' => bin2hex($order->id),
        'total_pages' => 5,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id']);

    $this->assertDatabaseHas('files', [
        'name' => 'document.pdf',
        'order_id' => $order->id,
    ]);
});

test('can get file by id', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);

    $response = getJson("/api/file/{bin2hex($file->id)}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => bin2hex($file->id)]);
});

test('can update uploader', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id, 'diupload_oleh' => null]);

    $response = putJson("/api/file/{bin2hex($file->id)}/uploader", [
        'session_id' => $user->sessionid,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('files', [
        'id' => $file->id,
        'diupload_oleh' => $user->sessionid,
    ]);
});

test('can update subtotal', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id, 'subtotal' => 0]);

    $response = putJson("/api/file/{bin2hex($file->id)}/subtotal", [
        'subtotal' => 5000,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('files', [
        'id' => $file->id,
        'subtotal' => 5000,
    ]);
});

test('can delete file', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);

    $response = deleteJson("/api/file/{bin2hex($file->id)}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('files', ['id' => $file->id]);
});

test('validates required fields', function () {
    $response = postJson('/api/file', []);

    $response->assertStatus(422);
});

test('rejects negative subtotal', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);

    $response = putJson("/api/file/{bin2hex($file->id)}/subtotal", [
        'subtotal' => -100,
    ]);

    $response->assertStatus(422);
});

