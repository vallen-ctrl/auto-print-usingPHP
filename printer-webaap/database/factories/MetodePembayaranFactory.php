<?php

namespace Database\Factories;

use App\Models\MetodePembayaran;
use App\Models\PengaturanToko;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MetodePembayaran>
 */
class MetodePembayaranFactory extends Factory
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
            'nama' => fake()->randomElement(['QRIS', 'GoPay', 'OVO', 'DANA', 'Bank Transfer']),
            'webhook' => fake()->url(),
            'token' => fake()->sha256(),
            'image_path' => '/images/' . fake()->word() . '.png',
            'id_pengaturan' => PengaturanToko::factory(),
        ];
    }
}
