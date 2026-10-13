<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File as filestype;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use App\Models\Order;
use App\Rules\CustomHalamanRule;

class FileController extends Controller
{
    private function parseHalaman(string $input): array
    {
        $result = [];
        foreach (explode(',', $input) as $part) {
            if (str_contains($part, '-')) {
                [$start, $end] = explode('-', $part);
                $result = array_merge($result, range((int) $start, (int) $end));
            } else {
                $result[] = (int) $part;
            }
        }
        return array_unique($result);
    }

    public function updateUploader(Request $request, string $fileId)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        $file = File::findOrFail(hex2bin($fileId));
        $file->update([
            'diupload_oleh' => $validated['session_id'],
        ]);
    }

    public function updateSubtotal(Request $request, string $fileId)
    {
        $validated = $request->validate([
            'subtotal' => 'required|integer|min:0',
        ]);

        $file = File::findOrFail(hex2bin($fileId));
        $file->update([
            'subtotal' => $validated['subtotal'],
        ]);

        return response()->json($file);
    }

    public function destroy(string $fileId)
    {
        $file = File::findOrFail(hex2bin($fileId));
        $file->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function store(Request $request)
    {
        $pilihan = $request->input('pilihan');

        $request->validate([
            'pilihan' => ['required', Rule::in(['kilat-langsung', 'kilat-edit', 'terjadwal'])],
            'file'    => ['required', 'array', 'min:1', 'max:10'],
            'file.*'  => [
                'required',
                in_array($pilihan, ['kilat-langsung', 'terjadwal'])
                    ? filestype::types(['pdf'])->max(10240)
                    : filestype::types(['pdf', 'docx', 'doc'])->max(10240),
            ],
        ]);

        $savedFiles = [];
        $orderID = Str::uuid7();
        $idwithNoDash = str_replace("-", "", $orderID->toString());
        $ordersFiles = Cache::get("client:$idwithNoDash", [
            "orderid" => "",
            "files" => []
        ]);
   
        $pdf = new Parser();
        DB::transaction(function () use ($request, &$savedFiles, &$pdf, &$pilihan){
            foreach ($request->file('file') as $file) {
                $id = Str::uuid7();
                $filemetadata = [
                    "id" => $id->getBytes(),
                    "name" => Str::squish($file->getClientOriginalName()),
                    "ext" => $file->getClientOriginalExtension(),
                    "mime" => $file->getMimeType(),
                    "size" => (int) ceil($file->getSize() / 1024),
                    "uploadsucced" => $file->isValid() 
                ];
                $randomUUID = bin2hex(random_bytes(2));
                $filename = date('Y-m-d H_i_s', time()) . "($randomUUID)" . $filemetadata["name"];
                
                $file->storeAs('uploads', $filename, 'local');
                $path = Storage::disk("local")->path("uploads/" . $filename);
                $jumlahPages = $pdf->parseFile($path)->getPages();
                $filemetadata["pages"] = count($jumlahPages);

                File::create([
                    "id" => $filemetadata["id"],
                    "name" => $filemetadata["name"],
                    "path" => "none",
                    "size" => $filemetadata["size"],
                    "mime" => $filemetadata["mime"],
                    "total_pages" => $filemetadata["pages"] ,
                    "layanan" => $pilihan
                ]);

                $filemetadata["id"] = bin2hex($filemetadata["id"]); 
                $savedFiles[] = $filemetadata;
            }
        });

        $ordersFiles["files"] = $savedFiles;
        $ordersFiles["orderid"] = $idwithNoDash;
        Cache::put("client:$idwithNoDash", $ordersFiles, 300);// jangan lupa untuk di kasih max 5 menit

        return response()->json([
            'orderid' => $idwithNoDash
        ], 201);
    }

    public function show(Request $request,string $orderid)
    {
        $ordersFiles = Cache::get("client:$orderid", [
            "orderid" => "",
            "filesid" => []
        ]);

        return response()->json($ordersFiles);
    }

    public function getManyPaper(Request $request){
        $validate = $request->validate([
            "costume_halaman" => ['required', new CustomHalamanRule()]
        ]);

        return response()->json([
            "pages" => count($this->parseHalaman($validate["costume_halaman"]))
        ]);
    }
}
