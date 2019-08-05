<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Description of Logger
 *
 * @author asok1
 */
class Logger
{

    public function __invoke(Request $request, Response $response, $next) {
        if (isset($_SERVER["REQUEST_TIME_FLOAT"])) {
            $start = $_SERVER["REQUEST_TIME_FLOAT"];
        } else {
            $start = microtime(true);
        }
        $response = $next($request, $response);
        $total = (microtime(true) - $start);
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $apiLog = new \App\Models\ApiLog;
        $apiLog->user_id = $request->getAttribute('user')->id;
        $apiLog->api = $method . " " . $path;
        $apiLog->param = json_encode($request->getParams());
        $apiLog->lead_time = $total;
        $apiLog->ip = $request->getAttribute('ip_address');
        $apiLog->agent = $request->getHeaderLine('User-Agent');
        $apiLog->referer = $request->getHeaderLine('HTTP_REFERER');
        $apiLog->save();
        return $response;
    }

}
