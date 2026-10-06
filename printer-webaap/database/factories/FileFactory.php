<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
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
            'name' => fake()->word() . '.pdf',
            'path' => '/storage/files/' . fake()->uuid() . '.pdf',
            'size' => fake()->numberBetween(100000, 10000000),
            'mime' => fake()->randomElement(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png']),
            'diupload_oleh' => User::factory(),
            'total_pages' => fake()->numberBetween(1, 100),
            'order_id' => Order::factory(),
            'dihapus' => false,
            'subtotal' => fake()->numberBetween(1000, 50000),
            'layanan' => fake()->randomElement(['kilat-edit', 'kilat-langsung', 'terjadwal']),
        ];
    }
}
