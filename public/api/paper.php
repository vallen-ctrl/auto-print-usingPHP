<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once dirname(__DIR__, 2) . "/vendor/autoload.php";
require_once dirname(__DIR__, 2) . "/Controler/paperControler.php";
require_once 'json.php';
require_once 'helper.php';
$METHOD = $_SERVER["REQUEST_METHOD"];

class template
{
    public static function post() {
        $validator = new Rakit\Validation\Validator();
        try{
            
            verifiedToken();
            $validateInput = $validator->validate($_POST, [
                "name" => "required|min:1|max:5",
                "width" => "required|numeric|min:20",
                "height" => "required|numeric|min:23",
                "harga_color" => "required|numeric|min:1",
                "harga_grayscale" => "required|numeric|min:1",
            ]);

            if($validateInput->fails()){
                throw new InvalidArgumentException(implode(", ", $validateInput->errors()->all()));
            }

            $data = $validateInput->getValidatedData();
            paperControler::insertPaper($data);
            return jsonResponse("succed add paper");
        }catch(InvalidArgumentException | Exception$e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function delete(){
        $input = getInput();
        $validator = new Rakit\Validation\Validator();
        $validate = $validator->validate($input, [
            "id" => "required"
        ]);
        try{
            verifiedToken();
            if($validate->fails()){
                throw new InvalidArgumentException(
                    implode(", ", $validate->errors()->all())
                );
            }
            $paperID = $validate->getValidatedData()["id"];
            paperControler::deletePaper($paperID);
            return jsonResponse("succed delete paper with id -> $paperID");
        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function get(){
        try{
            return jsonResponse("succed get", paperControler::getPaper());
        }catch (InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function put(){
        $validator = new Rakit\Validation\Validator();
        try{
            verifiedToken();
            $input = getInput();
        
            $validateInput = $validator->validate($input, [
                "paperid" => "required",
                "name" => "required|min:1|max:50",
                "width" => "required|numeric|min:20",
                "height" => "required|numeric|min:23",
                "harga_color" => "required|numeric|min:1",
                "harga_grayscale" => "required|numeric|min:1",
            ]);

                
            if($validateInput->fails()){
                throw new InvalidArgumentException(implode(", ", $validateInput->errors()->all()));
            }
            $data = $validateInput->getValidatedData();
            $data = array_filter($data, fn($v) => $v !== null);
            $id = $data["paperid"];
            unset($data["paperid"]);
            $affectedRow = paperControler::updatePaper($id, $data);
            if($affectedRow <= 0){
                throw new Exception("there is no paper with id $id on database");
            }
            return jsonResponse("succed edit paper");
        }catch(InvalidArgumentException | Exception$e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function patch(){
        
    }

    public static function option(){

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
