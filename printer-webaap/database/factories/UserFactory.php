<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sessionid' => 'session-' . Str::uuid(),
            'name' => fake()->name(),
            'no_wa' => '08' . fake()->numerify('##########'),
            'create_at' => now(),
        ];
    }
}
