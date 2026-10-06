<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LayananTerpilih;
use App\Models\Layanan;

class LayananTerpilihController extends Controller
{
    public function show(Request $request)
    {
        $validated = $request->validate([
            'files_id' => 'required|string',
        ]);

        $layanan = LayananTerpilih::where('files_id', hex2bin($validated['files_id']))->get();

        return response()->json($layanan);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'files_id' => 'required|string',
            'layanan_id' => 'required|integer',
        ]);

        $layanan = Layanan::findOrFail($validated['layanan_id']);

        $data = LayananTerpilih::create([
            'files_id' => hex2bin($validated['files_id']),
            'layanan_id' => $validated['layanan_id'],
            'nama_layanan' => $layanan->nama,
        ]);

        return response()->json($data, 201);
    }

    public function destroy(int $id)
    {
        $layanan = LayananTerpilih::findOrFail($id);
        $layanan->delete();

        return response()->json(['message' => 'Deleted']);
    }
}

