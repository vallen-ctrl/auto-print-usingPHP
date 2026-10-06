<?php 
    require_once dirname(__DIR__, 1) . "/handler/session.php";
    require_once dirname(__DIR__, 1) . "/handler/database.php";
    require_once "layananControler.php";
    class layanan_terpilihControler{
        public static function getLayanan(string $filesId){
            $sessionid = session_id();
            if(empty($filesId)){
                 throw new InvalidArgumentException("Cant find files id please add it");
            }
            if(empty($sessionid)){
                 throw new ErrorException("cant find session id");
            }
            
            $query = "SELECT * FROM `layanan_terpilih` WHERE files_id = ?";
            return db::get($query, [$filesId]);
        }

        public static function insertLayanan(string $filesId, string $layananId){
            if(empty($filesId) && empty($layananId)){
                throw new InvalidArgumentException("expect files, layananid or order id, consider to add it");
            }

            $query = layananControler::getLayanan($layananId, "assoc");
            $namaLayanan = $query["nama"];

            $dataInsert = [
                "files_id" => hex2bin($filesId),
                "layanan_id" => $layananId,
                "nama_layanan" => $namaLayanan,
            ];

            return db::insert("layanan_terpilih", $dataInsert);
        }

        public static function deleteLayanan(int $idLayananTerpilih){
            db::delete("layanan_terpilih", "id", $idLayananTerpilih);
        }
    }
?>