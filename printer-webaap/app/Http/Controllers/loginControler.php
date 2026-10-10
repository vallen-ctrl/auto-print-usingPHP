<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class loginControler extends Controller
{
    public function get()
    {
        return view("Auth.login");
    }
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $toko = Toko::where('username', $validated['username'])->first();

        if (!$toko) {
            return back()->withErrors([
                "login" => "username / password salah"
            ])->withInput($request->only('username'));
        }

        if ($toko->password === $validated['password']) {
            $token = random_bytes(32);
            $toko->update(['token' => $token]);
            Cache::put("admin", [
                "token" => bin2hex($token),
                "tokoid" => bin2hex($toko->first()->id)
            ]);

            $request->session()->put("session", [
                'valid' => true,
                'token' => bin2hex($token),
                'tokoid' => bin2hex($toko->first()->id)
            ]);

            return redirect()->route("dashboard");
        }

        return back()->withErrors([
                "login" => "username / password salah"
            ])->withInput($request->only('username'));
    }
}
