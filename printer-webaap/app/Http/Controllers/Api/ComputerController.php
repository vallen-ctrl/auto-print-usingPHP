<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ComputerController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $Toko = Cache::get("admin", null)["tokoid"] ?? null;
        if ($Toko == null) return response()->json([
            "message" => "unable to find toko id, please relogin again",
            "error" => true,
            "data" => hex2bin($Toko)
        ], 401);

        $id = Str::uuid();
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
            'id' => $id->toString(),
        ], 201);
    }

    public function serverSendEvent(Request $request)
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $validated = $request->validate([
            'token' => 'required',
        ]);

        $statuses = ['not connected', 'pending', 'connected', 'shutdown', 'warning'];
        $tabel =  Computer::where('token', hex2bin($validated["token"]));

        if (!$tabel->exists()) {
            return response()->json([
                "message" => "can't reconized your token please recreate again or copy the right value",
                "error" => true
            ], 401);
        }

        if ($validated["token"] !== bin2hex($tabel->first()->token)) {
            return response()->json([
                "message" => "TOKEN INVALID",
                "error" => true
            ], 401);
        }

        $tabel->update([
            "status" => $statuses[2]
        ]);


        return response()->eventStream(function () use ($tabel, $statuses) {
            while (true) {
                yield ['event' => "hearthbeat", 'waktu' => now()->toTimeString()];
                if (connection_aborted()) {
                    $tabel->update([
                        "status" => $statuses[3]
                    ]);
                    break;
                }

                sleep(2);
            }
        });
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
            'id' => strtolower(bin2hex($computer->id)),
            'name' => $computer->name,
            'toko_id' => strtolower(bin2hex($computer->toko_id)),
            'status' => $computer->status,
            'token' => strtolower(bin2hex($computer->token)),
            'tanggal_dibuat' => $computer->tanggal_dibuat,
        ]);
    }

    public function index()
    {
        $computers = Computer::selectRaw('LOWER(HEX(id)) as id, name, LOWER(HEX(toko_id)) as toko_id, status, LOWER(HEX(token)) as token, tanggal_dibuat')
            ->get();

        return response()->json($computers);
    }
}
