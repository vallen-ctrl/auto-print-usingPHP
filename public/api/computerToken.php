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

        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function delete(){
        try{

        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function get(){
        try{
            verifiedToken();
            $input = getInput();
            $validator = new Rakit\Validation\Validator();

            $data = $validator->validate($input, [
                "computerid" => "required|min:3",
            ]);
            
            if ($data->fails()) {
                throw new InvalidArgumentException(
                    implode(", ", $data->errors()->all())
                );
            }
            $valid  = $data->getValidatedData()["computerid"];
            $getDbData = computerControler::getComputerToken($valid);
            jsonResponse("Succed get options", [
                "token" => $getDbData
            ]);
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
