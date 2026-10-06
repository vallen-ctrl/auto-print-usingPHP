<?php
class validator
{
    public static function input(array $required, array $POST = null): array
    {
        $src = $POST ?? $_POST;
        $out = [];
        foreach ($required as $key) {
            $out[$key] = isset($src[$key]) ? trim(htmlspecialchars($src[$key])) : '';
        }
        return $out;
    }

    /**
     * @param array $input Data input (biasanya hasil $_POST)
     * @param array $rules Aturan validasi per field, contoh: ['nama' => 'required|min:3']
     * @return array Daftar error per field
     */
    public static function validate(array $input, array $rules)
    {
        $errors = [];
        foreach ($rules as $field => $ruleStr) {
            $val = $input[$field] ?? '';
            foreach (explode("|", $ruleStr) as $rule) {
                [$name, $param] = array_pad(explode(":", $rule, 2), 2, null);
                $msg = match ($name) {
                    'required' => $val === '' ? "$field wajib diisi" : null,
                    'email'    => $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL) ? "$field tidak valid" : null,
                    'min'      => strlen($val) < (int)$param ? "$field minimal $param karakter" : null,
                    'max'      => strlen($val) > (int)$param ? "$field maksimal $param karakter" : null,
                    'numeric'  => $val !== '' && !is_numeric($val) ? "$field harus angka" : null,
                    'date' => $val !== '' && !(($d = DateTime::createFromFormat($param ?? 'Y-m-d', $val)) && $d->format($param ?? 'Y-m-d') === $val)
                        ? "$field bukan tanggal valid" : null,
                    default    => null,
                };
                if ($msg) {
                    $errors[$field] = $msg;
                    break;
                }
            }
        }
        return $errors;
    }
}
