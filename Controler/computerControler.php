<?php

use Ramsey\Uuid\Uuid;

require_once dirname(__DIR__, 1) . "/handler/database.php";

class computerControler
{
    public static function createComputerToken(string $name, string $tokoID)
    {
        if (empty($name) || empty($tokoID)) {
            throw new InvalidArgumentException("expected name or tokoid");
        }
        $strtoBinary = hex2bin(str_replace("-", "", $tokoID));
        $randomString = random_bytes(16);

        $insertaData = [
            "id" => Uuid::uuid7()->getBytes(),
            "name" => substr($name, 0, 100),
            "token" => $randomString,
            "toko_id" => $strtoBinary
        ];

        db::insert("computer", $insertaData);

        return bin2hex($randomString);
    }

    public static function deleteComputer(string $id)
    {
        if (empty($name) || empty($tokoID)) {
            throw new InvalidArgumentException("expected name or tokoid");
        }

        $convertToBinary = hex2bin($id);

        db::delete("computer", "id", $convertToBinary);
    }

    public static function getComputerToken(string $id)
    {
        $str2bin = hex2bin($id);
        $result = db::get("SELECT token FROM computer WHERE id = ?", [$str2bin], "assoc");
        if (empty($result)) {
            return "";
        } else {
            return bin2hex($result["token"]);
        }
    }

    public static function getAllComputer(string $option = "all")
    {
        return db::get("SELECT HEX(id) AS id, name, HEX(toko_id) AS toko_id, aktif FROM computer", [], $option);
    }
}
