<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Configuration;
use App\Models\Paper;

class ConfigurationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file_id' => 'required|string',
            'orientasi' => 'required|in:potrait,landscape',
            'warna' => 'required|in:berwarna,hitam putih',
            'kualitas' => 'required|in:rendah,standar,tinggi',
            'skala' => 'nullable|string',
            'jumlah_cetak' => 'required|integer|min:1',
            'custom_halaman' => 'nullable|string',
            'catatan_tambahan' => 'nullable|string',
            'pages_toprint' => 'required|integer',
            'paper_id' => 'required|integer',
        ]);

        $paper = Paper::findOrFail($validated['paper_id']);
        $paperWidth = $paper->name . '|' . $paper->height . ' x ' . $paper->width;

        $validated['ukuran'] = $paperWidth;
        $validated['file_id'] = hex2bin($validated['file_id']);

        $configuration = Configuration::create($validated);

        return response()->json($configuration, 201);
    }

    public function destroy(int $id)
    {
        $configuration = Configuration::findOrFail($id);
        $configuration->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function update(Request $request, int $id)
    {
        $configuration = Configuration::findOrFail($id);
        $configuration->update($request->all());

        return response()->json($configuration);
    }

    public function showByFileId(string $fileId)
    {
        $configurations = Configuration::where('file_id', hex2bin($fileId))->get();

        return response()->json($configurations);
    }

    public function showById(int $id)
    {
        $configuration = Configuration::findOrFail($id);

        return response()->json($configuration);
    }
}

