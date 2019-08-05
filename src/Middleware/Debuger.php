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
class Debuger
{

    private $container;

    public function __construct($container) {
        $this->db = $container->get('db');
    }

    public function __invoke(Request $request, Response $response, $next) {
        if ((int) getenv('DEBUG.INFO')) {
            $startTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
            if (isset($_SERVER["REQUEST_TIME_FLOAT"])) {
                $start = $_SERVER["REQUEST_TIME_FLOAT"];
            } else {
                $start = microtime(true);
            }
            $response = $next($request, $response);

            if ($response->getHeaderLine('Content-Type') and $response->getHeaderLine('Content-Type') == 'application/json') {
                $total = (microtime(true) - $start) * 1000;
                $json = json_decode($response->getBody());
                $json->debug = [
                    'timeToStart' => $startTime * 1000,
                    'timeToRun' => $total,
                    'sqlLogs' => $this->db->connection()->getQueryLog()
                ];
                return $response->withJson($json);
            } elseif ($response->hasHeader('Content-Type') and $response->getHeaderLine('Content-Type') == 'application/xml;charset=utf-8') {
//                var_dump($this->db->connection()->getQueryLog());
                return $response;
            } else {
                return $response;
            }
        } else {
            return $next($request, $response);
        }
    }

}
