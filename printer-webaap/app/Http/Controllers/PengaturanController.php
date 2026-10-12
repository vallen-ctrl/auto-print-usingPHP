<?php

namespace App\Http\Controllers;

use App\Models\JamOperasional;
use App\Models\PengaturanToko;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengaturanController extends Controller
{
    private const DAFTAR_HARI = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    public function get(Request $request)
    {
        $toko = $this->tokoSekarang($request);

        $pengaturanTersimpan = $toko
            ? PengaturanToko::where('id_toko', $toko->id)->first()
            : null;

        $jamTersimpan = $pengaturanTersimpan
            ? JamOperasional::where('id_pengaturan', $pengaturanTersimpan->id)
                ->get()
                ->keyBy('nomor_hari')
            : collect();

        $hari = [];
        foreach (self::DAFTAR_HARI as $nomor => $nama) {
            $baris = $jamTersimpan->get($nomor);

            $hari[] = [
                'nomor_hari' => $nomor,
                'nama'       => $baris?->hari ?? $nama,
                'mulai'      => $baris?->waktu_buka ? substr($baris->waktu_buka, 0, 5) : '07:00',
                'selesai'    => $baris?->waktu_tutup ? substr($baris->waktu_tutup, 0, 5) : '18:00',
                'buka'       => $baris ? (bool) $baris->apakah_dibuka : true,
            ];
        }

        $pengaturan = [
            'nama_toko'         => $toko->name ?? 'Toko print saya',
            'status_buka'       => $pengaturanTersimpan ? (bool) $pengaturanTersimpan->status_buka : true,
            'biaya_admin'       => $pengaturanTersimpan->biaya_admin ?? 20000,
            'biaya_layanan'     => $pengaturanTersimpan->biaya_layanan ?? 20000,
            'lama_file_dihapus' => $pengaturanTersimpan->lama_file_dihapus ?? 5,
        ];

        return view('Dashboard.selection.pengaturan', compact('pengaturan', 'hari'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'status_buka'         => 'required|boolean',
            'biaya_admin'         => 'required|integer|min:0',
            'biaya_layanan'       => 'required|integer|min:0',
            'lama_file_dihapus'   => 'required|integer|min:1|max:365',
            'jam'                 => 'required|array|min:1',
            'jam.*.nomor_hari'    => 'required|integer|between:1,7',
            'jam.*.hari'          => 'required|string|max:10',
            'jam.*.apakah_dibuka' => 'required|boolean',
            'jam.*.waktu_buka'    => 'nullable|date_format:H:i',
            'jam.*.waktu_tutup'   => 'nullable|date_format:H:i',
        ], [
            'status_buka.required'       => 'Status buka wajib diisi.',
            'status_buka.boolean'        => 'Status buka tidak valid.',
            'biaya_admin.required'       => 'Biaya admin wajib diisi.',
            'biaya_admin.integer'        => 'Biaya admin harus berupa angka.',
            'biaya_admin.min'            => 'Biaya admin tidak boleh negatif.',
            'biaya_layanan.required'     => 'Biaya layanan wajib diisi.',
            'biaya_layanan.integer'      => 'Biaya layanan harus berupa angka.',
            'biaya_layanan.min'          => 'Biaya layanan tidak boleh negatif.',
            'lama_file_dihapus.required' => 'Lama penyimpanan file wajib diisi.',
            'lama_file_dihapus.integer'  => 'Lama penyimpanan file harus berupa angka.',
            'lama_file_dihapus.min'      => 'Lama penyimpanan file minimal 1 hari.',
            'lama_file_dihapus.max'      => 'Lama penyimpanan file maksimal 365 hari.',
            'jam.required'               => 'Jam operasional wajib diisi.',
            'jam.array'                  => 'Format jam operasional tidak valid.',
            'jam.min'                    => 'Jam operasional minimal satu hari.',
            'jam.*.nomor_hari.required'  => 'Nomor hari wajib diisi.',
            'jam.*.nomor_hari.between'   => 'Nomor hari tidak valid.',
            'jam.*.hari.required'        => 'Nama hari wajib diisi.',
            'jam.*.apakah_dibuka.boolean' => 'Status buka hari tidak valid.',
            'jam.*.waktu_buka.date_format'  => 'Format jam buka harus HH:MM.',
            'jam.*.waktu_tutup.date_format' => 'Format jam tutup harus HH:MM.',
        ]);

        $toko = $this->tokoSekarang($request);

        if (!$toko) {
            return response()->json([
                'message' => 'Toko tidak ditemukan. Silakan login ulang.',
            ], 401);
        }

        DB::transaction(function () use ($validated, $toko) {
            $pengaturan = PengaturanToko::updateOrCreate(
                ['id_toko' => $toko->id],
                [
                    'status_buka'       => $validated['status_buka'],
                    'biaya_admin'       => $validated['biaya_admin'],
                    'biaya_layanan'     => $validated['biaya_layanan'],
                    'lama_file_dihapus' => $validated['lama_file_dihapus'],
                ]
            );

            foreach ($validated['jam'] as $baris) {
                JamOperasional::updateOrCreate(
                    [
                        'id_pengaturan' => $pengaturan->id,
                        'nomor_hari'    => $baris['nomor_hari'],
                    ],
                    [
                        'hari'          => $baris['hari'],
                        'waktu_buka'    => $baris['waktu_buka'] ?? null,
                        'waktu_tutup'   => $baris['waktu_tutup'] ?? null,
                        'apakah_dibuka' => $baris['apakah_dibuka'],
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Pengaturan berhasil disimpan.',
        ]);
    }

    private function tokoSekarang(Request $request): ?Toko
    {
        $session = $request->session()->get('session', []);
        $tokoid = $session['tokoid'] ?? null;

        return $tokoid ? Toko::find(hex2bin($tokoid)) : Toko::first();
    }
}
