<?php
require_once dirname(__DIR__, 1) . "/handler/database.php";

class computerDb
{
    public static function createComputerToken(string $name, string $tokoID)
    {
        if (empty($name) || empty($tokoID)) {
            throw new InvalidArgumentException("expected name or tokoid");
        }
        $strtoBinary = hex2bin(str_replace("-", "", $tokoID));
        $randomString = rtrim(strstr(base64_encode(random_bytes(16)), "/+", "-_"), "=");

        $insertaData = [
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

    public static function getComputerToken(string $id){
        $str2bin = hex2bin($id);
        $result = db::get("SELECT token FROM computer WHERE id = ?", [$str2bin], "assoc");
        if(empty($result)){
            return "";
        }else{
            return bin2hex($result["token"]);
        }
    }
}
