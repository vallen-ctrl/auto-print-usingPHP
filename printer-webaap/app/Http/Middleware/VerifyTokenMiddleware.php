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
    $sessionData = $request->session()->get('session');
    $inputToken  = $sessionData['token'] ?? $request->bearerToken() ?? null;

    // 1. Ambil token dari cache
    $storedToken = Cache::remember('admin', now()->addHours(2), function () {
        $toko = Toko::select("id", "token")->first();
        return [
            "token"  => bin2hex($toko->token ?? random_bytes(16)),
            "tokoid" => bin2hex($toko->id ?? random_bytes(16)),
        ];
    });

    // 2. Cek token
    if (!$inputToken || $inputToken !== $storedToken["token"]) {

        // Request dari API / AJAX / expects JSON → return 401 JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                "message" => "unauthorized request from api!"
            ], 401);
        }

        // Request dari browser biasa → redirect ke login
        return redirect()->route('admin.login');
    }

    return $next($request);
}
}