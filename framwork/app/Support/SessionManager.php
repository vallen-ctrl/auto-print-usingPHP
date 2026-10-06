<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

class SessionManager
{
    public static function start(): void {} // Laravel sudah start otomatis

    public static function set(string $key, mixed $value): void
    {
        Session::put($key, $value);
    }

    public static function push(string $key, mixed $value, string $insideValueKey = ""): void
    {
        $path = $insideValueKey === "" ? $key : "$key.$insideValueKey";
        Session::push($path, $value);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Session::get($key, $default);
    }

    public static function has(string $key): bool
    {
        return Session::has($key);
    }

    public static function remove(string $key): void
    {
        Session::forget($key);
    }

    public static function destroy(): void
    {
        Session::flush();
        Session::invalidate();
    }
}