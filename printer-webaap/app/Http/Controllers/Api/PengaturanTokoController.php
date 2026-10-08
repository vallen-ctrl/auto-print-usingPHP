<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PengaturanToko;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\get;

class PengaturanTokoController extends Controller
{
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_toko' => 'required|string',
            'biaya_admin' => 'required|integer|min:0',
            'biaya_layanan' => 'required|integer|min:0',
            'lama_hari' => 'required|integer|min:0',
        ]);

        $tokoId = Cache::get("admin", null)["tokoid"] ?? null;
        if($tokoId == null) return response()->json([
            "message" => "Unautorized user",
            "error" => true
        ], 401);

        $pengaturan = PengaturanToko::create([
            'status_buka' => false,
            'biaya_admin' => $validated['biaya_admin'],
            'biaya_layanan' => $validated['biaya_layanan'],
            'id_toko' => hex2bin($validated['id_toko']),
            'lama_file_dihapus' => $validated['lama_hari'],
        ]);

        return response()->json($pengaturan, 201);
    }

    public function update(Request $request){
        $tokoid = $this->gettokoId();
        $validated = $request->validate([
            'biaya_admin' => 'nullable|integer|min:0',
            'biaya_layanan' => 'nullable|integer|min:0',
            'lama_hari' => 'nullable|integer|min:0',
        ]);
        $validate = collect($validated)->filter(fn ($v) => filled($v))->all();
        
        if(count($validate) <= 0){
            return response()->json([
                "message" => "cant update if you dont give some value",
                "error" => true
            ], 406);
        }
        
        $dbsearch = PengaturanToko::where('id_toko', hex2bin($tokoid))->firstOrFail();
        $dbsearch->update($validate);
        return response()->json([
            "message" => "succed update",
            "newData" => $validate
        ], 200);
    }

    public function getByTokoId(string $idToko)
    {
        $pengaturan = PengaturanToko::where('id_toko', hex2bin($idToko))->first();

        if (!$pengaturan) {
            return response()->json(null);
        }

        return response()->json($pengaturan);
    }

    public function getBiaya()
    {
        $pengaturan = PengaturanToko::find(1);

        if (!$pengaturan) {
            return response()->json(null);
        }

        return response()->json([
            'biaya_admin' => $pengaturan->biaya_admin,
            'biaya_layanan' => $pengaturan->biaya_layanan,
        ]);
    }

    public function destroy(int $id)
    {
        $pengaturan = PengaturanToko::findOrFail($id);
        $pengaturan->delete();

        return response()->json(['message' => 'Deleted']);
    }
}

