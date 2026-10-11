<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\Middleware\TrustProxies;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class dashboardControler extends Controller
{
    public function get()
    {
        return view("Dashboard.selection.index");
    }

    public function logout(Request $request)
    {
        try {
            $sessionData = $request->session()->get("session", null);

            if ($sessionData && isset($sessionData["tokoid"])) {
                $hexId = $sessionData["tokoid"];

                // Cek apakah string hex valid sebelum dikonversi
                if (ctype_xdigit($hexId) && strlen($hexId) % 2 === 0) {
                    $binaryId = hex2bin($hexId);

                    // Cari toko berdasarkan ID
                    $toko = Toko::where('id', $binaryId)->first();

                    if ($toko) {
                        $toko->update([
                            "token" => null
                        ]);
                    } else {
                        Log::warning("Logout: Toko dengan ID hex {$hexId} tidak ditemukan di database.");
                    }
                } else {
                    Log::warning("Logout: Format tokoid hex tidak valid: {$hexId}");
                }
            }

            // Hapus session & redirect
            $request->session()->flush();
            return redirect()->route("admin.login")->with([
                "success" => true,
                "message" => "sukses keluar dari dashboard"
            ]);
        } catch (\Throwable $th) {
            Log::error("Error saat logout: " . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return redirect()->route("admin.login")->with('error', 'Terjadi kesalahan saat logout.');
        }
    }

    public function pendapatan(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = now()->year;
        $jumlahHari = Carbon::create($tahun, $bulan, 1)->daysInMonth;

        // DUMMY, ganti dengan query asli, contoh:
        // $per_hari = Order::whereYear('created_at', $tahun)
        //     ->whereMonth('created_at', $bulan)
        //     ->selectRaw('DAY(created_at) as hari, SUM(total) as total')
        //     ->groupBy('hari')
        //     ->pluck('total', 'hari');
        // $data = collect(range(1, $jumlahHari))->map(fn ($h) => (int) ($per_hari[$h] ?? 0));

        $data = collect(range(1, $jumlahHari))->map(fn() => random_int(100_000, 600_000));

        return response()->json($data);
    }

    public function tipeFile()
    {
        // DUMMY, ganti dengan query asli, contoh:
        // return response()->json(
        //     File::selectRaw('tipe, COUNT(*) as jumlah')
        //         ->groupBy('tipe')->orderByDesc('jumlah')->limit(3)->get()
        // );

        return response()->json([
            ['tipe' => 'PDF',  'jumlah' => 22],
            ['tipe' => 'Word', 'jumlah' => 3],
            ['tipe' => 'PPT',  'jumlah' => 10],
        ]);
    }

    public function layananKertas(Request $request)
    {
        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = now()->year;

        // DUMMY, ganti dengan query asli, contoh:
        // $layanan = Order::whereYear('created_at', $tahun)->whereMonth('created_at', $bulan)
        //     ->join('layanan', 'layanan.id', '=', 'orders.layanan_id')
        //     ->selectRaw('layanan.nama, COUNT(*) as total')->groupBy('layanan.nama')
        //     ->pluck('total', 'nama');

        return response()->json([
            'layanan' => [
                'labels' => ['Layanan A', 'Layanan B', 'Layanan C'],
                'series' => [60, 20, 20],
            ],
            'kertas' => [
                'labels' => ['Layanan A', 'Layanan B', 'Layanan C'],
                'series' => [304, 223, 123],
            ],
        ]);
    }
    public function order(Request $request)
    {
        $perHalaman = 5;
        $halaman = max(1, (int) $request->query('page', 1));

        // DUMMY, ganti dengan query asli, contoh:
        // $paginator = Order::with('layanan')->latest()->paginate($perHalaman);
        // $data = $paginator->getCollection()->map(fn ($o) => [
        //     'tanggal'  => $o->created_at->format('d/m/Y'),
        //     'layanan'  => $o->layanan->pluck('nama')->join(', '),
        //     'ukuran'   => $o->ukuran_kertas,        // contoh: "A4, F4"
        //     'files'    => $o->files_count,
        //     'halaman'  => $o->total_halaman,
        //     'status'   => $o->status_pembayaran,    // pending | lunas | batal
        //     'total'    => $o->total,
        // ]);
        // return response()->json([
        //     'data' => $data,
        //     'current_page' => $paginator->currentPage(),
        //     'last_page' => $paginator->lastPage(),
        // ]);

        $semua = collect(range(1, 23))->map(fn($i) => [
            'tanggal' => now()->subDays($i)->format('d/m/Y'),
            'layanan' => 'Laminating, Jilid',
            'ukuran'  => 'A4, F4',
            'files'   => 2,
            'halaman' => 40,
            'status'  => $i % 3 === 0 ? 'lunas' : 'pending',
            'total'   => 30000,
        ]);

        return response()->json([
            'data' => $semua->forPage($halaman, $perHalaman)->values(),
            'current_page' => $halaman,
            'last_page' => (int) ceil($semua->count() / $perHalaman),
        ]);
    }
}
