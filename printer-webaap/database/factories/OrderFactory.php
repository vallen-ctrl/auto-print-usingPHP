<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\PengaturanToko;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->getBytes(),
            'tanggal_ambil' => fake()->dateTimeBetween('now', '+30 days'),
            'biaya_admin' => fake()->numberBetween(1000, 5000),
            'total_pembayaran' => fake()->numberBetween(10000, 100000),
            'total_dibayar' => 0,
            'pengaturan_id' => PengaturanToko::factory(),
            'status_pembayaran' => fake()->randomElement(['pending', 'proccesing ', 'succed', 'fail']),
            'metode_pembayaran' => null,
            'id_metode_pembayaran' => null,
            'session_id' => User::factory(),
        ];
    }
}
