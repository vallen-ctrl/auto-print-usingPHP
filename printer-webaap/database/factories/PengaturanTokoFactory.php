<?php

namespace Database\Factories;

use App\Models\PengaturanToko;
use App\Models\Toko;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengaturanToko>
 */
class PengaturanTokoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status_buka' => fake()->boolean(),
            'biaya_admin' => fake()->numberBetween(1000, 5000),
            'biaya_layanan' => fake()->numberBetween(500, 3000),
            'id_toko' => Toko::factory(),
            'lama_file_dihapus' => fake()->numberBetween(1, 7),
        ];
    }
}
