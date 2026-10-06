<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$c = \App\Models\Computer::factory()->create();
echo "ID: " . bin2hex($c->id) . PHP_EOL;
echo "Token: " . bin2hex($c->token) . PHP_EOL;
