<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Computer;
use Illuminate\Support\Str;

class KomputerController extends Controller
{
    public function get(){
        return view("Dashboard.selection.komputer");
    }

    public function getStatucisActivebyId(Request $request){
        $validated = $request->validate([
            "comid" => "required"
        ]);
        $binId = hex2bin($validated["comid"]);
        $data = Computer::where("id", $binId)
                ->select("status")
                ->first();
        return response()->json([
            "status" =>  $data->status
        ]);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $Toko =  $request->session()->get("session",null)["tokoid"] ?? null;
        if ($Toko == null) return response()->json([
            "message" => "unable to find toko id, please relogin again",
            "error" => true,
            "data" => hex2bin($Toko)
        ], 401);

        $id = Str::uuid7();
        $token = random_bytes(16);

        $validated["toko_id"] = $Toko;

        $computer = Computer::create([
            'id' => $id->getBytes(),
            'name' => substr($validated['name'], 0, 100),
            'token' => $token,
            'toko_id' => hex2bin($validated['toko_id']),
        ]);

        return response()->json([
            'token' => bin2hex($token),
            'id' => str_replace("-", "", $id->toString()),
        ], 201);
    }
}
