<?php

if (version_compare(phpversion(), '7.1', '>=')) {
    ini_set( 'serialize_precision', -1 );
}

//error_reporting(E_ALL);
//set_error_handler(function ($severity, $message, $file, $line) {
//    if (error_reporting() & $severity) {
//        throw new \ErrorException($message, 0, $severity, $file, $line);
//    }
//});

/** @var Slim\App $app */
$app = require __DIR__ . '/../config/bootstrap.php';

// Start
$app->run();

