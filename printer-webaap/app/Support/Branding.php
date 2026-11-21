<?php

namespace App\Support;

use App\Models\Toko;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Identitas toko (nama + logo + ikon tab) yang dipakai header semua halaman
 * customer.
 *
 * Disimpan di `toko.name`, `toko.logo_path`, dan file `toko/favicon.ico`
 * (disk public). Di-cache singkat supaya setiap halaman tidak selalu query;
 * halaman Pengaturan memanggil `lupakan()` setelah menyimpan perubahan.
 */
class Branding
{
    public const CACHE_KEY = 'branding:toko';

    /** Lokasi ikon tab browser di disk public. */
    public const FAVICON_PATH = 'toko/favicon.ico';

    /**
     * @return array{nama: string, logo_path: ?string, logo_url: ?string, favicon_url: ?string}
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
                    'favicon_url' => self::faviconUrl(),
                ];
            });
        } catch (\Throwable $e) {
            // Tabel belum siap (mis. saat migrasi) atau database bermasalah:
            // halaman tetap bisa dirender dengan identitas bawaan.
            return self::bawaan();
        }
    }

    /**
     * URL ikon tab, dengan penanda versi supaya browser tidak memakai cache lama.
     */
    public static function faviconUrl(): ?string
    {
        try {
            if (! Storage::disk('public')->exists(self::FAVICON_PATH)) {
                return null;
            }

            return asset('storage/' . self::FAVICON_PATH)
                . '?v=' . Storage::disk('public')->lastModified(self::FAVICON_PATH);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{nama: string, logo_path: ?string, logo_url: ?string, favicon_url: ?string}
     */
    public static function bawaan(): array
    {
        return [
            'nama' => 'Nama Print',
            'logo_path' => null,
            'logo_url' => null,
            'favicon_url' => null,
        ];
    }

    public static function lupakan(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
