<?php

namespace App\Http\Controllers;

use App\Models\Toko;
use Illuminate\Http\Middleware\TrustProxies;
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
                "message"=> "sukses keluar dari dashboard"
            ]);

        } catch (\Throwable $th) {
            Log::error("Error saat logout: " . $th->getMessage(), [
                'trace' => $th->getTraceAsString()
            ]);

            return redirect()->route("admin.login")->with('error', 'Terjadi kesalahan saat logout.');
        }
    }
}
