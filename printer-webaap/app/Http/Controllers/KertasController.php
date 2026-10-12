<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paper;
use Illuminate\Support\Facades\DB;

class KertasController extends Controller
{
    public function get()
    {
        $kertas = DB::table("paper")
            ->leftJoin('configuration', 'paper.id', '=', 'configuration.paper_id')
            ->select([
                'paper.id as id',
                'paper.name as name',
                'paper.width as width',
                'paper.height as height',
                DB::raw("CONCAT(paper.name, ' (', paper.height, '*', paper.width, ')') as ukuran"),
                'paper.aktif as aktif', // Menggunakan kolom 'aktif' dari tabel paper
                'paper.harga_color as berwarna',
                'paper.harga_grayscale as hitam_putih',
                DB::raw("COUNT(configuration.paper_id) as dibeli") // Menghitung total order
            ])
            ->groupBy(
                'paper.id',
                'paper.name',
                'paper.width',
                'paper.height',
                'paper.aktif',
                'paper.harga_color',
                'paper.harga_grayscale'
            )
            ->get()
            ->map(function ($item) {
                return [
                    'id'          => $item->id,
                    'name'        => $item->name,
                    'width'       => (int) $item->width,
                    'height'      => (int) $item->height,
                    'ukuran'      => $item->ukuran,
                    // Memastikan nilai 'aktif' bertipe boolean (true/false)
                    'aktif'       => (bool) $item->aktif,
                    // Format angka ke mata uang "Rp. X"
                    'berwarna'    => 'Rp. ' . number_format($item->berwarna, 0, ',', '.'),
                    'hitam_putih' => 'Rp. ' . number_format($item->hitam_putih, 0, ',', '.'),
                    // Nilai mentah (angka) untuk form edit
                    'harga_color_raw'     => (int) $item->berwarna,
                    'harga_grayscale_raw' => (int) $item->hitam_putih,
                    // Menambahkan suffix " order"
                    'dibeli'      => $item->dibeli . ' order',
                ];
            });

        return view("Dashboard.selection.kertas", compact('kertas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        Paper::create($validated);

        return redirect()->route("kertas.index")->with("success", "Sukses untuk menambahkan file");
    }

    public function update(Request $request, $id)
    {
        $paper = Paper::findOrFail($id);

        $validated = $request->validate($this->rules(), $this->messages());

        $paper->update($validated);

        return redirect()->route("kertas.index")->with("success", "Sukses mengubah kertas");
    }

    private function rules(): array
    {
        return [
            'name'            => 'required|string|min:1|max:50',
            'width'           => 'required|integer|min:20',
            'height'          => 'required|integer|min:23',
            'harga_color'     => 'required|integer|min:1',
            'harga_grayscale' => 'required|integer|min:1',
            'aktif'           => 'required|boolean',
        ];
    }

    private function messages(): array
    {
        return [
            // Messages untuk 'name'
            'name.required'            => 'Nama kertas wajib diisi.',
            'name.string'              => 'Nama kertas harus berupa teks.',
            'name.min'                 => 'Nama kertas minimal :min karakter.',
            'name.max'                 => 'Nama kertas maksimal :max karakter.',

            // Messages untuk 'width'
            'width.required'           => 'Lebar kertas wajib diisi.',
            'width.integer'            => 'Lebar kertas harus berupa angka bulat.',
            'width.min'                => 'Lebar kertas minimal :min mm.',

            // Messages untuk 'height'
            'height.required'          => 'Tinggi kertas wajib diisi.',
            'height.integer'           => 'Tinggi kertas harus berupa angka bulat.',
            'height.min'               => 'Tinggi kertas minimal :min mm.',

            // Messages untuk 'harga_color'
            'harga_color.required'     => 'Harga cetak berwarna wajib diisi.',
            'harga_color.integer'      => 'Harga cetak berwarna harus berupa angka.',
            'harga_color.min'          => 'Harga cetak berwarna minimal Rp :min.',

            // Messages untuk 'harga_grayscale'
            'harga_grayscale.required' => 'Harga cetak hitam putih wajib diisi.',
            'harga_grayscale.integer'  => 'Harga cetak hitam putih harus berupa angka.',
            'harga_grayscale.min'      => 'Harga cetak hitam putih minimal Rp :min.',

            // Messages untuk 'aktif'
            'aktif.required'          => 'Status aktif kertas wajib dipilih.',
            'aktif.boolean'           => 'Status aktif harus bernilai true atau false.',
        ];
    }
}
