<?php 
    require_once "../../config.php";
    header('Content-Type: application/json; charset=utf-8');
    $SAVE_DIR = rtrim($SAVE_DIR_FILES, '/\\') . '/';
    if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["dokumen"])){
        if(!is_dir($SAVE_DIR)){
            mkdir($SAVE_DIR, 0755, true);
        }

        $allowedMimes = [
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword', 'application/octet-stream'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        ];

        $file = $_FILES["dokumen"];

        if($file['error'] !== UPLOAD_ERR_OK){
            echo 'eror while uploading the file';
            http_response_code(401);
            die("Upload gagal, kode Eror: ".$file['error']);
        }

        $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = pathinfo($file["name"], PATHINFO_FILENAME);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        $newName = $fileName . ".(" . bin2hex(random_bytes(5)) . ').' . $ext;
        
        if (!isset($allowedMimes[$ext]) || !in_array($mime, $allowedMimes[$ext], true)) {
            echo 'media type cant supoort';
            http_response_code(415);
            die('Tipe file tidak diizinkan');
        }

        if(move_uploaded_file($file['tmp_name'], $SAVE_DIR . $newName)){
            echo "Berhasil save to: $SAVE_DIR/" . $newName;
        }else{
            echo "Gagal weite file";
            http_response_code(442);
        }
    }else if(!isset($_FILES["dokumen"])){
        
        echo json_encode([
            'status'  => 'failed',
            'message' => 'mohon untuk dokumen disertakan',
        ]);
        http_response_code(400);
    }
    // http_response_code(400);
?>