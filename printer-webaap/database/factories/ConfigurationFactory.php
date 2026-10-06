<?php

namespace Database\Factories;

use App\Models\Configuration;
use App\Models\File;
use App\Models\Paper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Configuration>
 */
class ConfigurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paper = Paper::factory()->create();
        $paperWidth = $paper->name . '|' . $paper->height . ' x ' . $paper->width;
        
        return [
            'file_id' => File::factory(),
            'orientasi' => fake()->randomElement(['potrait', 'landscape']),
            'warna' => fake()->randomElement(['berwarna', 'hitam putih']),
            'ukuran' => $paperWidth,
            'kualitas' => fake()->randomElement(['rendah', 'standar', 'tinggi']),
            'skala' => fake()->randomElement(['100%', '90%', '80%', 'Fit to page']),
            'jumlah_cetak' => fake()->numberBetween(1, 10),
            'custom_halaman' => fake()->optional()->numerify('##-##'),
            'catatan_tambahan' => fake()->optional()->sentence(),
            'paper_id' => $paper->id,
            'pages_toprint' => fake()->numberBetween(1, 50),
        ];
    }
}
