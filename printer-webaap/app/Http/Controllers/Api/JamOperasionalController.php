<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JamOperasional;

class JamOperasionalController extends Controller
{
    private function validateTime(string $time): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $time);
    }

    private function getDayName(int $day): string
    {
        return match ($day) {
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
            7 => 'minggu',
            default => throw new \InvalidArgumentException('expected day 1 to 7'),
        };
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hari_ke' => 'required|integer|between:1,7',
            'jam_buka' => 'required|string',
            'jam_tutup' => 'required|string',
            'apakah_buka' => 'required|boolean',
        ]);

        if (!$this->validateTime($validated['jam_buka']) || !$this->validateTime($validated['jam_tutup'])) {
            throw new \InvalidArgumentException('expected jam with format H:i:s');
        }

        $jam = JamOperasional::create([
            'nomor_hari' => $validated['hari_ke'],
            'hari' => $this->getDayName($validated['hari_ke']),
            'waktu_buka' => $validated['jam_buka'],
            'waktu_tutup' => $validated['jam_tutup'],
            'apakah_dibuka' => $validated['apakah_buka'],
            'id_pengaturan' => 1,
        ]);

        return response()->json($jam, 201);
    }

    public function update(Request $request, int $id)
    {
        $jam = JamOperasional::findOrFail($id);

        $data = $request->validate([
            'nomor_hari'    => 'sometimes|integer|between:1,7',
            'waktu_buka'    => 'sometimes|date_format:H:i:s',
            'waktu_tutup'   => 'sometimes|date_format:H:i:s',
            'apakah_dibuka' => 'sometimes|boolean',
        ]);
        
        if (empty($data)) {
            return response()->json(['message' => 'Tidak ada field valid yang dikirim.'], 422);
        }

        if (isset($data['nomor_hari'])) {
            $data['hari'] = $this->getDayName($data['nomor_hari']);
        }

        $jam->update($data);

        return response()->json($jam);
    }

    public function destroy(int $id)
    {
        $jam = JamOperasional::findOrFail($id);
        $jam->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function index()
    {
        return response()->json(JamOperasional::all());
    }

    public function getByPengaturan(string $idPengaturan)
    {
        $jams = JamOperasional::where('id_pengaturan', $idPengaturan)->get();

        return response()->json($jams);
    }
}
