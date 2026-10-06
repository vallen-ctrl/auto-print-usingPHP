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
    public static function post()
    {
        try {
            verifiedToken();
            $input = getInput();
            $validator = new Rakit\Validation\Validator();

            $data = $validator->validate($input, [
                "name" => "required|min:3",
            ]);
            $tokoId = SessionManager::get("admin", null);
            if ($data->fails()) {
                throw new InvalidArgumentException(
                    implode(", ", $data->errors()->all())
                );
            }
            if ($tokoId === null) throw new Exception("You not autorized");
            $valid = $data->getValidatedData();
            $computerToken = computerControler::createComputerToken($valid["name"], $tokoId["tokoid"]);
            return jsonResponse("succed create new computer", [
                "token" => $computerToken
            ]);
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function delete()
    {
        try {
        } catch (InvalidArgumentException | Exception $e) {
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function get()
    {
        try {
            verifiedToken();
            $hasil = computerControler::getAllComputer();
            
            return jsonResponse("succed get all computers", [
                $hasil
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
        return template::post();
        return http_response_code(405);
    case "GET":
        return template::get();
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
