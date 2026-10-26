<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\File;
use App\Models\FileQueue;
use App\Service\tokenComputer;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;


class ComputerController extends Controller
{
    public function gettokenQr(Request $request)
    {
        $validated = $request->validate([
            "token" => "required",
        ]);

        $computer = Computer::where("token", hex2bin($validated["token"]))->first();

        if (!$computer) {
            return response()->json([
                "message" => "can't recognize your token, please recreate it or copy the right value",
                "error" => true,
            ], 401);
        }
        $tokenComputer = new tokenComputer();
        $createToken = $tokenComputer->createToken([
            "computerid" => bin2hex($computer->first()->id),
            "userSession" => "session"
        ], 70);

        return response()->json([
            "token" => $createToken,
        ]);
    }

    public function computerScreen(Request $request)
    {
        $validated = $request->validate([
            "token" => "required"
        ]);

        $computer = Computer::where("token", hex2bin($validated["token"]));

        if (!$computer->exists()) {
            return response()->json([
                "message" => "can't reconized your token please recreate again or copy the right value",
                "error" => true
            ], 401);
        }

        $tokenComputer = new tokenComputer();

        $createToken = $tokenComputer->createToken([
            "computerid" => bin2hex($computer->first()->id),
            "userSession" => "session"
        ], 70);

        $token = $validated["token"];
        return view("qrtoken", compact('token', 'createToken'));
    }


    public function updateFileQueue(Request $request)
    {
        $token = $request->bearerToken();
        $validated = $request->validate([
            'fileid' => 'required',
            'status' => ['required', Rule::in(['pending', 'processing', 'complited', 'failed', 'downloading'])]
        ]);

        $computer = Computer::where('token', hex2bin($token))->first();

        if (!$computer) {
            return response()->json([
                'message' => 'unable to find token',
                'error' => true,
            ], 401);
        }

        // Gunakan DB facade untuk menghindari masalah serialisasi model
        $updated = DB::table('file_queue')
            ->where('file_id', hex2bin($validated['fileid']))
            ->update([
                "status" => $validated['status']
            ]);

        return response()->json([
            "message" => "Succed to update your status",
            "error" => false
        ], 200);
    }

    public function serverSendEvent(Request $request)
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $validated = $request->validate([
            "token" => "required"
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

        $computer = $tabel->first();
        $cacheKey = 'computer:sse:' . bin2hex($computer->token);
        $connectionId = (string) Str::uuid();

        Cache::put($cacheKey, $connectionId, now()->addDay());

        // add ini untuk mempercepat req ketika server benar benar sudah terhubung
        Cache::put("cache:" . $validated["token"], $statuses[2], 20);
        $tabel->update([
            "status" => $statuses[2]
        ]);


        return response()->eventStream(function () use ($computer, $cacheKey, $connectionId, $statuses) {
            yield new StreamedEvent('connected', [
                'status' => $statuses[2],
                'waktu'  => now()->toTimeString(),
            ]);

            while (true) {
                $pendingRows = FileQueue::where('file_queue.computer_id', $computer->id)
                    ->where('file_queue.status', 'pending')
                    ->join('files', 'files.id', '=', 'file_queue.file_id')
                    ->whereIn('files.layanan', ['kilat-langsung', 'terjadwal'])
                    ->where('files.dihapus', false)
                    ->select('file_queue.id', 'file_queue.file_id')
                    ->get();

                if ($pendingRows->isEmpty()) {
                    yield new StreamedEvent('heartbeat', ['waktu' => now()->toTimeString()]);
                } else {
                    $ids = $pendingRows->map(fn($row) => bin2hex($row->file_id));

                    yield new StreamedEvent('download', [
                        'waktu' => now()->toTimeString(),
                        'data'  => $ids,
                    ]);

                    FileQueue::whereIn('id', $pendingRows->pluck('id'))
                        ->update(['status' => 'processing']);
                }

                if (connection_aborted()) {
                    // Jangan timpa status koneksi baru saat koneksi lama abort.
                    if (Cache::get($cacheKey) === $connectionId) {
                        Computer::whereKey($computer->id)->update([
                            "status" => $statuses[3]
                        ]);
                        Cache::forget($cacheKey);
                    }
                    break;
                }
                sleep(2);
            }
        }, [], null);
    }
}
