<?php


function jsonResponse(string $msg, array $data = [], bool $isEror = false , int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "message" => $msg,
        "iseror" => $isEror,
        "data" => $data,
        "responsesCode" => $code
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
