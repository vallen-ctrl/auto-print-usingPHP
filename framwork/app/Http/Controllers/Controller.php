<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
   public function jsonResponse(string $msg, array $data = [], bool $isEror = false, int $code = 200): JsonResponse
    {
        return response()->json([
            'message'       => $msg,
            'iseror'        => $isEror,
            'data'          => $data,
            'responsesCode' => $code,
        ], $code, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
