<?php
    require_once dirname(__DIR__, 1) . "/handler/database.php";

    class userControler {
        public static function insertUser(string $sessionId,string $name, string $noWa){
            if(empty($name) or empty($noWa) or empty($sessionId)){
                throw new InvalidArgumentException("can't find name, no wa, sessionID");
            }
            //085383895994
            if(strlen($noWa) < 10){
                throw new LengthException("expected len of nowa is greather then 10");
            }

            $trimNoWa = trim($noWa);
            $removeother = trim($trimNoWa, "-");

            $inserted = [
                "sessionid" => $sessionId,
                "name" => $name,
                "no_wa" => $removeother
            ];

            db::insert("user", $inserted);
        }
    }
?>