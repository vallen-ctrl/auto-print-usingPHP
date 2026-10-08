<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SkalaRule implements ValidationRule
{
    /**
     * Nilai enum yang diperbolehkan.
     */
    protected array $enum = [
        'fit_to_paper',
        'fit_to_printable_area',
        'actual_size',
        'custom',
        'none',
    ];

    /**
     * Batas minimum & maksimum persentase.
     */
    protected int $minPersen = 1;
    protected int $maxPersen = 200;

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1. Cek apakah value ada di enum
        if (in_array($value, $this->enum, true)) {
            return;
        }

        // 2. Cek apakah value format persentase: 80%, 100%, 150%
        if (preg_match('/^(\d{1,3})%$/', $value, $m)) {
            $angka = (int) $m[1];

            if ($angka < $this->minPersen || $angka > $this->maxPersen) {
                $fail("Persentase skala harus antara {$this->minPersen}% - {$this->maxPersen}%.");
            }
            return;
        }

        // 3. Kalau bukan enum & bukan persen → gagal
        $enumList = implode(', ', $this->enum);
        $fail("Skala harus salah satu dari: {$enumList}, atau format persentase (contoh: 80%).");
    }
}