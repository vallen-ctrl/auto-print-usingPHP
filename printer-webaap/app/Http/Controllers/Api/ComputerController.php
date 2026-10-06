<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use Illuminate\Support\Str;

class ComputerController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'toko_id' => 'required|string',
        ]);

        $id = Str::uuid();
        $token = random_bytes(16);

        $computer = Computer::create([
            'id' => $id->getBytes(),
            'name' => substr($validated['name'], 0, 100),
            'token' => $token,
            'toko_id' => hex2bin($validated['toko_id']),
        ]);

        return response()->json([
            'token' => bin2hex($token),
            'id' => $id->toString(),
        ], 201);
    }

    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required|integer|between:0,4',
        ]);

        $statuses = ['not connected', 'pending', 'connected', 'shutdown', 'warning'];
        
        $computer = Computer::findOrFail(hex2bin($id));
        $computer->update([
            'status' => $statuses[$validated['status']],
        ]);

        return response()->json([
            'id' => bin2hex($computer->id),
            'name' => $computer->name,
            'toko_id' => bin2hex($computer->toko_id),
            'status' => $computer->status,
            'token' => bin2hex($computer->token),
            'tanggal_dibuat' => $computer->tanggal_dibuat,
        ]);
    }

    public function destroy(string $id)
    {
        $computer = Computer::findOrFail(hex2bin($id));
        $computer->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function getByToken(Request $request)
    {
        $token = $request->query('token');
        
        if (!$token) {
            return response()->json(['message' => 'Token required'], 422);
        }

        $computer = Computer::where('token', hex2bin($token))->first();

        if (!$computer) {
            return response()->json(null);
        }

        return response()->json([
            'token' => bin2hex($computer->token),
            'id' => bin2hex($computer->id),
        ]);
    }

    public function getById(string $id)
    {
        $computer = Computer::findOrFail(hex2bin($id));

        return response()->json([
            'id' => bin2hex($computer->id),
            'name' => $computer->name,
            'toko_id' => bin2hex($computer->toko_id),
            'status' => $computer->status,
            'token' => bin2hex($computer->token),
            'tanggal_dibuat' => $computer->tanggal_dibuat,
        ]);
    }

    public function index()
    {
        $computers = Computer::selectRaw('HEX(id) as id, name, HEX(toko_id) as toko_id, status, HEX(token) as token, tanggal_dibuat')
            ->get();

        return response()->json($computers);
    }
}

