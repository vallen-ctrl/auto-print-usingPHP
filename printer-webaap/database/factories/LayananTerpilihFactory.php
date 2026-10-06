<?php

namespace Database\Factories;

use App\Models\LayananTerpilih;
use App\Models\File;
use App\Models\Layanan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LayananTerpilih>
 */
class LayananTerpilihFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $layanan = Layanan::factory()->create();
        
        return [
            'files_id' => File::factory(),
            'layanan_id' => $layanan->id,
            'nama_layanan' => $layanan->nama,
        ];
    }
}
