<?php

use App\Models\LayananTerpilih;
use App\Models\Layanan;
use App\Models\File;
use App\Models\Order;
use App\Models\User;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;

test('can create layanan terpilih', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $layanan = Layanan::factory()->create(['nama' => 'Print Warna']);

    $response = postJson('/api/layanan-terpilih', [
        'files_id' => bin2hex($file->id),
        'layanan_id' => $layanan->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'files_id', 'layanan_id', 'nama_layanan']);

    $this->assertDatabaseHas('layanan_terpilih', [
        'files_id' => $file->id,
        'layanan_id' => $layanan->id,
        'nama_layanan' => 'Print Warna',
    ]);
});

test('can get layanan terpilih by file id', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $layanan = Layanan::factory()->create();
    LayananTerpilih::factory()->count(2)->create([
        'files_id' => $file->id,
        'layanan_id' => $layanan->id,
    ]);

    $response = getJson('/api/layanan-terpilih', [
        'files_id' => bin2hex($file->id),
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(2);
});

test('can delete layanan terpilih', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);
    $file = File::factory()->create(['order_id' => $order->id]);
    $layanan = Layanan::factory()->create();
    $lt = LayananTerpilih::factory()->create([
        'files_id' => $file->id,
        'layanan_id' => $layanan->id,
    ]);

    $response = deleteJson("/api/layanan-terpilih/{$lt->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('layanan_terpilih', ['id' => $lt->id]);
});

test('validates required fields', function () {
    $response = postJson('/api/layanan-terpilih', []);

    $response->assertStatus(422);
});

