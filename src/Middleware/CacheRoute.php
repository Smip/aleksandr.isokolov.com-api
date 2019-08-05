<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Headers;

/**
 * Description of Logger
 *
 * @author asok1
 */
class CacheRoute
{

    private $cache;

    public function __construct($container, $timelife = 0) {
        $this->cache = $container->get('cache');
        $this->timelife = $timelife;
    }

    public function __invoke(Request $request, Response $response, $next) {
        if ($request->isGet()) {
            $user = $request->getAttribute('user');
            $type = $user ? "private" : "public";
            $path = $request->getUri()->getPath() . "?" . $request->getUri()->getQuery() . ($user ? "/user_id=" . $user->id : '');
            $path = preg_replace('/[{}()\/\\@:]/', '_', $path);
            $cache = $this->cache->getItem($path);
            if (!$cache->isHit()) {
                $response = $next($request, $response);
                if ($response->getStatusCode() == 200) {
                    $response = $response
                            ->withHeader('last-modified', sprintf(
                                            '%s',
                                            gmdate("D, d M Y H:i:s \G\M\T")
                            ))
                            ->withHeader('Expires', sprintf(
                                    '%s',
                                    gmdate("D, d M Y H:i:s \G\M\T", time() + $this->timelife)
                    ));
                    $cache->set([
                        $response,
                        (string) $response->getBody(),
                    ])->expiresAfter($this->timelife); //in seconds, also accepts Datetime
                    $this->cache->save($cache);
                } else {
                    return $response;
                }
            } else {
                // Last-Modified header and conditional GET check
                $lastModified = $cache->getCreationDate()->getTimestamp();
                $ifModifiedSince = $request->getHeaderLine('If-Modified-Since');
                if ($ifModifiedSince && $lastModified <= strtotime($ifModifiedSince)) {
                    return $response->withStatus(304);
                }
                [$response, $body] = $cache->get();
                $headers = new Headers;
                foreach ($response->getHeaders() as $header => $value) {
                    $headers->set($header, $value);
                }
                $response = (new Response($response->getStatusCode(), $headers))->write($body);
            }
            // Cache-Control header
            $response = $response->withHeader('Cache-Control', sprintf(
                            '%s, max-age=%s, must-revalidate',
                            $type,
                            $cache->getTtl()
            ));
            return $response;
        }
        $response = $next($request, $response);
        return $response;
    }

}
