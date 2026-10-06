<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once dirname(__DIR__, 2) . "/vendor/autoload.php";
require_once dirname(__DIR__, 2) . "/Controler/MetodePembayaran.php";
require_once 'json.php';
require_once 'helper.php';

$METHOD = $_SERVER["REQUEST_METHOD"];

class template
{
    public static function post() {
        try{
            $input = getInput();
            verifiedToken();
            $validator = new Rakit\Validation\Validator();
            $validate = $validator->validate($input, [
                "nama" => "required|min:1|max:255",
                "webhook" => "required|min:1",
                "token" => "required",
                "image_path" => "required"
            ]);

            if($validate->fails()){
                throw new Exception(
                    implode(", ", $validate->errors()->all())
                );
            }
            
            $tokoSetting = SessionManager::get("admin", null);

            if($tokoSetting == null || empty($tokoSetting["pengaturanid"])) 
                throw new Exception("Cant find the pengatruan id please relogin again");
            
            $settingId = $tokoSetting["pengaturanid"];

            $validData = $validate->getValidatedData();
            MetodePembayaran::insertMetodePembayaran($settingId,
            $validData["nama"], $validData["webhook"],
            $validData["token"], $validData["image_path"]
            );
            unset($validData["token"]);
            return jsonResponse("succed to add data", $validData);
        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function delete(){
        try{
            $input = getInput();
            verifiedToken();
            $validator = new Rakit\Validation\Validator();
            $validate = $validator->validate($input, [
                "id" => "required",
            ]);

            if($validate->errors()->has("id")){
                throw new InvalidArgumentException(implode("",$validate->errors()->get("id")));
            }
            
            $validData = $validate->getValidatedData();
            $idMetod = $validData["id"];
        
            $affectedRow = MetodePembayaran::deleteMetodePembayaran($idMetod);

            if($affectedRow < 1){
                return jsonResponse("can't find data", $validData, true, 401);
            }
            return jsonResponse("succed delete data", $validData);
        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function get(){
        try{
            $databaseGet = MetodePembayaran::getMetodeAll(["HEX(id)", "nama", "image_path"]);
            return jsonResponse("succed get metodepembayaran", $databaseGet);
        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function put(){
        try{
            $input = getInput();
            verifiedToken();
            $validator = new Rakit\Validation\Validator();
            $validate = $validator->validate($input, [
                "id" => "required",
                "nama" => "required|min:1|max:255",
                "webhook" => "required|min:1",
                "token" => "required",
                "image_path" => "required"
            ]);

            if($validate->errors()->has("id")){
                throw new InvalidArgumentException(implode("",$validate->errors()->get("id")));
            }
            $validData = array_filter($validate->getValidatedData(), fn($v) => $v !== null);
            $idMetod = $validData["id"];
            
            unset($validData["id"]);
            $affectedrow = MetodePembayaran::updateMetodePembayaran($idMetod, $validData);
            unset($validData["token"]);
            return jsonResponse("succed to edit data", [
                "afecctedrow" => $affectedrow,
                "updateData" => $validData
            ]);
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
