<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once dirname(__DIR__, 2) . "/vendor/autoload.php";
require_once dirname(__DIR__, 2) . "/Controler/tokoControler.php";
require_once 'json.php';
require_once 'helper.php';
$METHOD = $_SERVER["REQUEST_METHOD"];

class toko
{
    public static function post() {
        $postData = getInput();
        try{
            tokoControler::insertToko($postData);
            jsonResponse("succed create", [
                "succed" => true
            ]);
        }catch(InvalidArgumentException $e){
            jsonResponse($e->getMessage(), [], true, 403);
        } catch(Exception $e){
            jsonResponse($e->getMessage(), [], true, 403);
        }
        
    }

    public static function delete(){
        $postData = $_POST;
        try{
            verifiedToken();
            jsonResponse("succed delete", [
                "succed" => true
            ]);
        }catch(InvalidArgumentException $e){
            jsonResponse($e->getMessage(), [], true, 403);
        } catch(Exception $e){
            jsonResponse($e->getMessage(), [], true, 403);
        }
    }

    public static function get(){
        try{
            
            $data = tokoControler::getTokoInformation();
            jsonResponse("succed Get toko", $data);

        }catch (Exception $e){
            jsonResponse($e->getMessage(), [], true, 403);
        }
    }
}

switch ($METHOD){
    case "POST":
        return toko::post();
    case "GET":
        return toko::get();
}
