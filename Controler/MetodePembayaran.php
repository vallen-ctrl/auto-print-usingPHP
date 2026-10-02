<?php

    require_once dirname(__DIR__, 1) . "/vendor/autoload.php";
    require_once dirname(__DIR__, 1) . "/handler/database.php";

    use Ramsey\Uuid\Uuid;
    class MetodePembayaran{
        public static function insertMetodePembayaran(
            int $pengaturan, string $name, string $webhook,
            string $token, string $imagepath)
        {
            if(empty($name) ||empty($webhook) ||empty($token) || empty($imagepath)) {
                throw new InvalidArgumentException("expected get name or webhook or token or imagepath");
            }

            $uuid = Uuid::uuid7();
            $insertedTabel = [
                "id" => $uuid->getBytes(),
                "nama" => $name,
                "webhook" => $webhook,
                "token" => $token,
                "image_path" => $imagepath,
                "id_pengaturan" => $pengaturan
            ];
            db::insert("metode_pembayaran", $insertedTabel);
            return $uuid->toString();
        }

        public static function updateMetodePembayaran(string $id, array $columntoValue) {
            $hexTobinary = hex2bin($id);
            db::update("metode_pembayaran", $columntoValue, "id", $hexTobinary);
        }

        public static function deleteMetodePembayaran(string $id){
            $hexTobinary = hex2bin($id);
            db::delete("metode_pembayaran", "id", $hexTobinary);
        }

        public static function getMetodeById(string $idTabel, string $option = "all"){
            return db::getUsingParam("metode_pembayaran", "id", hex2bin($idTabel), $option);
        }

        public static function getMetodeAll(string $option = "all"){
            return db::get("SELECT * FROM metode_pembayaran", [],$option);
        }
        
    }
?>