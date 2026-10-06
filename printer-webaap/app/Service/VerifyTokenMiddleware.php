<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Toko;
use Symfony\Component\HttpFoundation\Response;

class VerifyTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $inputToken = $request->bearerToken();

        // 1. Ambil token dari cache atau database jika cache kosong
        $storedToken = Cache::remember('admin_token', now()->addHours(2), function () {
            return Toko::first()?->token; 
        });

        // 2. Cek apakah token cocok
        if (!$inputToken || $inputToken !== $storedToken) {
            return response()->json([
                'message' => 'Unauthorized / Token tidak valid'
            ], 401);
        }

        return $next($request);
    }
}