<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;

abstract class Controller
{
    public function gettokoId(){
        $tokoId = Cache::get("admin", null)["tokoid"] ?? null;
        if($tokoId == null) return response()->json([
            "message" => "Unautorized user",
            "error" => true
        ], 401);
        return $tokoId;
    }
    
}
