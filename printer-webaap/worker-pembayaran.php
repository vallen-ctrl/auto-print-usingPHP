<?php

/**
 * Pintu masuk cron untuk worker pembayaran.
 *
 * Pakai file ini kalau hosting hanya mengizinkan cron memanggil file PHP:
 *   * * * * * php /path/ke/printer-webaap/worker-pembayaran.php
 *
 * Bisa juga memakai artisan langsung (lebih dianjurkan):
 *   * * * * * cd /path/ke/printer-webaap && php artisan pembayaran:cek
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArrayInput;

require __DIR__.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

$input = ['command' => 'pembayaran:cek'];

exit($app->handleCommand(new ArrayInput($input)));
