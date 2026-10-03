<?php

use function PHPSTORM_META\type;

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once dirname(__DIR__, 2) . "/vendor/autoload.php";
require_once dirname(__DIR__, 2) . "/handler/session.php";
require_once dirname(__DIR__, 2) . "/Controler/tokoControler.php";
require_once 'json.php';
$METHOD = $_SERVER["REQUEST_METHOD"];

class template
{
    public static function post()
    {
        try {
            $username = $_POST["username"] ?? '';
            $password = $_POST["password"] ?? '';
            $boolhasil = tokoControler::isValidPassword($username, $password);

            if (gettype($boolhasil) == "string") {

                $tokoID = tokoControler::getTokoId("assoc");
                $id = bin2hex($tokoID["id"]);
                SessionManager::set("admin", [
                    "tokoid" => $id,
                    "token" => $boolhasil
                ]);
                return jsonResponse("login status", [
                    "islogin" => $boolhasil
                ]);
            }else if($boolhasil === false){
                return jsonResponse("login status", [
                    "islogin" => $boolhasil
                ]);
            }
        } catch (InvalidArgumentException | TypeError $e) {
            jsonResponse($e->getMessage(), [], false, 403);
        } catch (InvalidArgumentException $e) {
            jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function delete() {}

    public static function get() {}

    public static function put() {}

    public static function patch() {}

    public static function option() {}
}

switch ($METHOD) {
    case "POST":
        return template::post();
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
