<?php

namespace Database\Factories;

use App\Models\Paper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paper>
 */
class PaperFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = ['A4', 'A3', 'A5', 'Letter', 'Legal', 'F4'];
        return [
            'name' => fake()->unique()->randomElement($names),
            'width' => fake()->numberBetween(100, 500),
            'height' => fake()->numberBetween(100, 500),
            'harga_color' => fake()->numberBetween(500, 5000),
            'harga_grayscale' => fake()->numberBetween(200, 3000),
        ];
    }
}
