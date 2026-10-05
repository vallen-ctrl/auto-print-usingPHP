<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once dirname(__DIR__, 2) . "/vendor/autoload.php";
require_once dirname(__DIR__, 2) . "/Controler/paperControler.php";
require_once dirname(__DIR__, 2) . "/Controler/filecontorler.php";
require_once dirname(__DIR__, 2) . "/Controler/layanan_terpilihControler.php";
require_once dirname(__DIR__, 2) . "/Controler/layananControler.php";
require_once dirname(__DIR__, 2) . "/Controler/configurationControler.php";
require_once dirname(__DIR__, 2) . "/Controler/userControler.php";
require_once 'json.php';
require_once 'helper.php';


$METHOD = $_SERVER["REQUEST_METHOD"];

class template
{
    private static function hitungHalaman(string $input, int $totalHalaman): int
    {
        $input = str_replace(' ', '', $input);   // "1-4, 8" -> "1-4,8"
        $halaman = [];

        foreach (explode(',', $input) as $bagian) {
            if (strpos($bagian, '-') !== false) {
                [$awal, $akhir] = array_map('intval', explode('-', $bagian));

                if ($awal < 1 || $awal > $akhir || $akhir > $totalHalaman) {
                    throw new InvalidArgumentException("Range tidak valid: $bagian");
                }
                for ($i = $awal; $i <= $akhir; $i++) {
                    $halaman[$i] = true;   // pakai key supaya duplikat otomatis hilang
                }
            } else {
                $n = (int) $bagian;
                if ($n < 1 || $n > $totalHalaman) {
                    throw new InvalidArgumentException("Halaman tidak valid: $bagian");
                }
                $halaman[$n] = true;
            }
        }

        return count($halaman);
    }
    public static function post()
    {
        try {
            $input = getInput();
            $orderId = SessionManager::get("order", "")["id"] ?? null;
            $filesSession = SessionManager::get("order", "")["files"] ?? null;
            $sessionId = SessionManager::get("user", null)["sessionid"] ?? null;
            if ($orderId === null || $filesSession == null) throw new Exception("you are not eror find order id or File session gone");
            if ($sessionId === null ) throw new Exception("cant find session id please go to firstPages");
            $validator = new Rakit\Validation\Validator();
            $validate = $validator->validate($input, [
                "nama" => "required",
                "no_wa" => ['required', 'regex:/^(08|62)[0-9]{8,12}$/'],
                "tanggal_ambil" => "required|date:Y-m-d\TH:i:sP",
                "files" => "required|array",

                "files.*.file_id" => "required",
                "files.*.orientasi" => "required",
                "files.*.warna" => ["required", 'regex:/^(berwarna|hitam putih)$/'],
                "files.*.kualitas" => "required",
                "files.*.skala" => "required",
                "files.*.jumlah_cetak" => "required|numeric|min:1",
                "files.*.custom_halaman" => ['regex:/^\d+(-\d+)?(,\d+(-\d+)?)*$/'],
                "files.*.catatan_tambahan" => "required",
                "files.*.paper_id" => "required",
                "files.*.layanan" => "nullable|array"
            ]);


            if ($validate->fails()) {
                return jsonResponse(
                    "Eror",
                    $validate->errors()->firstOfAll(),
                    true,
                    422
                );
            }
         
            $data = $validate->getValidatedData();
            $files = $data["files"];
            $filesessionCount = count($filesSession);
            $filesReqCount = count($files);

            if($filesessionCount !== $filesReqCount) return jsonResponse("the difference between the number of files provided and the files stored",
            [], true, 409);
            
            userControler::insertUser($sessionId, $data["nama"], $data["no_wa"]);
            foreach ($files as $index => $value) {
                //menghitung banyak pages di print
                $idFiles = $value["file_id"];
                if($idFiles !== $filesSession[$index]["id"]) return jsonResponse("
                    difrent id from req and sever saved. did you uploaded the same files id?
                ", [], true, 403);
                fileControler::insertUploader($sessionId, $idFiles);
                $totalPages = fileControler::getFileById($idFiles, "assoc", ["total_pages"])["total_pages"] ?? null;
                if ($totalPages == null) throw new Exception("cant find files");
                $pagesToPrint = self::hitungHalaman($value["custom_halaman"], $totalPages);
                $totalPagesToPrint =  $pagesToPrint * $value["jumlah_cetak"];
                $data["files"][$index]["pages_toprint"] = $totalPagesToPrint;

                $paperId = $value["paper_id"];
                //menghitung sub total
                $paper = paperControler::getPaper($paperId, "assoc") ?? null;
                if ($paper === null) throw new Exception("cant find paper");
                $subTotal = 0;
                foreach ($value["layanan"] as $ke => $layananId) {
                    $dbLayanan = layananControler::getLayanan($layananId, "assoc");
                    if ($dbLayanan === null) throw new Exception("cant find layanan");
                    $subTotal += $dbLayanan["harga"];
                    layanan_terpilihControler::insertLayanan($idFiles, $layananId);
                }

                switch ($value["warna"]) {
                    case "berwarna":
                        $subTotal += $totalPagesToPrint * $paper["harga_color"];
                        break;
                    case "hitam putih":
                        $subTotal += $totalPagesToPrint * $paper["harga_grayscale"];
                        break;
                    default:
                        throw new Exception("warna tidak dikenal: " . var_export($value["warna"], true));
                }

                configurationControler::insertConfiguration($idFiles, $data["files"][$index]);
                fileControler::insertSubtotal($idFiles, $subTotal);
                
                $data["files"][$index]["subtotal"] = $subTotal;
            }
            return jsonResponse("Succed post order", $data);
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function delete()
    {
        try {
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function get()
    {
        try {
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function put()
    {
        try {
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function patch()
    {
        try {
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function option()
    {
        try {
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }
}

switch ($METHOD) {
    case "POST":
        return template::post();
        return http_response_code(405);
    case "GET":
        return template::get();
        return http_response_code(405);
    case "PUT":
        return template::put();
        return http_response_code(405);
    case "DELETE":
        return template::delete();
        return http_response_code(405);
    case "OPTIONS":
        return template::option();
        return http_response_code(405);
    case "PATCH":
        return template::patch();
        return http_response_code(405);
}
