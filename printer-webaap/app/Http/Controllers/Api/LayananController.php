<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Layanan;

class LayananController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:50',
            'deskripsi' => 'required|string',
            'harga' => 'required|integer',
        ]);

        $layanan = Layanan::create($validated);
        $layanan->refresh();

        return response()->json($layanan, 201);
    }

    public function destroy(int $id)
    {
        $layanan = Layanan::findOrFail($id);
        $layanan->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function update(Request $request, int $id)
    {
        $layanan = Layanan::findOrFail($id);
        $layanan->update($request->all());

        return response()->json($layanan);
    }

    public function index(Request $request)
    {
        $id = $request->query('id');

        if ($id) {
            $layanan = Layanan::findOrFail($id);
            return response()->json($layanan);
        }

        return response()->json(Layanan::all());
    }
}

