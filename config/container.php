<?php

use Slim\Container;

/** @var \Slim\App $app */
$container = $app->getContainer();

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\Config;

$container['logger'] = function (Container $container) {
    $settings = $container->get('settings');
    $logger = new Logger($settings['logger']['name']);

    $level = $settings['logger']['level'];
    if (!isset($level)) {
        $level = Logger::DEBUG;
    }

    $logFile = $settings['logger']['file'];
    $handler = new RotatingFileHandler($logFile, 20, $level, true, 0775);
    $logger->pushHandler($handler);

    return $logger;
};

$container['errorHandler'] = function ($container) {
    return new App\Handlers\Error($container['logger']);
};

$container['phpErrorHandler'] = function ($container) {
    return new App\Handlers\Error($container['logger']);
};

$container['environment'] = function () {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_NAME'] = dirname(dirname($scriptName)) . '/' . basename($scriptName);
    return new Slim\Http\Environment($_SERVER);
};

$settings = $container->get('settings');

$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection($settings['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

if ((int) getenv('DEBUG.INFO')) {
    $capsule->connection()->enableQueryLog();
}
$container['db'] = function () use ($capsule) {
    return $capsule;
};

$container['validator'] = function () {
    return new App\Validation\Validator;
};

$container['httpClient'] = function () {
    return new GuzzleHttp\Client();
};

CacheManager::setDefaultConfig(new Config([
            "path" => sys_get_temp_dir(),
            "itemDetailedDate" => false,
            "itemDetailedDate" => true
        ]));

$container['cache'] = function () {
    return CacheManager::getInstance('files');
};
