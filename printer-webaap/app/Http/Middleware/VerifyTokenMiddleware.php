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
        $storedToken = Cache::remember('admin', now()->addHours(2), function () {
            
            $toko = Toko::select("id", "token")->get();
            return [
                "token" => bin2hex($toko->first()->token),
                "tokoid" => bin2hex($toko->first()->id)
            ];
        });

        // 2. Cek apakah token cocok
        if (!$inputToken || $inputToken !== $storedToken["token"]) {
            return response()->json([
                'message' => "Unautorized please relogin again"
            ], 401);
        }

        return $next($request);
    }
}