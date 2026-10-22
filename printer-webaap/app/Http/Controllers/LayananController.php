<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Layanan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LayananController extends Controller
{
    public function get()
    {
        $layanan = DB::table('layanan')
            ->leftJoin('layanan_terpilih', 'layanan.id', '=', 'layanan_terpilih.layanan_id')
            ->select([
                'layanan.id as id',
                'layanan.nama as nama',
                'layanan.deskripsi as deskripsi',
                'layanan.harga as harga',
                'layanan.aktif as status',
                'layanan.jumlah as stok',
                'layanan.image_path as gambar',
                DB::raw('COUNT(layanan_terpilih.id) as dibeli')
            ])
            ->groupBy(
                'layanan.id',
                'layanan.nama',
                'layanan.deskripsi',
                'layanan.harga',
                'layanan.aktif',
                'layanan.jumlah',
                'layanan.image_path'
            )
            ->get()
            ->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'nama'       => $item->nama,
                    'deskripsi'  => $item->deskripsi,
                    // Mengubah angka 2000 menjadi format string "Rp. 2000"
                    'harga'      => 'Rp. ' . number_format($item->harga, 0, ',', '.'),
                    // Nilai mentah (angka) untuk form edit
                    'harga_raw'  => (int) $item->harga,
                    // Memastikan status bertipe boolean (true/false)
                    'status'     => (bool) $item->status,
                    // Mengubah angka stok ke string
                    'stok'       => (string) $item->stok,
                    // Nilai mentah (angka) untuk form edit
                    'jumlah_raw' => (int) $item->stok,
                    // Menambahkan teks " order" di belakang angka jumlah dibeli
                    'dibeli'     => $item->dibeli . ' order',
                    // Path gambar yang tersimpan di storage/app/public
                    'gambar'     => $item->gambar,
                ];
            });
        return view("Dashboard.selection.layanan", compact('layanan'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $data = collect($validated)->except('gambar')->all();

        if ($request->hasFile('gambar')) {
            $data['image_path'] = $request->file('gambar')->store('layanan', 'public');
        }

        Layanan::create($data);

        return redirect()->route('layanan.index')->with('success', 'Sukses menambah layanan');
    }

    public function update(Request $request, $id)
    {
        $layanan = Layanan::findOrFail($id);

        $validated = $request->validate($this->rules(), $this->messages());

        $data = collect($validated)->except('gambar')->all();

        if ($request->hasFile('gambar')) {
            // Hapus gambar lama supaya tidak menumpuk di storage
            if ($layanan->image_path && $layanan->image_path !== '-') {
                Storage::disk('public')->delete($layanan->image_path);
            }

            $data['image_path'] = $request->file('gambar')->store('layanan', 'public');
        }

        $layanan->update($data);

        return redirect()->route('layanan.index')->with('success', 'Sukses mengubah layanan');
    }

    private function rules(): array
    {
        return [
            'nama'      => 'required|string|max:50',
            'deskripsi' => 'required|string',
            'harga'     => 'required|integer|min:0',
            'jumlah'    => 'required|integer|min:0',
            'aktif'     => 'required|boolean',
            'gambar'    => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ];
    }

    private function messages(): array
    {
        return [
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
        ];
    }

    public function DeleteLayanan(Request $request){
        $validated = $request->validate([
            "id" => "required|number"
        ]);

        $deleted = Layanan::destroy($validated["id"]);

        if($deleted == 0){
            return response()->json([
                "message" => "cant find any paper id",
                "error" => false
            ], 304);
        }

        return response()->json([
            "message" => "sukses menghapus paper",
            "error" => false
        ], 200);
    }
}
