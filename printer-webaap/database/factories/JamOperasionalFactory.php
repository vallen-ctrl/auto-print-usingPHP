<?php

namespace Database\Factories;

use App\Models\JamOperasional;
use App\Models\PengaturanToko;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JamOperasional>
 */
class JamOperasionalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nomorHari = fake()->numberBetween(1, 7);
        $days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        
        return [
            'nomor_hari' => $nomorHari,
            'hari' => $days[$nomorHari - 1],
            'waktu_buka' => '08:00:00',
            'waktu_tutup' => '17:00:00',
            'apakah_dibuka' => true,
            'id_pengaturan' => PengaturanToko::factory(),
        ];
    }
}
