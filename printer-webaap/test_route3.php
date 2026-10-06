<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Test with the exact format from test
$c = \App\Models\Computer::factory()->create();
$id = bin2hex($c->id);
echo "ID: " . $id . PHP_EOL;
echo "URL: /api/computer/{$id}" . PHP_EOL;

$request = Request::create("/api/computer/{$id}", 'GET');
$response = $app->handle($request);
echo "Status: " . $response->getStatusCode() . PHP_EOL;
echo "Content: " . $response->getContent() . PHP_EOL;
