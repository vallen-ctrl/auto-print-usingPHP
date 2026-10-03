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
            "username" => "required|min:10|max50",
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

    public static function updateUsernameOrPasswordOrName(string $id, array $usernameOrPasswor){
        $validator = new Rakit\Validation\Validator();

        $validation = $validator->validate($usernameOrPasswor, [
            "name" => "required|min:10|max:255",
            "username" => "required|min:10|max50",
            "password" => "required|min:8|max:255",
        ]);

        $dataValid = $validation->getValidatedData();
        return db::update("toko", $dataValid, "id", hex2bin($id));
    }

    public static function getTokoInformation(string $option = "all"){
        return db::get("SELECT name, username, tanggal_dibuat, tanggal_update FROM toko", [], $option);
    }

    public static function isValidPassword(string $username){
        $getUsername = db::getUsingParam("toko", "username", $username, "assoc", ["password"]);
        if($getUsername["password"] === $username){
            return true;
        }
        return false;
    }
}
