<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Layanan;
use Illuminate\Support\Facades\DB;

class LayananController extends Controller
{
    public function get()
    {
        $layanan = [
            ['nama' => 'Laminating', 'harga' => 'Rp. 2000', 'status' => true, 'stok' => '600', 'dibeli' => '502 order'],
            ['nama' => 'Jilid Spiral', 'harga' => 'Rp. 5000', 'status' => true, 'stok' => '340', 'dibeli' => '281 order'],
            ['nama' => 'Print Warna', 'harga' => 'Rp. 1500', 'status' => true, 'stok' => '999', 'dibeli' => '812 order'],
            ['nama' => 'Fotokopi', 'harga' => 'Rp. 300', 'status' => false, 'stok' => '0', 'dibeli' => '1204 order'],
            ['nama' => 'Scan Dokumen', 'harga' => 'Rp. 2000', 'status' => true, 'stok' => '120', 'dibeli' => '97 order'],
        ];

        $layanan = DB::table('layanan')
            ->leftJoin('layanan_terpilih', 'layanan.id', '=', 'layanan_terpilih.layanan_id')
            ->select([
                'layanan.nama as nama',
                'layanan.harga as harga',
                'layanan.aktif as status',
                'layanan.jumlah as stok',
                'layanan.image_path as gambar',
                DB::raw('COUNT(layanan_terpilih.id) as dibeli')
            ])
            ->groupBy('layanan.id', 'layanan.nama', 'layanan.harga', 'layanan.aktif', 'layanan.jumlah', 'layanan.image_path')
            ->get()
            ->map(function ($item) {
                return [
                    'nama'   => $item->nama,
                    // Mengubah angka 2000 menjadi format string "Rp. 2000"
                    'harga'  => 'Rp. ' . number_format($item->harga, 0, ',', '.'),
                    // Memastikan status bertipe boolean (true/false)
                    'status' => (bool) $item->status,
                    // Mengubah angka stok ke string
                    'stok'   => (string) $item->stok,
                    // Menambahkan teks " order" di belakang angka jumlah dibeli
                    'dibeli' => $item->dibeli . ' order',
                    // Path gambar yang tersimpan di storage/app/public
                    'gambar' => $item->gambar,
                ];
            });
        return view("Dashboard.selection.layanan", compact('layanan'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'      => 'required|string|max:50',
            'deskripsi' => 'required|string',
            'harga'     => 'required|integer|min:0',
            'jumlah'    => 'required|integer|min:0',
            'aktif'     => 'required|boolean',
            'gambar'    => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ], [
            'nama.required'      => 'Nama layanan wajib diisi.',
            'nama.max'           => 'Nama layanan maksimal 50 karakter.',
            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'harga.required'     => 'Harga wajib diisi.',
            'harga.integer'      => 'Harga harus berupa angka bulat.',
            'harga.min'          => 'Harga tidak boleh negatif.',
            'jumlah.required'    => 'Jumlah wajib diisi.',
            'jumlah.integer'     => 'Jumlah harus berupa angka bulat.',
            'jumlah.min'         => 'Jumlah tidak boleh kurang dari 0.',
            'aktif.required'     => 'Status aktif wajib diisi.',
            'aktif.boolean'      => 'Status aktif tidak valid.',
            'gambar.image'       => 'File harus berupa gambar.',
            'gambar.mimes'       => 'Gambar harus PNG atau JPEG.',
            'gambar.max'         => 'Ukuran gambar maksimal 2MB.',
        ]);

        $data = collect($validated)->except('gambar')->all();

        if ($request->hasFile('gambar')) {
            $data['image_path'] = $request->file('gambar')->store('layanan', 'public');
        }

        Layanan::create($data);

        return redirect()->route('layanan.index')->with('success', 'Sukses menambah layanan');
    }
}
