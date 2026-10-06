<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Paper;
use App\Service\VerifiedToken;

class PaperController extends Controller
{
    public function store(Request $request)
    {
        
        $validated = $request->validate([
            'name' => 'required|string|min:1|max:50',
            'width' => 'required|integer|min:20',
            'height' => 'required|integer|min:23',
            'harga_color' => 'required|integer|min:1',
            'harga_grayscale' => 'required|integer|min:1',
        ]);

        $paper = Paper::create($validated);

        return response()->json($paper, 201);
    }

    public function destroy(int $id)
    {
        $paper = Paper::findOrFail($id);
        $paper->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function update(Request $request, int $id)
    {
        $paper = Paper::findOrFail($id);
        $paper->update($request->all());

        return response()->json($paper);
    }

    public function index(Request $request)
    {
        $id = $request->query('id');

        if ($id) {
            $paper = Paper::findOrFail($id);
            return response()->json($paper);
        }

        return response()->json(Paper::all());
    }

    public function get(Request $request){
        $paper = Paper::select('id','name', 'width', 'height', 'harga_color', 'harga_grayscale')->get();
        return response()->json($paper);
    }
}

