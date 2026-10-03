<?php

use Ramsey\Uuid\Uuid;

require_once dirname(__DIR__, 1) . "/handler/database.php";
require_once dirname(__DIR__, 1) . "/vendor/autoload.php";
class tokoControler
{
    public static function insertToko(array $dataToko)
    {
        $validator = new Rakit\Validation\Validator();
        $uuid = Uuid::uuid7();

        $validation = $validator->validate($dataToko, [
            "name" => "required|min:10|max:255",
            "username" => "required|min:10|max:50",
            "password" => "required|min:8|max:255",
        ]);

        if ($validation->fails()) {
            throw new InvalidArgumentException(
                implode(', ', $validation->errors()->all())
            );
        }

        $data = $validation->getValidatedData();

        $data["id"] = $uuid->getBytes();
        db::insert("toko", $data);
        return $uuid->toString();
    }

    public static function updateUsernameOrPasswordOrName(string $id, array $usernameOrPasswor)
    {
        $validator = new Rakit\Validation\Validator();

        $validation = $validator->validate($usernameOrPasswor, [
            "name" => "required|min:10|max:255",
            "username" => "required|min:10|max:50",
            "password" => "required|min:8|max:255",
        ]);

        $dataValid = $validation->getValidatedData();
        return db::update("toko", $dataValid, "id", hex2bin($id));
    }

    public static function getTokoInformation(string $option = "all")
    {
        return db::get("SELECT name, username, tanggal_dibuat, tanggal_update FROM toko", [], $option);
    }

    public static function getTokoId(string $option = "all")
    {
        return db::get("SELECT id FROM toko", [], $option);
    }

    public static function setToken(string $tokoID):string{
        $token = random_bytes(32);
        db::update("toko", [
            "token" => $token
        ], "id", hex2bin($tokoID));
        return bin2hex($token);
    }

    public static function isValidPassword(string $username, string $password)
    {
        if (empty($username) || empty($password)) {
            throw new InvalidArgumentException("Username dan password wajib diisi");
        }

        $row = db::get("SELECT password, id FROM toko WHERE username = ?", [$username], "assoc");
        if (!$row || !isset($row["password"])) {
            return false; // username nggak ketemu
        }
        if($row["password"] === $password){
            return self::setToken(bin2hex($row["id"]));
        }
        return false;
    }

    public static function isTokenvalid(string $tokoID, string $token):bool
    {
        if (empty($token)) {
            throw new InvalidArgumentException("token wajib diisi");
        }

        $row = db::get("SELECT token FROM toko WHERE id = ?", [hex2bin($tokoID)], "assoc");
        if (!$row || !isset($row["token"])) {
            return false; // username nggak ketemu
        }

        if(bin2hex($row["token"]) === $token){
            return true;
        }
        return false;
    }
}
