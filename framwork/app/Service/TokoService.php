<?php

namespace App\Services;

use App\Support\Db;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Rakit\Validation\Validator;

class TokoService
{
    public static function insertToko(array $dataToko)
    {
        $validator = new Validator();
        $uuid = Uuid::uuid7();

        $validation = $validator->validate($dataToko, [
            "name"     => "required|min:10|max:255",
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
        Db::insert("toko", $data);

        return str_replace("-", "", $uuid->toString());
    }

    public static function updateUsernameOrPasswordOrName(string $id, array $usernameOrPasswor)
    {
        $validator = new Validator();

        $validation = $validator->validate($usernameOrPasswor, [
            "name"     => "min:10|max:255",
            "username" => "min:10|max:50",
            "password" => "min:8|max:255",
        ]);

        if ($validation->fails()) {
            throw new InvalidArgumentException(
                implode(', ', $validation->errors()->all())
            );
        }

        $dataValid = $validation->getValidatedData();
        if (empty($dataValid)) {
            throw new InvalidArgumentException("Tidak ada data yang diubah");
        }

        return Db::update("toko", $dataValid, "id", hex2bin($id));
    }

    public static function chekTokoMax(): bool
    {
        $row = Db::get("SELECT COUNT(id) FROM toko", [], "row");
        return ((int) $row[0]) > 0;
    }

    public static function getTokoInformation(string $option = "all")
    {
        return Db::get("SELECT name, username, tanggal_dibuat, tanggal_update FROM toko", [], $option);
    }

    public static function getTokoId(string $option = "all")
    {
        return Db::get("SELECT id FROM toko", [], $option);
    }

    public static function setToken(string $tokoID): string
    {
        $token = random_bytes(32);
        Db::update("toko", ["token" => $token], "id", hex2bin($tokoID));
        return bin2hex($token);
    }

    public static function isValidPassword(string $username, string $password)
    {
        if (empty($username) || empty($password)) {
            throw new InvalidArgumentException("Username dan password wajib diisi");
        }

        $row = Db::get("SELECT password, id FROM toko WHERE username = ?", [$username], "assoc");
        if (!$row || !isset($row["password"])) {
            return false;
        }

        if (hash_equals($row["password"], $password)) {
            return self::setToken(bin2hex($row["id"]));
        }
        return false;
    }

    public static function isTokenvalid(string $tokoID, string $token): bool
    {
        if (empty($token)) {
            throw new InvalidArgumentException("token wajib diisi");
        }

        $row = Db::get("SELECT token FROM toko WHERE id = ?", [hex2bin($tokoID)], "assoc");
        if (!$row || !isset($row["token"])) {
            return false;
        }

        return hash_equals(bin2hex($row["token"]), $token);
    }
}