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
            ], "id", $filesiD);
        }
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
        ]);
        if ($validation->fails()) {
            throw new InvalidArgumentException(
                implode(', ', $validation->errors()->all())
            );
        } else {
            $fileMetadata["id"] = $id;
            db::insert("files", $fileMetadata);
            return $uuid->toString();
        }
    }
}
