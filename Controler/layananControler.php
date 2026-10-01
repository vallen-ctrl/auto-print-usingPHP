<?php
    require_once dirname(__DIR__, 1) . "/handler/database.php";

    class layananControler{
        public static function insertLayanan 
        (string $nama, string $deskripsi, int $harga)
        {
            if(empty($nama)||empty($deskripsi)||empty($harga)){
                throw new InvalidArgumentException("cant find nama, deskripsi or harga");
            }

            $insertData = [
                "nama" => $nama,
                "deskripsi" => $deskripsi,
                "harga" => $harga
            ];

            return db::insert("layanan", $insertData);
        }

        public static function deleteLayanan (int $idLayanan){
            if(empty($idLayanan)){
                throw new InvalidArgumentException("eror cant find id layanan");
            }
            return db::delete("layanan", "id", $idLayanan);
        }

        public static function updateLayanan(int $layananId, array $updatePack){
            return db::update("layanan",$updatePack, "id", $layananId);
        }

        public static function getLayanan(string $id = "all", string $option = "all"){
            if($id == "all"){
                $query = "SELECT * FROM layanan";
                return db::get($query, [], $option);
            }
            $query = "SELECT * FROM layanan WHERE id = ?";

            return db::get($query, [$id], $option);
            
        }
    }
?>