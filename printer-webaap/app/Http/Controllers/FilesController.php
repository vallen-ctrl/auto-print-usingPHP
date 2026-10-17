<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    public function get()
    {
        return view("Dashboard.selection.files");
    }

    public function getFileById(Request $request)
    {
        $token = $request->bearerToken();
        $validated = $request->validate([
            "fileid" => "required"
        ]);

        $computer = Computer::where('token', hex2bin($token))->first();

        if (!$computer) {
            return response()->json([
                'message' => 'unable to find token',
                'error' => true,
            ], 401);
        }

        $file = File::findOrFail(hex2bin($validated['fileid']));


        return Storage::disk('local')->download($file->path, $file->name);
    }
}
