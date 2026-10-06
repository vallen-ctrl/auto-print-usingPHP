<?php

require_once dirname(__DIR__, 2) . "/Controler/filecontorler.php";
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . "/vendor/autoload.php";
require_once dirname(__DIR__, 2) . "/handler/session.php";
require_once 'json.php';
require_once 'helper.php';

$SAVE_DIR = rtrim(dirname(__DIR__, 2) . "/temp", '/\\') . '/';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["dokumen"])) {
    $parser = new \Smalot\PdfParser\Parser();
    if (!is_dir($SAVE_DIR)) {
        mkdir($SAVE_DIR, 0755, true);
    }

    $input = getInput();
    $validator = new Rakit\Validation\Validator();

    $validate = $validator->validate($input, [
        "layanan" => "required"
    ]);

    if ($validate->fails()) {
        return jsonResponse(implode(" ", $validate->errors()->all()), [], true, 403);
    }


    $allowedMimes = [];

    switch ($validate->getValidatedData()["layanan"]) {
        case "kilat-edit":
            $allowedMimes = [
                'pdf'  => ['application/pdf'],
                'doc'  => ['application/msword', 'application/octet-stream'],
                'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
                'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
                'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            ];
            break;
        case "kilat-langsung":
            $allowedMimes = [
                'pdf'  => ['application/pdf']
            ];
            break;
        case "terjadwal":
            $allowedMimes = [
                'pdf'  => ['application/pdf']
            ];
            break;
        default:
            return jsonResponse("you just have, kilat-edit, kilat-langsung, terjadwal. for your layanan", [], true, 403);
    }

    $file = $_FILES["dokumen"];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return jsonResponse(
            "Eror while uploading",
            [],
            true,
            401
        );
    }
    
    $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = preg_replace('/[^A-Za-z0-9]/','',pathinfo($file["name"], PATHINFO_FILENAME));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $fileSize = round($file["size"]/1024, 2);

    $newName = $fileName . ".(" . bin2hex(random_bytes(4)) . ').' . $ext;

    if (!isset($allowedMimes[$ext]) || !in_array($mime, $allowedMimes[$ext], true)) {
        return jsonResponse(
            "media type cant support",
            [],
            true,
            401
        );
    }
    $orderid = SessionManager::get("order", null)["id"] ?? null;
    if ($orderid === null) {
        $orderid = bin2hex(random_bytes(16));
        SessionManager::set("order", [
            "id" => $orderid
        ]);
    }

    // layananControler::insertLayanan("laminating", "laminating si itu", 20000);
    if(move_uploaded_file($file['tmp_name'], $SAVE_DIR . $newName)){
        $pdf_pages = $parser->parseFile($SAVE_DIR . $newName);
        $fileId = fileControler::insertFiles([
            "name" => $newName,
            "path" => "var/".$newName,
            "size" => $fileSize,
            "mime" => $mime,
            "order_id" => $orderid,
            "total_pages" => count($pdf_pages->getPages()),
            "layanan" => $validate->getValidatedData()["layanan"]
        ]);
        
        $filesSave = [
            "id" => $fileId,
            "pages" => count($pdf_pages->getPages())
        ];
        SessionManager::push("order", $filesSave, "files");
        return jsonResponse("succed save", [
            "files" => SessionManager::get("order")["files"]??[],
        ]);
    }else{
        return jsonResponse("failed save file", [
        ], 442);
    }
} else if (!isset($_FILES["dokumen"])) {

    echo json_encode([
        'status'  => 'failed',
        'message' => 'mohon untuk dokumen disertakan',
    ]);
    http_response_code(400);
}
// http_response_code(400);
