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
        $inputToken = $sessionData['token'] ?? $request->bearerToken() ?? null;

        $hasToken = $request->has('_token') || 
                    $request->hasHeader('X-CSRF-TOKEN') || 
                    $request->hasHeader('X-XSRF-TOKEN');
       
        // 1. Ambil token dari cache atau database jika cache kosong
        $storedToken = Cache::remember('admin', now()->addHours(2), function () {
            
            $toko = Toko::select("id", "token")->get();
            return [
                "token" => bin2hex($toko->first()->token ?? random_bytes(16)),
                "tokoid" => bin2hex($toko->first()->id ?? random_bytes(16))
            ];
        });

        // 2. Cek apakah token cocok
        if (!$inputToken || $inputToken !== $storedToken["token"]) {
            if($hasToken){
                return redirect()->route('admin.login');
            }
            return response()->json([
                "message" => "unautorized request from api!"
            ], 401);
        }

        return $next($request);
    }
}