<?php

use App\Models\Order;
use App\Models\User;
use App\Models\PengaturanToko;
use App\Models\MetodePembayaran;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

test('can create order', function () {
    $user = User::factory()->create();
    $pengaturan = PengaturanToko::factory()->create();

    $response = postJson('/api/order', [
        'tanggal_ambil' => '2024-12-31T10:00:00+07:00',
        'biaya_admin' => 2000,
        'total_pembayaran' => 15000,
        'status_pembayaran' => 'pending',
        'session_id' => $user->sessionid,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id']);

    $this->assertDatabaseHas('orders', [
        'session_id' => $user->sessionid,
        'total_pembayaran' => 15000,
        'pengaturan_id' => 1,
    ]);
});

test('can update total dibayar', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'session_id' => $user->sessionid,
        'total_dibayar' => 0,
    ]);

    $response = putJson("/api/order/{bin2hex($order->id)}/total-dibayar", [
        'total_dibayar' => 15000,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'total_dibayar' => 15000,
    ]);
});

test('can update metode pembayaran', function () {
    $user = User::factory()->create();
    $pengaturan = PengaturanToko::factory()->create();
    $metode = MetodePembayaran::factory()->create(['id_pengaturan' => $pengaturan->id]);
    $order = Order::factory()->create(['session_id' => $user->sessionid]);

    $response = putJson("/api/order/{bin2hex($order->id)}/metode-pembayaran", [
        'metode_pembayaran' => 'QRIS',
        'id_metode_pembayaran' => bin2hex($metode->id),
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'metode_pembayaran' => 'QRIS',
        'id_metode_pembayaran' => $metode->id,
    ]);
});

test('can update status pembayaran', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'session_id' => $user->sessionid,
        'status_pembayaran' => 'pending',
    ]);

    $response = putJson("/api/order/{bin2hex($order->id)}/status-pembayaran", [
        'status_pembayaran' => 'succed',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status_pembayaran' => 'succed',
    ]);
});

test('validates required fields', function () {
    $response = postJson('/api/order', []);

    $response->assertStatus(422);
});

test('validates status pembayaran enum', function () {
    $user = User::factory()->create();

    $response = postJson('/api/order', [
        'tanggal_ambil' => '2024-12-31T10:00:00+07:00',
        'biaya_admin' => 2000,
        'total_pembayaran' => 15000,
        'status_pembayaran' => 'invalid-status',
        'session_id' => $user->sessionid,
    ]);

    $response->assertStatus(422);
});

test('rejects negative total dibayar', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['session_id' => $user->sessionid]);

    $response = putJson("/api/order/{bin2hex($order->id)}/total-dibayar", [
        'total_dibayar' => -100,
    ]);

    $response->assertStatus(422);
});

