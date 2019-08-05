<?php

$settings = [];

// Slim settings
$settings['displayErrorDetails'] = getenv('DEBUG');
$settings['determineRouteBeforeAppMiddleware'] = true;
$settings['addContentLengthHeader'] = false;

// Path settings
$settings['root'] = dirname(__DIR__);
$settings['temp'] = $settings['root'] . '/tmp';
$settings['public'] = $settings['root'] . '/public';

$settings['twig'] = $settings['root'] . '/resources/views';

// Database settings
$settings['db']['driver'] = 'mysql';
$settings['db']['host'] = getenv('DB.HOST');
$settings['db']['username'] = getenv('DB.USERNAME');
$settings['db']['password'] = getenv('DB.PASSWORD');
$settings['db']['database'] = getenv('DB.DATABASE');
$settings['db']['charset'] = getenv('DB.CHARSET');
$settings['db']['collation'] = getenv('DB.COLLATION');
$settings['db']['prefix'] = '';

$settings['logger'] = [
    'name' => 'app',
    'file' => $settings['temp'] . '/logs/app.log',
    'level' => \Monolog\Logger::ERROR,
];

return $settings;
