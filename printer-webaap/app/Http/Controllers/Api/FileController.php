<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function updateUploader(Request $request, string $fileId)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $file = File::findOrFail(hex2bin($fileId));
        $file->update([
            'diupload_oleh' => $validated['session_id'],
        ]);

        return response()->json($file);
    }

    public function updateSubtotal(Request $request, string $fileId)
    {
        $validated = $request->validate([
            'subtotal' => 'required|integer|min:0',
        ]);

        $file = File::findOrFail(hex2bin($fileId));
        $file->update([
            'subtotal' => $validated['subtotal'],
        ]);

        return response()->json($file);
    }

    public function destroy(string $fileId)
    {
        $file = File::findOrFail(hex2bin($fileId));
        $file->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3',
            'path' => 'required|string|min:3',
            'size' => 'required|integer',
            'mime' => 'required|string',
            'order_id' => 'required|string',
            'total_pages' => 'required|integer',
        ]);

        $id = Str::uuid();
        $validated['id'] = $id->getBytes();
        $validated['order_id'] = hex2bin($validated['order_id']);

        $file = File::create($validated);

        return response()->json(['id' => $id->toString()], 201);
    }

    public function show(string $fileId)
    {
        $file = File::findOrFail(hex2bin($fileId));

        return response()->json($file);
    }
}

