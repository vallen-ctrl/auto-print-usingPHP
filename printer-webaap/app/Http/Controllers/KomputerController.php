<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Computer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KomputerController extends Controller
{
    public function get()
    {
        // $computers = [
        //     ['nama' => 'Komputer Kasir 1', 'token' => 'a3f9c1d04b7e5821e6f0a9b3c7d21e45', 'dibuat' => '2026-08-20 09:12:00', 'status' => 'connected'],
        //     ['nama' => 'Komputer Operator', 'token' => '7b2e9a4f1c8d3056af47e2b9c0d8135a', 'dibuat' => '2026-08-21 13:45:00', 'status' => 'pending'],
        //     ['nama' => 'Komputer Admin', 'token' => 'e91c4f7a2b6d8035cf12a7e4b9d06c38', 'dibuat' => '2026-08-22 08:03:00', 'status' => 'not connected'],
        //     ['nama' => 'Komputer Gudang', 'token' => '4d8b1e6a9f2c7053ba69d4e1f8c02b57', 'dibuat' => '2026-08-24 16:20:00', 'status' => 'shutdown'],
        //     ['nama' => 'Komputer Front Office', 'token' => 'c2a7f0e3b6d9184e5a3c7f2b0d6e9148', 'dibuat' => '2026-08-26 10:55:00', 'status' => 'warning'],
        // ];

        $computers = Computer::select(
            DB::raw("LOWER(HEX(id)) as id"),
            'name as nama',
            DB::raw("LOWER(HEX(token))as token"),
            'tanggal_dibuat as dibuat',
            'status'
        )->get();

        return view("Dashboard.selection.komputer", compact('computers'));
    }

    public function getStatucisActivebyId(Request $request)
    {
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $Toko =  $request->session()->get("session", null)["tokoid"] ?? null;
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

    public function destroy(string $id){
        $data = Computer::find(hex2bin($id));

        if(!$data){
            return redirect()->back()->with("warning", "Tidak ada computer dengan id tersebut");
        }
        $data->delete();
        return redirect()->back()->with("success", "berhasil menghapus");
    }
}
