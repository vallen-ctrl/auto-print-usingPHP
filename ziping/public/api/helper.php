<?php

require_once dirname(__DIR__, 2) . "/handler/session.php";
require_once dirname(__DIR__, 2) . "/Controler/tokoControler.php";
function getInput(): array
{
    // if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //     return $_GET;
    // }

    $raw = file_get_contents("php://input");
    $json = json_decode($raw, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        return $json;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    parse_str($raw, $parsed);
    return $parsed;
}

function verifiedToken(){
    $session = SessionManager::get("admin", null);

    if($session == null){
        throw new Exception("Cant Find admin session");
    }

    $tokoid = $session["tokoid"];
    $token = $session["token"];
    if(tokoControler::isTokenvalid($tokoid, $token) === false){
        throw new Exception("You are not allow here!!");
    }

    return;
}

