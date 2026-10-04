<?php
    require_once dirname(__DIR__, 1) . "/handler/database.php";

    class paperControler{
        public static function insertPaper(array $paperConfiguration) {
            $validator = new Rakit\Validation\Validator();
            $validateInput = $validator->validate($paperConfiguration, [
                "name" => "required|min:1|max:50",
                "width" => "required|numeric|min:20",
                "height" => "required|numeric|min:23",
                "harga_color" => "required|numeric|min:1",
                "harga_grayscale" => "required|numeric|min:1",
            ]);

            if($validateInput->fails()){
                throw new InvalidArgumentException(implode(", ", $validateInput->errors()->all()));
            }

            return db::insert("paper", $validateInput->getValidatedData());
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