<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once dirname(__DIR__, 2) . "/vendor/autoload.php";
require_once dirname(__DIR__, 2) . "/Controler/computerControler.php";
require_once 'json.php';
require_once 'helper.php';

$METHOD = $_SERVER["REQUEST_METHOD"];

class template
{
        public static function post() {
        try{
            $input = getInput();
            $validator = new Rakit\Validation\Validator();

            $data = $validator->validate($input, [
                "token" => "required|min:3"
            ]);

            if ($data->fails()) {
                throw new InvalidArgumentException(
                    implode(", ", $data->errors()->all())
                );
            }

            $valid = $data->getValidatedData();
            $getDbData = computerControler::getComputerToken($valid["token"]) ?? null;
            if($getDbData == null) throw new Exception("cant find computer with those token");

            if($valid["token"] !== bin2hex($getDbData["token"])){
                return jsonResponse("your token is invalid", [], true, 401);
            }

            $computerId = bin2hex($getDbData["id"]);
            computerControler::updateComputerStatus($computerId, 2);
        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }

        self::stream($computerId); // method stream() yang tadi
    }


    private static function stream(string $computerId){
        ignore_user_abort(true);
        set_time_limit(0);

        // timpa Content-Type JSON di atas
        header("Content-Type: text/event-stream");
        header("Cache-Control: no-cache");
        header("Connection: keep-alive");
        header("X-Accel-Buffering: no");

        while (ob_get_level() > 0) ob_end_flush();
        ob_implicit_flush(true);

        $done = false;
        $onDisconnect = function () use ($computerId, &$done) {
            if ($done) return;   // cegah kepanggil 2x
            $done = true;

            // === script saat putus ===
            computerControler::updateComputerStatus($computerId, 1); // 1 = offline (sesuaikan)
        };

        // jaga-jaga kalau script mati karena timeout / fatal error
        register_shutdown_function(function () use ($onDisconnect) {
            $onDisconnect();
        });

        $id = 0;
        while (true) {
            if (connection_aborted()) {
                $onDisconnect();
                break;
            }

            $id++;
            echo "id: $id\n";
            echo "event: message\n";
            echo "data: " . json_encode(["time" => date("H:i:s")]) . "\n\n";
            flush();

            sleep(2);
        }
    }

    public static function delete(){
        try{

        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }


    public static function put(){
        try{

        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function patch(){
        try{

        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function option(){
        try{

        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }
}

switch ($METHOD){
    case "POST":
        return template::post();
        return http_response_code(405);
    case "GET":
        // return template::get();
        return http_response_code(405);
    case "PUT":
        return template::put();
        return http_response_code(405);
    case "DELETE":
        return template::delete();
        return http_response_code(405);
    case "OPTIONS":
        return template::option();
        return http_response_code(405);
    case "PATCH":
        return template::patch();
        return http_response_code(405);
}
