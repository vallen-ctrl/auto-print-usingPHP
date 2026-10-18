<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\File;
use App\Models\FileQueue;
use Illuminate\Http\StreamedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ComputerController extends Controller
{
    public function updateFileQueue(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required',
            'fileid' => 'required',
            'status' => ['requiired', Rule::in(['pending', 'processing', 'complited', 'failed', 'downloading'])]
        ]);
        $computerToken =  Computer::where('token', hex2bin($validated["token"]));
        if (!$computerToken->exists()) {
            return response()->json([
                "message" => "can't reconized your token please recreate again or copy the right value",
                "error" => true
            ], 401);
        }

        $updated = File::where('file_id', hex2bin($validated['fileid']))
            ->update([
                "status" => $validated['status']
            ]);
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

        $tabel->update([
            "status" => $statuses[2]
        ]);

        return response()->eventStream(function () use ($computer, $cacheKey, $connectionId, $statuses) {
            yield new StreamedEvent('connected', [
                'status' => $statuses[2],
                'waktu'  => now()->toTimeString(),
            ]);

            while (true) {
                $pending = FileQueue::where('computer_id', $computer->id)
                    ->where('status', 'pending')
                    ->get();
                if ($pending->isEmpty()) {
                    yield new StreamedEvent('heartbeat', [
                        'waktu' => now()->toTimeString(),
                    ]);
                } else {
                    yield new StreamedEvent('notification', [
                        'waktu' => now()->toTimeString(),
                        'data'  => $pending,
                    ]);

                    FileQueue::whereIn('id', $pending->pluck('id'))
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
