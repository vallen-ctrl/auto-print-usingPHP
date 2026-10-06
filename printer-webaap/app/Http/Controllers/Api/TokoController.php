<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Toko;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class TokoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:10|max:255',
            'username' => 'required|string|min:10|max:50|unique:toko,username',
            'password' => 'required|string|min:8|max:255',
        ]);

        $id = Str::uuid();
        $manyUsers = Toko::count();
        if($manyUsers > 0){
            return response()->json([
                "message" => "cant add more users, this package create just for one user",
                "code" => 409
            ], 409);
        }
        $toko = Toko::create([
            'id' => $id->getBytes(),
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => $validated['password'],
        ]);

        return response()->json([
            'id' => $id->toString(),
            'message' => 'Toko created'
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:10|max:255',
            'username' => 'required|string|min:10|max:50',
            'password' => 'required|string|min:8|max:255',
        ]);

        $toko = Toko::findOrFail(hex2bin($id));
        $toko->update($validated);

        return response()->json($toko);
    }

    public function checkMax()
    {
        $exists = Toko::exists();
        
        return response()->json(['max' => $exists]);
    }

    public function info()
    {
        $toko = Toko::select('name', 'tanggal_dibuat', 'tanggal_update')->get();
        
        return response()->json($toko);
    }

    public function ids()
    {
        $ids = Toko::select('id')->get();
        
        return response()->json($ids);
    }

    public function setToken(string $id)
    {
        $toko = Toko::findOrFail(hex2bin($id));
        $token = random_bytes(32);
        
        $toko->update(['token' => $token]);
        
        return response()->json(['token' => bin2hex($token)]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $toko = Toko::where('username', $validated['username'])->first();
        
        if (!$toko) {
            return response()->json(['valid' => false], 401);
        }

        if ($toko->password === $validated['password']) {
            $token = random_bytes(32);
            $toko->update(['token' => $token]);
            Cache::put("admin", [
                "token" => bin2hex($token),
                "tokoid" => bin2hex($toko->first()->id)
            ]);
            return response()->json([
                'valid' => true,
                'token' => bin2hex($token)
            ]);
        }

        return response()->json(['valid' => false], 401);
    }

    public function validateToken(Request $request)
    {
        $validated = $request->validate([
            'toko_id' => 'required|string',
            'token' => 'required|string',
        ]);

        $toko = Toko::findOrFail(hex2bin($validated['toko_id']));
        
        if (!$toko->token) {
            return response()->json(['valid' => false]);
        }

        $isValid = bin2hex($toko->token) === $validated['token'];
        
        return response()->json(['valid' => $isValid]);
    }
}

