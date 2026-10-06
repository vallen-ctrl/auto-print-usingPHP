<?php

    require_once dirname(__DIR__, 1) . "/vendor/autoload.php";
    require_once dirname(__DIR__, 1) . "/handler/database.php";

    use Ramsey\Uuid\Uuid;
    class MetodePembayaran{
        private static function chekLenBytes(string $id, int $len=32){
            if(strlen($id) > $len || strlen($id) < $len) throw new Exception("eror id expected 16 bytes (32 char)");
        }
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
            self::chekLenBytes($id);
            $hexTobinary = hex2bin($id);
            return db::update("metode_pembayaran", $columntoValue, "id", $hexTobinary);
        }

        public static function deleteMetodePembayaran(string $id){
            self::chekLenBytes($id);
            $hexTobinary = hex2bin($id);
            return db::delete("metode_pembayaran", "id", $hexTobinary);
        }

        public static function getMetodeById(string $idTabel, string $option = "all"){
            self::chekLenBytes($idTabel);
            return db::getUsingParam("metode_pembayaran", "id", hex2bin($idTabel), $option, ["nama"]);
        }

        public static function getMetodeAll(array $column = ["*"], string $option = "all"){
            return db::get("SELECT ". trim(implode(", ", $column)). " FROM metode_pembayaran", [],$option);
        }
        
    }
?>