<?php
    require_once __DIR__ . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $SAVE_DIR_FILES = $_ENV['SAVE_DIR'];
    
?>