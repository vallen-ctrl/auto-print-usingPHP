<?php

namespace App\Support;

use Illuminate\Support\Facades\DB as LaravelDB;
use InvalidArgumentException;

class db
{
    public static function execute(string $query, array $param = []): bool
    {
        return LaravelDB::statement($query, $param);
    }

    public static function insert(string $tabel, array $colum_to_value): int
    {
        foreach ($colum_to_value as $key => $value) {
            if ($value === null) {
                throw new InvalidArgumentException("Nilai untuk kolom '$key' null");
            }
        }
        return (int) LaravelDB::table($tabel)->insertGetId($colum_to_value);
    }

    public static function update(string $tabel, array $data, string $whereCol, int|string $whereVal): int
    {
        return LaravelDB::table($tabel)->where($whereCol, $whereVal)->update($data);
    }

    public static function delete(string $tabel, string $whereCol, int|string $whereVal): int
    {
        return LaravelDB::table($tabel)->where($whereCol, $whereVal)->delete();
    }

    public static function get(string $query, array $param = [], string $option = 'all')
    {
        $rows = array_map(fn ($r) => (array) $r, LaravelDB::select($query, $param));

        return match ($option) {
            'row'   => isset($rows[0]) ? array_values($rows[0]) : null,
            'assoc' => $rows[0] ?? null,
            default => array_map('array_values', $rows),
        };
    }

    public static function getUsingParam(string $tabel, string $fromWhere, int|string $key, string $option = 'all', array $column = ['*']): mixed
    {
        if (is_string($key)) {
            $key = hex2bin($key);
        }

        $sql = 'SELECT ' . implode(', ', $column) . " FROM `$tabel` WHERE `$fromWhere` = ?";

        return self::get($sql, [$key], $option);
    }
}