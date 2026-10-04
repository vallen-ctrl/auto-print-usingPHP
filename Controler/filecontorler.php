<?php
require_once dirname(__DIR__, 1) . "/handler/database.php";
require_once dirname(__DIR__, 1) . "/vendor/autoload.php";
// require dirname(__DIR__, 1) . "/handler/validation.php";

use Ramsey\Uuid\Uuid;

class fileControler
{
    public static function insertUploader(string $sessionID, string $filesiD){
        if(empty($sessionID) || empty($filesiD)){
            throw new InvalidArgumentException("can't find session id or filesid, consider to add it");
        }else{
            return db::update("files", [
                "diupload_oleh" => $sessionID
            ], "id", hex2bin($filesiD));
        }
    }

    public static function insertSubtotal(string $fileid, int $subTotal){
        if(empty($fileid)) throw new InvalidArgumentException("Cant find file id");
        if($subTotal < 0) throw new InvalidArgumentException("sub total anda kurang dari 0");
        return db::update("files", [
            "subtotal" => $subTotal
        ], "id", hex2bin($fileid));
    }

    public static function deleteFile(string $fileid){
        return db::delete("files", "id", $fileid);
    }

    public static function insertFiles(array $fileMetadata): string
    {
        $uuid = Uuid::uuid7();
        $validator = new Rakit\Validation\Validator;
        $id = $uuid->getBytes();

        $validation = $validator->validate($fileMetadata, [
            "name" => "required|min:3",
            "path" => "required|min:3",
            "size" => "required|numeric",
            "mime" => "required",
            "order_id" => "required",
            "total_pages" => "required|numeric"
        ]);
        if ($validation->fails()) {
            throw new InvalidArgumentException(
                implode(', ', $validation->errors()->all())
            );
        } else {
            $fileMetadata["id"] = $id;
            $fileMetadata["order_id"] = hex2bin($fileMetadata["order_id"]);
            db::insert("files", $fileMetadata);
            return str_replace("-", "", $uuid->toString());
        }
    }
    public static function getFileById(string $fileId, string $option = "all", array $column = ["*"]){
        if(empty($fileId))
            throw new InvalidArgumentException("file id is emty");
        return db::getUsingParam("files", "id", $fileId, $option, $column);
    }
}
