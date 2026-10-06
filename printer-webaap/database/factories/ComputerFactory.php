<?php

namespace Database\Factories;

use App\Models\Computer;
use App\Models\Toko;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Computer>
 */
class ComputerFactory extends Factory
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
            'name' => 'PC-' . fake()->unique()->numberBetween(1, 100),
            'token' => random_bytes(16),
            'toko_id' => Toko::factory(),
            'status' => fake()->randomElement(['not connected', 'pending', 'connected', 'shutdown', 'warning']),
        ];
    }
}
