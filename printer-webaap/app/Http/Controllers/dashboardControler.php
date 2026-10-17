<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\File;
use App\Models\Order;
use App\Models\Toko;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class dashboardControler extends Controller
{
    public function get()
    {
        $hariIni = now()->toDateString();
        $kemarin = now()->copy()->subDay()->toDateString();

        $orderHariIni = Order::whereDate('tanggal_pesan', $hariIni)->count();
        $orderSelesaiHariIni = Order::whereDate('tanggal_pesan', $hariIni)
            ->where('status_pembayaran', 'success')
            ->count();

        $pendapatanHariIni = (int) Order::whereDate('tanggal_pesan', $hariIni)
            ->sum('total_pembayaran');
        $pendapatanKemarin = (int) Order::whereDate('tanggal_pesan', $kemarin)
            ->sum('total_pembayaran');
        $persenPendapatan = $this->persentase($pendapatanHariIni, $pendapatanKemarin);

        $komputerTotal = Computer::count();
        $komputerAktif = Computer::where('status', 'connected')->count();

        $fileTersimpan = File::where('dihapus', false)->count();

        $bulanLalu = now()->copy()->subMonthNoOverflow();
        $fileBulanIni = File::where('dihapus', false)
            ->whereYear('tanggal_dibuat', now()->year)
            ->whereMonth('tanggal_dibuat', now()->month)
            ->count();
        $fileBulanLalu = File::where('dihapus', false)
            ->whereYear('tanggal_dibuat', $bulanLalu->year)
            ->whereMonth('tanggal_dibuat', $bulanLalu->month)
            ->count();
        $persenFile = $this->persentase($fileBulanIni, $fileBulanLalu);

        return view('Dashboard.selection.index', compact(
            'orderHariIni',
            'orderSelesaiHariIni',
            'pendapatanHariIni',
            'persenPendapatan',
            'komputerTotal',
            'komputerAktif',
            'fileTersimpan',
            'persenFile'
        ));
    }

    private function persentase(int $sekarang, int $sebelumnya): ?float
    {
        if ($sebelumnya === 0) {
            return $sekarang === 0 ? 0.0 : null;
        }

        return round((($sekarang - $sebelumnya) / $sebelumnya) * 100, 1);
    }

    private function labelLayanan(?string $layanan): string
    {
        if (! $layanan) {
            return '-';
        }

        return match ($layanan) {
            'kilat-edit' => 'Kilat Edit',
            'kilat-langsung' => 'Kilat Langsung',
            'terjadwal' => 'Terjadwal',
            default => ucwords(str_replace('-', ' ', $layanan)),
        };
    }

    private function labelMime(?string $mime): string
    {
        if (! $mime) {
            return 'Lainnya';
        }

        return match ($mime) {
            'application/pdf' => 'PDF',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PowerPoint',
            'image/jpeg', 'image/png' => 'Gambar',
            default => strtoupper(substr($mime, strrpos($mime, '/') + 1)),
        };
    }

    public function logout(Request $request)
    {
        try {
            $sessionData = $request->session()->get('session', null);

            if ($sessionData && isset($sessionData['tokoid'])) {
                $hexId = $sessionData['tokoid'];

                // Cek apakah string hex valid sebelum dikonversi
                if (ctype_xdigit($hexId) && strlen($hexId) % 2 === 0) {
                    $binaryId = hex2bin($hexId);

                    // Cari toko berdasarkan ID
                    $toko = Toko::where('id', $binaryId)->first();

                    if ($toko) {
                        $toko->update([
                            'token' => null,
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

            return redirect()->route('admin.login')->with([
                'success' => true,
                'message' => 'sukses keluar dari dashboard',
            ]);
        } catch (\Throwable $th) {
            Log::error('Error saat logout: '.$th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);

            return redirect()->route('admin.login')->with('error', 'Terjadi kesalahan saat logout.');
        }
    }

    public function pendapatan(Request $request)
    {
        $bulan = max(1, min(12, (int) $request->query('bulan', now()->month)));
        $tahun = (int) $request->query('tahun', now()->year);
        $jumlahHari = Carbon::create($tahun, $bulan, 1)->daysInMonth;

        $perHari = Order::whereYear('tanggal_pesan', $tahun)
            ->whereMonth('tanggal_pesan', $bulan)
            ->selectRaw('DAY(tanggal_pesan) as hari, SUM(total_pembayaran) as total')
            ->groupBy('hari')
            ->pluck('total', 'hari');

        $data = collect(range(1, $jumlahHari))
            ->map(fn ($hari) => (int) ($perHari[$hari] ?? 0));

        return response()->json($data);
    }

    public function tipeFile()
    {
        $data = File::where('dihapus', false)
            ->selectRaw('mime, COUNT(*) as jumlah')
            ->groupBy('mime')
            ->orderByDesc('jumlah')
            ->limit(3)
            ->get()
            ->map(fn ($file) => [
                'tipe' => $this->labelMime($file->mime),
                'jumlah' => (int) $file->jumlah,
            ]);

        return response()->json($data);
    }

    public function layananKertas(Request $request)
    {
        $bulan = max(1, min(12, (int) $request->query('bulan', now()->month)));
        $tahun = (int) $request->query('tahun', now()->year);

        $layanan = DB::table('files')
            ->join('orders', 'orders.id', '=', 'files.order_id')
            ->leftJoin('layanan_terpilih', 'layanan_terpilih.files_id', '=', 'files.id')
            ->whereYear('orders.tanggal_pesan', $tahun)
            ->whereMonth('orders.tanggal_pesan', $bulan)
            ->selectRaw('COALESCE(layanan_terpilih.nama_layanan, files.layanan) as nama, COUNT(DISTINCT orders.id) as total')
            ->groupBy(DB::raw('COALESCE(layanan_terpilih.nama_layanan, files.layanan)'))
            ->orderByDesc('total')
            ->get();

        $kertas = DB::table('configuration')
            ->join('files', 'files.id', '=', 'configuration.file_id')
            ->join('orders', 'orders.id', '=', 'files.order_id')
            ->whereYear('orders.tanggal_pesan', $tahun)
            ->whereMonth('orders.tanggal_pesan', $bulan)
            ->selectRaw('COALESCE(configuration.ukuran, "Lainnya") as nama, SUM(configuration.pages_toprint * configuration.jumlah_cetak) as total')
            ->groupBy('configuration.ukuran')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'layanan' => [
                'labels' => $layanan->pluck('nama')->map(fn ($nama) => $this->labelLayanan($nama))->values(),
                'series' => $layanan->pluck('total')->map(fn ($total) => (int) $total)->values(),
            ],
            'kertas' => [
                'labels' => $kertas->pluck('nama')->values(),
                'series' => $kertas->pluck('total')->map(fn ($total) => (int) $total)->values(),
            ],
        ]);
    }

    public function order(Request $request)
    {
        $perHalaman = 5;
        $halaman = max(1, (int) $request->query('page', 1));

        $paginator = DB::table('orders')
            ->select([
                'orders.id',
                'orders.tanggal_pesan',
                'orders.status_pembayaran',
                'orders.total_pembayaran',
                DB::raw('(SELECT COUNT(*) FROM files WHERE files.order_id = orders.id) as jumlah_file'),
                DB::raw('(SELECT COALESCE(SUM(COALESCE(configuration.pages_toprint, files.total_pages) * COALESCE(configuration.jumlah_cetak, 1)), 0) FROM files LEFT JOIN configuration ON configuration.file_id = files.id WHERE files.order_id = orders.id) as total_halaman'),
                DB::raw('(SELECT GROUP_CONCAT(DISTINCT COALESCE(layanan_terpilih.nama_layanan, files.layanan)) FROM files LEFT JOIN layanan_terpilih ON layanan_terpilih.files_id = files.id WHERE files.order_id = orders.id) as layanan'),
                DB::raw('(SELECT GROUP_CONCAT(DISTINCT configuration.ukuran) FROM files INNER JOIN configuration ON configuration.file_id = files.id WHERE files.order_id = orders.id) as ukuran'),
            ])
            ->orderByDesc('orders.tanggal_pesan')
            ->paginate($perHalaman, ['*'], 'page', $halaman);

        $data = $paginator->getCollection()->map(fn ($order) => [
            'tanggal' => Carbon::parse($order->tanggal_pesan)->format('d/m/Y'),
            'layanan' => $order->layanan
                ? collect(explode(',', $order->layanan))
                    ->map(fn ($nama) => $this->labelLayanan($nama))
                    ->join(', ')
                : '-',
            'ukuran' => $order->ukuran ?: '-',
            'files' => (int) $order->jumlah_file,
            'halaman' => (int) $order->total_halaman,
            'status' => $order->status_pembayaran,
            'total' => (int) $order->total_pembayaran,
        ]);

        return response()->json([
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }
}
