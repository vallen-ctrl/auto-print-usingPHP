<?php
    require_once dirname(__DIR__, 1) . "/handler/database.php";

    class paperControler{
        public static function insertPaper(string $name, int $width, int $height) {
            if(strlen($name) > 8 ){
                throw new InvalidArgumentException("expected under 8 for the name");
            }   

            $inserted = [
                "name" => $name,
                "width" => $width,
                "height" => $height
            ];

            return db::insert("paper", $inserted);
        }

        public static function deletePaper(int $paperId){
            return db::delete("paper", "id" ,$paperId);
        }

        public static function updatePaper(int $paperId, array $columToValue){
            return db::update("paper", $columToValue, "id", $paperId);
        }

        public static function getPaper(int $paperId = -1, string $options = "all"){
            if($paperId < 0){
                return db::get("SELECT * FROM paper", [], $options);
            }
            return db::get("SELECT * FROM paper WHERE id = ?", [$paperId], $options);
        }
    }
?>