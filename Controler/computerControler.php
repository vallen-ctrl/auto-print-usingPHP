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
        $strtoBinary = hex2bin($tokoID);
        $randomString = random_bytes(16);

        $insertaData = [
            "id" => Uuid::uuid7()->getBytes(),
            "name" => substr($name, 0, 100),
            "token" => $randomString,
            "toko_id" => $strtoBinary
        ];

        db::insert("computer", $insertaData);

        return [
            "token" => bin2hex($randomString),
            "id" => bin2hex($insertaData["id"])
        ];
    }

    public static function updateComputerStatus (string $computerId, int $intStatus){
        $status = ["not connected", "pending", "connected", "shutdown", "warning"];
        return db::update("computer", [
            "status" => $status[$intStatus]
        ], "id", hex2bin($computerId));
    }

    public static function deleteComputer(string $id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException("expected id");
        }

        $convertToBinary = hex2bin($id);

        return db::delete("computer", "id", $convertToBinary);
    }

    public static function getComputerToken(string $token)
    {
        $str2bin = hex2bin($token);
        $result = db::get("SELECT token, id FROM computer WHERE token = ?", [$str2bin], "assoc");
        if (empty($result)) {
            return null;
        } else {
            return $result;
        }
    }

    public static function getComputerById(string $id, array $colum = ["*"]){
        return db::getUsingParam("computer", "id", $id, "assoc", $colum);
    }

    public static function getAllComputer(string $option = "all")
    {
        return db::get("SELECT HEX(id) AS id, name, HEX(toko_id) AS toko_id, status, HEX(token), tanggal_dibuat  FROM computer", [], $option);
    }
}
