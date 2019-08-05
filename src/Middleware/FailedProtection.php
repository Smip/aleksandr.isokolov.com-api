<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;
use Carbon\Carbon;
use App\Models\IpBlock;

/**
 * Description of FailedProtection
 *
 * @author asok1
 */
class FailedProtection
{

    const LOW = 1;
    const MEDIUM = 2;
    const HIGH = 3;
    const REQUEST_BLOCK = 2;
    const BLOCK_RESPONCE = [
        'status' => 'error',
        'data' => null,
        'messages' => ['Your Ip address is blocked due to a large number of requests with incorrect data.']
    ];

    public function __invoke(Request $request, Response $response, $next) {
        if ($this->checkBlocked($request)) {
            $response = $next($request, $response);
            if ($response->getStatusCode() == 401 or $response->getStatusCode() == 404) {
                $this->onFailedRequest($request);
            }
            return $response;
        } else {
            return $response->withJson(FailedProtection::BLOCK_RESPONCE, \Slim\Http\StatusCode::HTTP_TOO_MANY_REQUESTS);
        }
    }

    public function onFailedRequest($request, $level = FailedProtection::HIGH) {
        $block = IpBlock::firstOrNew(['ip' => $request->getAttribute('ip_address'), 'level' => $level]);
        if ((new \DateTime($block->expires_at)) > (new \DateTime('NOW'))) {
            $block->expires_at = (new \DateTime($block->expires_at))->modify('+1 minute')->format('Y-m-d H:i:s');
        } else {
            $block->failed_count = 0;
            $block->blocked = 0;
            $block->expires_at = (new \DateTime('NOW'))->modify('+30 minute')->format('Y-m-d H:i:s');
        }
        $block->failed_count++;
        if ($level == FailedProtection::HIGH and $block->failed_count >= 50) {
            $block->blocked = FailedProtection::REQUEST_BLOCK;
        } elseif ($level == FailedProtection::LOW and $block->failed_count >= 300) {
            $block->blocked = FailedProtection::REQUEST_BLOCK;
        }
        $block->save();
    }

    public function checkBlocked($request, $level = FailedProtection::HIGH) {
        $block = IpBlock::where('ip', '=', $request->getAttribute('ip_address'))
                ->where('level', '=', $level)
                ->where('expires_at', '>', Carbon::now())
                ->first();
        if ($block) {
            if ($block->blocked == FailedProtection::REQUEST_BLOCK) {
                return FALSE;
            }
        }
        return TRUE;
    }

}
