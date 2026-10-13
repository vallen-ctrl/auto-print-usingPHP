<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class ComputerController extends Controller
{

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

}
