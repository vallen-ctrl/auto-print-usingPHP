<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once dirname(__DIR__, 2) . "/vendor/autoload.php";
require_once dirname(__DIR__, 2) . "/Controler/userControler.php";
require_once 'json.php';
require_once 'helper.php';

$METHOD = $_SERVER["REQUEST_METHOD"];

class template
{

    private static function startSession():string
    {
        $sessionId = bin2hex(random_bytes(16));
        SessionManager::set("user", [
            "sessionid" => $sessionId
        ]);
        return $sessionId;
    }

    public static function post(array $data = null)
    {
        try {
            $input = $data ?? getInput();
            $validator = new Rakit\Validation\Validator();

            $validate = $validator->validate($input, [
                "name" => "required|min:4|max:255",
                "no_wa" => "required|min:10|max:20"
            ]);

            if($validate->fails()){
                throw new Exception(
                    implode(", ", $validate->errors()->all())
                );
            }

            $data = $validate->getValidatedData();
            $session = SessionManager::get("user", null);
            $sessionId = "";

            if ($session === null) {
                $sessionId = self::startSession();
            }else{
                $sessionId = $session["sessionid"];
            }
            userControler::insertUser($sessionId, $data["name"], $data["no_wa"]);
            jsonResponse("yourSessionId", [
                "sessionid" => $sessionId
            ]);
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function delete()
    {
        try {
            SessionManager::destroy();
            return jsonResponse("delete User complite");
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function get()
    {
        try {
            $sessionId = self::startSession();
            jsonResponse("yourSessionId", [
                "sessionid" => $sessionId
            ]);
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function put()
    {
        try {
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function patch()
    {
        try {
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function option()
    {
        try {
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }
}

switch ($METHOD) {
    case "POST":
        // return template::post();
        return http_response_code(405);
    case "GET":
        // return template::get();
        return http_response_code(405);
    case "PUT":
        // return template::put();
        return http_response_code(405);
    case "DELETE":
        // return template::delete();
        return http_response_code(405);
    case "OPTIONS":
        // return template::option();
        return http_response_code(405);
    case "PATCH":
        // return template::patch();
        return http_response_code(405);
}
