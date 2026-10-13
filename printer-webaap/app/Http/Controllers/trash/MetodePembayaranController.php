<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MetodePembayaran;
use Illuminate\Support\Str;

class MetodePembayaranController extends Controller
{
    private function checkLenBytes(string $id, int $len = 32): void
    {
        if (strlen($id) !== $len) {
            throw new \Exception("eror id expected 16 bytes (32 char)");
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'webhook' => 'required|string',
            'token' => 'required|string',
            'imagepath' => 'required|string',
        ]);

        $id = Str::uuid7();
        $metode = MetodePembayaran::create([
            'id' => $id->getBytes(),
            'nama' => $validated['name'],
            'webhook' => $validated['webhook'],
            'token' => $validated['token'],
            'image_path' => $validated['imagepath'],
            'id_pengaturan' => 1,
        ]);

        return response()->json(['id' => str_replace("-", "", $id->toString())], 201);
    }

    public function update(Request $request, string $id)
    {
        $this->checkLenBytes($id);
        
        $metode = MetodePembayaran::findOrFail(hex2bin($id));
        $metode->update($request->all());

        return response()->json($metode);
    }

    public function destroy(string $id)
    {
        $this->checkLenBytes($id);
        
        $metode = MetodePembayaran::findOrFail(hex2bin($id));
        $metode->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function show()
    {
        $responses = MetodePembayaran::selectRaw("LOWER(HEX(id)) as id, nama, webhook, token, image_path")->get();
        return response()->json($responses);
    }

    public function index()
    {   
        $responses = MetodePembayaran::selectRaw("LOWER(HEX(id)) as id, nama, image_path")->get();
        return response()->json($responses);
    }
}

