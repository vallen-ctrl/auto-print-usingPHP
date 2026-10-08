<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CustomHalamanRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $hasil = str_replace(' ', '', $value);
        // 1. Harus string
        if (!is_string($hasil)) {
            $fail("Format halaman harus berupa teks.");
            return;
        }

        // 2. Cek format dasar: 1, 1-5, 1,2,3, 1-3,5,7-9
        if (!preg_match('/^\d+(-\d+)?(,\d+(-\d+)?)*$/', $hasil)) {
            $fail("Format halaman tidak valid. Contoh: 1-2, 1,2,3, 1-5, 1,4-10");
            return;
        }

        // 3. Cek range terbalik (misal 10-2)
        foreach (explode(',', $hasil) as $part) {
            if (str_contains($part, '-')) {
                [$start, $end] = array_map('intval', explode('-', $part));

                if ($start > $end) {
                    $fail("Range '$part' tidak valid (start lebih besar dari end).");
                    return;
                }

                if ($start < 1) {
                    $fail("Nomor halaman harus minimal 1.");
                    return;
                }
            } else {
                if ((int) $part < 1) {
                    $fail("Nomor halaman harus minimal 1.");
                    return;
                }
            }
        }
    }

    /**
     * Normalize input jadi string range yang rapi.
     *
     * Contoh:
     *  - "2,9,2-10"  → "2-10"
     *  - "1,2,3"     → "1-3"
     *  - "1-5,3-7"   → "1-7"
     *  - "5,3,1,2,4" → "1-5"
     *  - "1-3,10,7-9"→ "1-3,7-10"
     */
    public static function normalize(string $input): string
    {
        $allPages = [];
        $hasil = str_replace(' ', '', $input);

        foreach (explode(',', $hasil) as $part) {
            $part = trim($part);

            if (str_contains($part, '-')) {
                [$start, $end] = array_map('intval', explode('-', $part));

                // Tukar kalau terbalik (biar aman walau validasi sudah cegah)
                if ($start > $end) {
                    [$start, $end] = [$end, $start];
                }

                $allPages = array_merge($allPages, range($start, $end));
            } else {
                $allPages[] = (int) $part;
            }
        }

        // Hapus duplikat & urutkan
        $allPages = array_values(array_unique($allPages));
        sort($allPages);

        if (empty($allPages)) {
            return '';
        }

        // Gabungkan jadi range yang rapi
        $result = [];
        $start = $end = $allPages[0];

        for ($i = 1; $i < count($allPages); $i++) {
            if ($allPages[$i] === $end + 1) {
                $end = $allPages[$i];
            } else {
                $result[] = $start === $end ? (string) $start : "{$start}-{$end}";
                $start = $end = $allPages[$i];
            }
        }
        $result[] = $start === $end ? (string) $start : "{$start}-{$end}";

        return implode(',', $result);
    }

    /**
     * Convert input jadi array of integer halaman.
     *
     * Contoh:
     *  - "1,4-7" → [1, 4, 5, 6, 7]
     */
    public static function toArray(string $input): array
    {
        $allPages = [];
        $hasil = str_replace(' ', '', $input);
        foreach (explode(',', $hasil) as $part) {
            $part = trim($part);

            if (str_contains($part, '-')) {
                [$start, $end] = array_map('intval', explode('-', $part));

                if ($start > $end) {
                    [$start, $end] = [$end, $start];
                }

                $allPages = array_merge($allPages, range($start, $end));
            } else {
                $allPages[] = (int) $part;
            }
        }

        $allPages = array_values(array_unique($allPages));
        sort($allPages);

        return $allPages;
    }
}