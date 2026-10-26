<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Configuration;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    public function formatBytes(int $bytes, $presisi = 2)
    {
        $satuan = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return round($bytes / (1024 ** $i), $presisi) . ' ' . $satuan[$i];
    }

    public function getMaxFile()
    {
        return File::count();
    }

    public function maxFile(Request $request)
    {
        return response()->json([
            "max" => $this->getMaxFile()
        ]);
    }

    public function get(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $query = File::query()
            ->leftJoin('configuration', 'configuration.file_id', '=', 'files.id')
            ->select([
                'files.id',
                'files.name',
                'files.path',
                'files.mime',
                'files.total_pages',
                'files.subtotal',
                'files.dihapus',
                'files.tanggal_dibuat',
                'files.order_id',
                DB::raw('LOWER(HEX(files.id)) as id_hex'),
                DB::raw('LOWER(HEX(files.order_id)) as order_hex'),
                DB::raw('MIN(configuration.ukuran) as kertas'),
            ])
            ->groupBy(
                'files.id',
                'files.name',
                'files.path',
                'files.mime',
                'files.total_pages',
                'files.subtotal',
                'files.dihapus',
                'files.tanggal_dibuat',
                'files.order_id'
            );

        // Pencarian: nama file, mime, path, atau order id (hex).
        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($q) use ($like, $search) {
                $q->where('files.name', 'like', $like)
                    ->orWhere('files.mime', 'like', $like)
                    ->orWhere('files.path', 'like', $like)
                    ->orWhereRaw('LOWER(HEX(files.order_id)) LIKE ?', ['%' . strtolower($search) . '%']);
            });
        }

        // Paginasi 10 file per halaman; withQueryString agar ?search tetap terbawa.
        $files = $query->orderByDesc('files.tanggal_dibuat')
            ->paginate(10)
            ->withQueryString();

        $totalFiles = $this->getMaxFile();
        $sumTotalPenggunaan = $this->formatBytes((int) File::sum("size"));

        return view("Dashboard.selection.files", compact('totalFiles', 'sumTotalPenggunaan', 'files', 'search'));
    }

    public function download(string $id)
    {
        $file = File::findOrFail(hex2bin($id));

        return Storage::disk('local')->download($file->path, $file->name);
    }

    public function destroy(string $id)
    {
        $file = File::findOrFail(hex2bin($id));

        // Hapus file fisik dari storage, lalu barisnya di database.
        if ($file->path) {
            Storage::disk('local')->delete($file->path);
        }

        $file->delete();

        return redirect()->route('files.index')->with('success', 'File berhasil dihapus.');
    }

    public function getConfigurationByFileId(Request $request)
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

        // Tambahkan join ke tabel 'files'
        $configuration = Configuration::join('paper', 'paper.id', '=', 'configuration.paper_id')
            ->join('files', 'files.id', '=', 'configuration.file_id') // Join ke tabel files
            ->where("configuration.file_id", hex2bin($validated['fileid']))
            ->select(
                'configuration.*',
                'paper.width as paper_width',
                'paper.height as paper_height',
                'files.layanan' // Ambil kolom layanan dari tabel files
            )
            ->first();

        if ($configuration) {
            $data = $configuration->toArray();

            // 1. Ubah BINARY file_id menjadi HEX agar aman di-JSON-kan
            if (isset($data['file_id'])) {
                $data['file_id'] = bin2hex($data['file_id']);
            }

            // 2. Tentukan nilai 'mode' berdasarkan 'layanan'
            $layanan = $data['layanan'] ?? '';
            $mode = null;

            if ($layanan === 'kilat-edit') {
                $mode = 'downloads';
            } elseif ($layanan === 'kilat-langsung') {
                $mode = 'print';
            } else {
                // Opsional: jika ada layanan lain seperti 'terjadwal' atau 'kilat-langsung'
                // kamu bisa menyesuaikan mode default-nya di sini
                $mode = 'print'; // Contoh default
            }

            // Tambahkan properti 'mode' ke dalam response
            $data['mode'] = $mode;

            return response()->json(['data' => $data]);
        }

        return response()->json(['data' => null]);
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
