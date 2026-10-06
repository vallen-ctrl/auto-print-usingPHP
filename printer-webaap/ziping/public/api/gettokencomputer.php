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
    public static function finishResponse(): void
    {
        ignore_user_abort(true);
        set_time_limit(360);

        if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            return;
        }

        // fallback: Apache mod_php / Laragon
        header('Connection: close');
        header('Content-Length: ' . ob_get_length());
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }
    public static function post()
    {
        try {
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
            $input = getInput();
            $validator = new Rakit\Validation\Validator();
            $tokoId = SessionManager::get("admin", null)["tokoid"] ?? null;
            if ($tokoId === null) throw new Exception("cant find admin session please relogin again");
            $validate = $validator->validate($input, [
                "name" => "required|min:2|max:100"
            ]);
            if ($validate->fails()) {
                return jsonResponse(
                    "Eror invalid arguments, cant find keys",
                    $validate->errors()->firstOfAll(),
                    true,
                    422
                );
            }
            ob_start();
            $data = $validate->getValidatedData();
            $createComputer = computerControler::createComputerToken($data["name"], $tokoId);
            jsonResponse(
                "this keys and computer Id, please keep it secred",
                $createComputer
            );

            self::finishResponse();
            sleep(300);
            $status = computerControler::getComputerById($createComputer["id"], ["status"])["status"] ?? null;
            if ($status === "not connected") {
                computerControler::deleteComputer($createComputer["id"]);
            }
            return;
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
