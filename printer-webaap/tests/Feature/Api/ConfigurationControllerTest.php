<?php

use App\Models\Configuration;
use App\Models\File;
use App\Models\Order;
use App\Models\User;
use App\Models\Paper;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\putJson;

test('can create configuration', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $paper = Paper::factory()->create([
        'name' => 'A4',
        'width' => 210,
        'height' => 297,
    ]);

    $response = postJson('/api/configuration', [
        'file_id' => bin2hex($file->id),
        'orientasi' => 'potrait',
        'warna' => 'berwarna',
        'kualitas' => 'standar',
        'skala' => '100%',
        'jumlah_cetak' => 2,
        'custom_halaman' => '1-5',
        'catatan_tambahan' => 'Test print',
        'pages_toprint' => 5,
        'paper_id' => $paper->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'file_id', 'orientasi', 'warna', 'ukuran', 'kualitas']);

    $this->assertDatabaseHas('configuration', [
        'file_id' => $file->id,
        'orientasi' => 'potrait',
        'warna' => 'berwarna',
        'paper_id' => $paper->id,
    ]);
});

test('can get configuration by file id', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $paper = Paper::factory()->create();
    Configuration::factory()->count(2)->create([
        'file_id' => $file->id,
        'paper_id' => $paper->id,
    ]);

    $response = getJson("/api/configuration/file/{bin2hex($file->id)}");

    $response->assertStatus(200)
        ->assertJsonCount(2);
});

test('can get configuration by id', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $paper = Paper::factory()->create();
    $config = Configuration::factory()->create([
        'file_id' => $file->id,
        'paper_id' => $paper->id,
    ]);

    $response = getJson("/api/configuration/{$config->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $config->id]);
});

test('can update configuration', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $paper = Paper::factory()->create();
    $config = Configuration::factory()->create([
        'file_id' => $file->id,
        'paper_id' => $paper->id,
    ]);

    $response = putJson("/api/configuration/{$config->id}", [
        'jumlah_cetak' => 5,
        'kualitas' => 'tinggi',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('configuration', [
        'id' => $config->id,
        'jumlah_cetak' => 5,
        'kualitas' => 'tinggi',
    ]);
});

test('can delete configuration', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $paper = Paper::factory()->create();
    $config = Configuration::factory()->create([
        'file_id' => $file->id,
        'paper_id' => $paper->id,
    ]);

    $response = deleteJson("/api/configuration/{$config->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('configuration', ['id' => $config->id]);
});

test('validates required fields', function () {
    $response = postJson('/api/configuration', []);

    $response->assertStatus(422);
});

test('validates enum values', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $paper = Paper::factory()->create();

    $response = postJson('/api/configuration', [
        'file_id' => bin2hex($file->id),
        'orientasi' => 'invalid',
        'warna' => 'berwarna',
        'kualitas' => 'standar',
        'jumlah_cetak' => 1,
        'pages_toprint' => 1,
        'paper_id' => $paper->id,
    ]);

    $response->assertStatus(422);
});

test('validates minimum jumlah_cetak', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $paper = Paper::factory()->create();

    $response = postJson('/api/configuration', [
        'file_id' => bin2hex($file->id),
        'orientasi' => 'potrait',
        'warna' => 'berwarna',
        'kualitas' => 'standar',
        'jumlah_cetak' => 0,
        'pages_toprint' => 1,
        'paper_id' => $paper->id,
    ]);

    $response->assertStatus(422);
});

