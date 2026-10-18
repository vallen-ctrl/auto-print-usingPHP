<?php

return [
    'server_key'   => env('MIDTRANS_SERVER_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
    'client_key'    => env('MIDTRANS_CLIENT_KEY')
];