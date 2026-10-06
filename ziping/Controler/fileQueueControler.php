<?php
    require_once dirname(__DIR__, 1) . "/handler/database.php";

    class fileQueueControler{
        public static function insertQueue(
            string $path, string $orderId, 
            string $fileId, string $computerId){

            $inseted = [
                "stasus" => "pending",
                "path" => $path,
                "order_id" => hex2bin($orderId),
                "file_id" => hex2bin($fileId),
                "computer_id" => hex2bin($computerId)
            ];

            return db::insert("file_queue", $inseted);
        }

        public static function deleteQueue(int $id){
            return db::delete("file_queue", "id", $id);
        }

        public static function update(int $id, array $columnToUpdate){
            return db::update("file_queue", $columnToUpdate, "id", $id);
        }

        public static function getFileQueueUsingComputerId(string $computerId){
            return db::getUsingParam("file_queue", "file_id", $computerId);
        }

        public static function getFileQueueAll(){
            return db::get("SELECT * FROM file_queue");
        }
    }
?>