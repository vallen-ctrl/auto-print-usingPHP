<?php

require_once dirname(__DIR__, 1) . "/vendor/autoload.php";
require_once dirname(__DIR__, 1) . "/handler/database.php";
require_once "MetodePembayaran.php";
use Ramsey\Uuid\Uuid;

class orderControler
{
    public static function getOrder() {}
    public static function setOrder(array $orderSet)
    {
        $validator = new Rakit\Validation\Validator();
        $data = $validator->validate($orderSet, [
            "tanggal_ambil" => "required|date:Y-m-d H:i:s",
            "biaya_admin" => "required",
            "total_pembayaran" => "required",
            "total_dibayar" => "required",
            "pengaturan_id" => "required",
            "status_pembayaran" => "nullable",
            "metode_pembayaran" => "nullable",
            "id_metode_pembayaran" => "nullable",
            "session_id" => "required"
        ]);

        if ($data->fails()) {
            throw new InvalidArgumentException(
                implode(', ', $data->errors()->all())
            );
        }
        $dataValidate = $data->getValidatedData();

        $id = Uuid::uuid7();
        $dataValidate["id"] = $id->getBytes();
        db::insert("orders", $dataValidate);
        return $id->toString();
    }
    public static function updateTotaldibayar(string $orderID, int $totalIbayar){
        if($totalIbayar < 0){
            throw new InvalidArgumentException("expected total dibayar > 0");
        }
        return db::update("orders", [
            "total_dibayar" => $totalIbayar
        ], "id", hex2bin($orderID));
    }

    public static function updateMetodePembayaran(string $orderID, string $idMetodePembayaran){
        $getMetodePembayaranName = MetodePembayaran::getMetodeById($idMetodePembayaran, "assoc");
        $nameMetode = $getMetodePembayaranName["name"];

        if(empty($nameMetode)){
            throw new InvalidArgumentException("cant find name of metodePmebayaran from your id $idMetodePembayaran");
        }

        $insertedData = [
            "metode_pembayaran" => $nameMetode,
            "id_metode_pembayaran" => hex2bin($idMetodePembayaran)
        ];

        return db::update("orders", $insertedData, "id", hex2bin($orderID));
    }

    public static function updateStatusPembayara(string $orderId, string $status){
        return db::update("orders", [
            "status_pembayaran" => $status
        ], "id", hex2bin($orderId));
    }
    public static function delete() {}
}
