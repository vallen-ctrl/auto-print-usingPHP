<?php
    require_once dirname(__DIR__, 1) . "/handler/database.php";
    class pengaturanTokoContorler{
        public static function insertToko(string $idToko ,int $biayaAdmin, int $biayaLayanan, int $lamaHari){
            if ($biayaAdmin < 0 or $biayaLayanan < 0 or $lamaHari < 0) {
                throw new InvalidArgumentException("Biaya Admin lamahari, dan Layanan harus lebih dari samadengan 0");
            }

            $insertedValue = [
                "status_buka" => 0,
                "biaya_admin" => $biayaAdmin,
                "id_toko" => hex2bin($idToko),
                "biaya_layanan" => $biayaLayanan,
                "lama_file_dihapus" => $lamaHari
            ];

            return db::insert("pengaturan_toko", $insertedValue);
        }

        public static function getPengaturanByTokoId(string $idToko, array $column = ["*"])
        {
            return db::getUsingParam("pengaturan_toko", "id_toko", $idToko, "assoc", $column);
        }

        public static function deletePengaturanToko(int $idToko){
            return db::delete("pengaturan_toko", "id", $idToko);
        }
    }
?>