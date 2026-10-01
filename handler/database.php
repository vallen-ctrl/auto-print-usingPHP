<?php
   
    class db {
        private static string $host = "127.0.0.1"; //ganti jadi localhost kalau eror di line 38
        private static string $username = "root";
        private static string $password = "";
        private static string $dbName = "marketplace";
        private static bool $isConnected = false;
        private static string $scheme = __DIR__ . "/scheme.sql";
        private static mysqli $conn;

        private static function runScheme(){
            if(!self::$isConnected || !isset(self::$conn)){
                die("cant find the connection, please start the connection first");
            }

            if(!file_exists(self::$scheme)){
                die("cant find scheme please consider to add scheme");
            }

            $content = file_get_contents(self::$scheme);

            $execute = mysqli_multi_query(self::$conn, $content);

            if($execute){
                do{
                    if($result = mysqli_store_result(self::$conn)){
                        mysqli_free_result($result);
                    }
                }while(mysqli_next_result(self::$conn));
            }else{
                die("gagal dijalankan skema: " . mysqli_error(self::$conn));
            }
        }

        public static function start(){
            if(!self::$isConnected){
                self::$conn = mysqli_connect(self::$host, self::$username, self::$password);
                self::$isConnected = true;
            }
            
            try{
                mysqli_select_db(self::$conn,self::$dbName);
            }catch(Exception $e){
                mysqli_query(self::$conn, "CREATE DATABASE IF NOT EXISTS `" . self::$dbName . "`");
                mysqli_select_db(self::$conn, self::$dbName);
                self::runScheme();
            }
        }

        public static function execute(string $query, array $param = []) : mysqli_result|bool {
            return mysqli_execute_query(self::$conn, $query, $param);
        }

        public static function insert(string $tabel, array $colum_to_value){
            self::start();
            if(empty($tabel)) throw new InvalidArgumentException("Please makesure the table is there");

            $cols = [];
            $vals = [];
           
            foreach ($colum_to_value as $key => $value) {
                if ($value === null) {
                    throw new InvalidArgumentException("Nilai untuk kolom '$key' null");
                }
                
                $cols[] = "`$key`";
                $vals[] = $value;
            }

            $placeholder = implode(', ', array_fill(0, count($cols), "?"));
            $sql = "INSERT INTO `$tabel` (" . implode(", ", $cols) . ") VALUES ($placeholder)";

            self::execute($sql, $vals);
            return mysqli_insert_id(self::$conn);
        }

        public static function update(string $tabel, array $data, string $whereCol, int | string $whereVal): int{
            self::start();
            $set = implode(", ", array_map(fn($item) => "`$item` = ?", array_keys($data)));
            $val = [...array_values($data), $whereVal];
            $sql = "UPDATE `$tabel` SET $set WHERE `$whereCol` = ? ";
            self::execute($sql, $val);
            return mysqli_affected_rows(self::$conn);
        }

        public static function delete(string $tabel, string $whereCol, int|string $whereVal){
            self::start();
            $sql = "DELETE FROM `$tabel` WHERE `$whereCol` = ?";
            self::execute($sql, [$whereVal]);
            return mysqli_affected_rows(self::$conn);
        }
        public static function get(string $query, array $param = [], string $option = "all"){
            self::start();
            switch($option){
                case "row":
                    return mysqli_fetch_row(self::execute($query, $param));
                case "assoc":
                return mysqli_fetch_assoc(self::execute($query, $param));
                default:
                    return mysqli_fetch_all(self::execute($query, $param));
            }
        }
    }
?>