<?php

use App\Models\FileQueue;
use App\Models\File;
use App\Models\Order;
use App\Models\User;
use App\Models\Computer;
use App\Models\Toko;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

test('can create file queue', function () {
    $toko = Toko::factory()->create();
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $computer = Computer::factory()->create(['toko_id' => $toko->id]);

    $response = postJson('/api/file-queue', [
        'path' => '/storage/print/document.pdf',
        'order_id' => bin2hex($order->id),
        'file_id' => bin2hex($file->id),
        'computer_id' => bin2hex($computer->id),
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'status', 'path', 'order_id', 'file_id', 'computer_id']);

    $this->assertDatabaseHas('file_queue', [
        'path' => '/storage/print/document.pdf',
        'order_id' => $order->id,
        'file_id' => $file->id,
        'computer_id' => $computer->id,
        'status' => 'pending',
    ]);
});

test('can get all file queues', function () {
    $toko = Toko::factory()->create();
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $computer = Computer::factory()->create(['toko_id' => $toko->id]);
    FileQueue::factory()->count(3)->create([
        'order_id' => $order->id,
        'file_id' => $file->id,
        'computer_id' => $computer->id,
    ]);

    $response = getJson('/api/file-queue');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

test('can get file queue by computer id', function () {
    $toko = Toko::factory()->create();
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $computer = Computer::factory()->create(['toko_id' => $toko->id]);
    FileQueue::factory()->count(2)->create([
        'file_id' => $computer->id,
        'computer_id' => $computer->id,
    ]);

    $response = getJson("/api/file-queue/computer/{bin2hex($computer->id)}");

    $response->assertStatus(200)
        ->assertJsonCount(2);
});

test('can update file queue', function () {
    $toko = Toko::factory()->create();
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $computer = Computer::factory()->create(['toko_id' => $toko->id]);
    $queue = FileQueue::factory()->create([
        'order_id' => $order->id,
        'file_id' => $file->id,
        'computer_id' => $computer->id,
        'status' => 'pending',
    ]);

    $response = putJson("/api/file-queue/{$queue->id}", [
        'status' => 'processing',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('file_queue', [
        'id' => $queue->id,
        'status' => 'processing',
    ]);
});

test('can delete file queue', function () {
    $toko = Toko::factory()->create();
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $computer = Computer::factory()->create(['toko_id' => $toko->id]);
    $queue = FileQueue::factory()->create([
        'order_id' => $order->id,
        'file_id' => $file->id,
        'computer_id' => $computer->id,
    ]);

    $response = deleteJson("/api/file-queue/{$queue->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('file_queue', ['id' => $queue->id]);
});

test('validates required fields', function () {
    $response = postJson('/api/file-queue', []);

    $response->assertStatus(422);
});

