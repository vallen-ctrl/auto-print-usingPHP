<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sessionid' => 'required|string',
            'name' => 'required|string',
            'no_wa' => 'required|string|min:10',
        ]);

        $noWa = str_replace('-', '', $validated['no_wa']);

        $user = User::create([
            'sessionid' => $validated['sessionid'],
            'name' => $validated['name'],
            'no_wa' => $noWa,
        ]);

        return response()->json([
            'sessionid' => $user->sessionid,
            'name' => $user->name,
            'no_wa' => $user->no_wa,
            'create_at' => $user->create_at,
        ], 201);
    }
}


