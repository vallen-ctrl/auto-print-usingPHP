<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

$request = Request::create('/api/computer/73b553f6159c42d7990e045a79cdd4eb', 'GET');
$response = $app->handle($request);
echo "Status: " . $response->getStatusCode() . PHP_EOL;
echo "Content: " . $response->getContent() . PHP_EOL;
