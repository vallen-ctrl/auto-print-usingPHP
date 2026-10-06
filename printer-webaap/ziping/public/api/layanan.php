<?php


header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once dirname(__DIR__, 2) . "/vendor/autoload.php";
require_once dirname(__DIR__, 2) . "/Controler/layananControler.php";
require_once 'json.php';
require_once 'helper.php';

$METHOD = $_SERVER["REQUEST_METHOD"];

class template
{
    public static function post() {
        $input = getInput();
        try{
            verifiedToken();
            $validator = new Rakit\Validation\Validator();

            $validate = $validator->validate($input,[
                "nama" => "required|min:1",
                "deskripsi" => "nullable",
                "harga" => "required|numeric|min:1"
            ]);

            if($validate->fails()){
                throw new InvalidArgumentException(
                    implode(", ", $validate->errors()->all())
                );
            }
            $data = $validate->getValidatedData();
            layananControler::insertLayanan($data["nama"], $data["deskripsi"], $data["harga"]);
            return jsonResponse("succed to add");
        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function delete(){
        try{
            verifiedToken();
            $input = getInput();
            $validator = new Rakit\Validation\Validator();

            $validate = $validator->validate($input,[
                "id" => "required|numeric|min:0",
            ]);
            if($validate->errors()->has("id")){
                throw new InvalidArgumentException(implode("",$validate->errors()->get("id")));
                }
            
            $id = $validate->getValidatedData()["id"];
            layananControler::deleteLayanan($id);    
            return jsonResponse("succed to delete id -> $id");
            
        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function get(){
        try{
            $data = layananControler::getLayanan();
            return jsonResponse("succed to get layanan", $data);
        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
    }

    public static function put(){
        try{
            $input = getInput();
        try{
            verifiedToken();
            $validator = new Rakit\Validation\Validator();

            $validate = $validator->validate($input,[
                "id" => "required|numeric|min:0",
                "nama" => "required|min:1",
                "deskripsi" => "required",
                "harga" => "required|numeric|min:1"
            ]);
            if($validate->errors()->has("id")){
                throw new InvalidArgumentException(implode("",$validate->errors()->get("id")));
            }
            $data = $validate->getValidatedData();
            $data = array_filter($data, fn($v) => $v !== null);
            $id = $data["id"];
            unset($data["id"]);
            layananControler::updateLayanan($id, $data);
            return jsonResponse("succed to edit");
        }catch(InvalidArgumentException | Exception $e){
            return jsonResponse($e->getMessage(), [], false, 403);
        }
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
