<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Computer;
use App\Models\FileQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KomputerController extends Controller
{
    public function computerStatus(int $StatusCode): string
    {
        $data = ['not connected', 'shutdown', 'pending', 'connected', 'warning'];
        return $data[$StatusCode];
    }

    private function getFileStatus()
    {
        $queueStats = DB::table('file_queue')
            ->select(
                DB::raw("LOWER(HEX(computer_id)) as computer_id"),
                'status',
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('computer_id')
            ->groupBy(DB::raw("LOWER(HEX(computer_id))"), 'status')
            ->get()
            ->groupBy('computer_id'); // dikelompokkan per computer_id
        return $queueStats;
    }

    private function getComputerFromMysql()
    {
        $computers = Computer::select(
            DB::raw("LOWER(HEX(id)) as id"),
            'name as nama',
            DB::raw("LOWER(HEX(token)) as token"),
            'tanggal_dibuat as dibuat',
            'status'
        )->get();

        // Hitung jumlah per status per komputer
        $queueStats = $this->getFileStatus();

        // Map hasil ke masing-masing komputer
        $computers->each(function ($computer) use ($queueStats) {
            $stats = $queueStats->get($computer->id, collect());

            // default 0 untuk semua status
            $computer->queue_counts = [
                'pending'     => (int) optional($stats->firstWhere('status', 'pending'))->total,
                'processing'  => (int) optional($stats->firstWhere('status', 'processing'))->total,
                'completed'   => (int) optional($stats->firstWhere('status', 'completed'))->total,
                'failed'      => (int) optional($stats->firstWhere('status', 'failed'))->total,
                'downloading' => (int) optional($stats->firstWhere('status', 'downloading'))->total,
            ];
        });

        return $computers;
    }

    public function getFileStat()
    {
        $fileStat = $this->getFileStatus()
        ->map(function ($rows) {
            $counts = $rows->pluck('total', 'status')
                ->map(fn ($v) => (int) $v)
                ->all();

            return array_merge([
                'pending'     => 0,
                'processing'  => 0,
                'completed'   => 0,
                'failed'      => 0,
                'downloading' => 0,
            ], $counts);
        })
        ->all();

        return response()->json([
            "message" => "sukses mengambil data",
            "data" => $fileStat
        ]);
    }


    public function get()
    {
        $computers = $this->getComputerFromMysql();
        return view("Dashboard.selection.komputer", compact('computers'));
    }

    public function getStatucisActivebyId(Request $request)
    {
        $validated = $request->validate([
            "comid" => "required"
        ]);

        $getStatus = Cache::get("cache:" . $validated["comid"], null);

        if ($getStatus == null) {
            return response()->json([
                "status" =>  $this->computerStatus(0)
            ]);
        } else {
            return response()->json([
                "status" =>  $getStatus
            ]);
        }
        // bagian ini diubah untuk mempercepat saja
        // $binId = hex2bin($validated["comid"]);
        // $data = Computer::where("id", $binId)
        //     ->select("status")
        //     ->first();
        // return response()->json([
        //     "status" =>  $data->status
        // ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $Toko =  $request->session()->get("session", null)["tokoid"] ?? null;
        if ($Toko == null) return response()->json([
            "message" => "unable to find toko id, please relogin again",
            "error" => true,
            "data" => hex2bin($Toko)
        ], 401);

        $id = Str::uuid7();
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
            'id' => str_replace("-", "", $id->toString()),
        ], 201);
    }

    public function destroy(string $id)
    {
        // Konversi Hex dari URL ke Binary untuk mencari data
        $binaryId = hex2bin($id);
        $computer = Computer::find($binaryId);

        if (!$computer) {
            return redirect()->back()->with("warning", "Tidak ada computer dengan id tersebut");
        }

        // Cek file yang berstatus pending atau failed
        $filesToMove = DB::table('file_queue')
            ->where('computer_id', $binaryId)
            ->whereIn('status', ['pending', 'failed'])
            ->get();

        if ($filesToMove->isNotEmpty()) {

            // PERBAIKAN 1: Gunakan 'computer.*' (tunggal) sesuai nama tabel asli
            // PERBAIKAN 2: Pastikan subquery merujuk ke tabel 'computer'
            $targetComputer = Computer::select('computer.*')
                ->selectRaw("(
                SELECT COUNT(*) FROM file_queue 
                WHERE file_queue.computer_id = computer.id 
                AND file_queue.status IN ('pending', 'processing')
            ) as beban_kerja")
                ->where('id', '!=', $binaryId) // Gunakan binaryId yang sudah di hex2bin
                ->orderBy('beban_kerja', 'asc')
                ->first();

            // Jika tidak ada komputer lain sama sekali
            if (!$targetComputer) {
                return redirect()->back()->with("warning", "Gagal menghapus. Tidak ada komputer lain untuk memindahkan file pending/failed.");
            }

            // Pindahkan file
            DB::table('file_queue')
                ->where('computer_id', $binaryId)
                ->whereIn('status', ['pending', 'failed'])
                ->update([
                    'computer_id' => $targetComputer->id
                ]);
        }

        // Hapus komputer
        $computer->delete();

        return redirect()->back()->with("success", "Berhasil menghapus komputer" . ($filesToMove->isNotEmpty() ? " dan memindahkan {$filesToMove->count()} antrean file ke {$targetComputer->name}" : ""));
    }

    public function retry(string $id)
    {
        $computerBinaryId = hex2bin($id);
        $computerName = Computer::findOrFail($computerBinaryId)->first()->name;
        try {
            FileQueue::where("computer_id", $computerBinaryId)
                ->whereIn("status", ["processing", "failed"])
                ->update([
                    "status" => "pending"
                ]);
            return redirect()->back()->with("success", "Mengulang semua file yang gagal pada komputer $computerName");
        } catch (\Exception $e) {
            return redirect()->back()->with("error", "Ada masalah dengan komputer / File yang bermasalah.");
        }
    }
}
