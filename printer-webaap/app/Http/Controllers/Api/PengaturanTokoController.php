<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PengaturanToko;

class PengaturanTokoController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_toko' => 'required|string',
            'biaya_admin' => 'required|integer|min:0',
            'biaya_layanan' => 'required|integer|min:0',
            'lama_hari' => 'required|integer|min:0',
        ]);

        $pengaturan = PengaturanToko::create([
            'status_buka' => false,
            'biaya_admin' => $validated['biaya_admin'],
            'biaya_layanan' => $validated['biaya_layanan'],
            'id_toko' => hex2bin($validated['id_toko']),
            'lama_file_dihapus' => $validated['lama_hari'],
        ]);

        return response()->json($pengaturan, 201);
    }

    public function getByTokoId(string $idToko)
    {
        $pengaturan = PengaturanToko::where('id_toko', hex2bin($idToko))->first();

        if (!$pengaturan) {
            return response()->json(null);
        }

        return response()->json($pengaturan);
    }

    public function getBiaya()
    {
        $pengaturan = PengaturanToko::find(1);

        if (!$pengaturan) {
            return response()->json(null);
        }

        return response()->json([
            'biaya_admin' => $pengaturan->biaya_admin,
            'biaya_layanan' => $pengaturan->biaya_layanan,
        ]);
    }

    public function destroy(int $id)
    {
        $pengaturan = PengaturanToko::findOrFail($id);
        $pengaturan->delete();

        return response()->json(['message' => 'Deleted']);
    }
}

