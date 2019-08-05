<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write("It works!");

    return $response;
});

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->group('/map', function () use ($app) {
    $app->get('', App\Controllers\MapController::class . ':list');
    $app->put('', App\Controllers\MapController::class . ':put');
    $app->get('/wikimapia', App\Controllers\MapController::class . ':wikimapia_search')->add(new App\Middleware\CacheRoute($app->getContainer(), 24 * 60 * 60));
    $app->get('/{slug}', App\Controllers\MapController::class . ':get');
    $app->post('/{slug}', App\Controllers\MapController::class . ':post');
    
});
