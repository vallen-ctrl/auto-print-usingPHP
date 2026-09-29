<?php
    class SessionManager{
        private static bool $started = false;
        private static int $TTL = 60*25; //25 menit

        public static function start(): void{
            if(!self::$started && session_status() === PHP_SESSION_NONE){
                session_start();
                self::$started = true;
            }
        }

        public static function set(string $key, mixed $value):void {
            self::start();
            $_SESSION[$key] = $value;
        }

        public static function get(string $key, mixed $default = null): mixed{
            self::start();
            return $_SESSION[$key]  ?? $default;
        }

        public static function has(string $key):bool{
            self::start();
            return isset($_SESSION[$key]);
        }

        public static function remove (string $key): void{
            self::start();
            unset($_SESSION[$key]);
        }

        public static function destroy(): void{
            self::start();
            session_unset();
            session_destroy();
        }
    }
?>