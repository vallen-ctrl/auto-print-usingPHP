<?php

namespace Database\Factories;

use App\Models\FileQueue;
use App\Models\Order;
use App\Models\File;
use App\Models\Computer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileQueue>
 */
class FileQueueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed', 'downloading']),
            'path' => '/storage/print/' . fake()->uuid() . '.pdf',
            'order_id' => Order::factory(),
            'file_id' => File::factory(),
            'computer_id' => Computer::factory(),
        ];
    }
}
