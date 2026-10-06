<?php

require_once dirname(__DIR__, 1) . "/handler/database.php";
require_once dirname(__DIR__, 1) . "/vendor/autoload.php";
require_once "userControler.php";

class configurationControler
{
    public static function insertConfiguration(string $fileid, array $configuration)
    {
        $validator = new Rakit\Validation\Validator;

        $conf = $validator->validate($configuration, [
            "orientasi" => "required",
            "warna" => "required",
            "kualitas" => "required",
            "skala" => "nullable",
            "jumlah_cetak" => "numeric|min:1",
            "custom_halaman" => "nullable",
            "catatan_tambahan" => "nullable",
            "pages_toprint" => "required|numeric",
            "paper_id" => "required|numeric"
        ]);

        if ($conf->fails()) {
            throw new InvalidArgumentException(
                implode(', ', $conf->errors()->all())
            );
        }

        $data = $conf->getValidData();

        $dbPaper = paperControler::getPaper($data["paper_id"], "assoc");
        $paperWidth =  $dbPaper["name"]. "|" . $dbPaper["height"] . " x " . $dbPaper["width"];

        $data["ukuran"] = $paperWidth;
        $data["file_id"] = hex2bin($fileid);

        return db::insert("configuration", $data);
    }

    public static function deleteConfiguration(int $id){
        return db::delete("configuration", "id", $id);
    }

    public static function updateConfiguration(int $id, array $columntoEdit){
        return db::update("configuration", $columntoEdit, "id", $id);
    }

    private static function query(string $fromWhere, int | string $key , string $option = "all"){
        $sql = "SELECT * FROM configguration WHERE " . $fromWhere . " = ?";
        $keys = $key;
        if(gettype($key) === "string"){
            $keys = hex2bin($key);
        }

        return db::get($sql, [$keys], $option);
    }

    public static function getConfigurationFromFileId(string $fileid, string $option = "all"){
        return self::query("file_id", $fileid, $option);
    }

    public static function getConfigurationFromId(int $id, string $option = "all"){
        return self::query("id", $id, $option);
    }
}
