<?php

namespace App\Support;

use App\Models\Toko;
use Illuminate\Support\Facades\Cache;

/**
 * Identitas toko (nama + logo) yang dipakai header semua halaman customer.
 *
 * Disimpan di `toko.name` dan `toko.logo_path` (disk public). Di-cache singkat
 * supaya setiap halaman tidak selalu query; halaman Pengaturan memanggil
 * `lupakan()` setelah menyimpan perubahan.
 */
class Branding
{
    public const CACHE_KEY = 'branding:toko';

    /**
     * @return array{nama: string, logo_path: ?string, logo_url: ?string}
     */
    public static function get(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
                $toko = Toko::query()->select('name', 'logo_path')->first();
                $logo = $toko?->logo_path;

                return [
                    'nama' => $toko?->name ?: 'Nama Print',
                    'logo_path' => $logo,
                    'logo_url' => $logo ? asset('storage/' . $logo) : null,
                ];
            });
        } catch (\Throwable $e) {
            // Tabel belum siap (mis. saat migrasi) atau database bermasalah:
            // halaman tetap bisa dirender dengan identitas bawaan.
            return self::bawaan();
        }
    }

    /**
     * @return array{nama: string, logo_path: ?string, logo_url: ?string}
     */
    public static function bawaan(): array
    {
        return [
            'nama' => 'Nama Print',
            'logo_path' => null,
            'logo_url' => null,
        ];
    }

    public static function lupakan(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
