<?php

namespace App\Http\Controllers;

use App\Models\PengaturanToko;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class signupControler extends Controller
{
    public function get(Request $req){
        $error = "";
        $countToko = Toko::count();
        if($countToko > 0){
            $error = "User lain sudah mendaftar, harap masuk dengan akun sebelumnya. aplikasi ini dirancang untuk hanya memiliki 1 akun saja";
        }

        return view("Auth.signup", compact('error'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:10|max:255',
            'username' => 'required|string|min:10|max:50|unique:toko,username',
            'password' => 'required|string|min:8|max:255',
        ]);

        $id = Str::uuid7();
        $manyUsers = Toko::count();
        if($manyUsers > 0){
            return back()->withErrors([
                "error" => "User lain sudah mendaftar, harap masuk dengan akun sebelumnya. aplikasi ini dirancang untuk hanya memiliki 1 akun saja"
            ])->withInput($request->only('username', 'name'));
        }

        $toko = Toko::create([
            'id' => $id->getBytes(),
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => $validated['password'],
        ]);

        $pengaturanToko = PengaturanToko::create([
            "id" => 1,
            "status_buka" => false,
            "biaya_admin" => 0,
            "biaya_layanan" => 0,
            "id_toko" => $id->getBytes(),
            "lama_file_dihapus" => 2
        ]);

        return redirect()->route("admin.login")->with([
            "success" => true,
            "message" => "login anda berhasil harap dapat masuk kembali"
        ]);
    }
}
