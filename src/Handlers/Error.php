<?php

namespace App\Handlers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Monolog\Logger;

final class Error extends \Slim\Handlers\Error
{

    protected $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function __invoke(Request $request, Response $response, $exception) {
        // Log the message
        $this->logger->critical($exception->getMessage(), [
            'method' => $request->getMethod(),
            'url' => $request->getUri(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'headers' => $request->getHeaders(),
            'body' => $request->getParams()
        ]);
        $this->logger->critical($exception->getTraceAsString());

        // create a JSON error string for the Response body
        $body = json_encode([
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $response
                        ->withStatus(500)
                        ->withHeader('Access-Control-Allow-Origin', '*')
                        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization, Method, Headers')
                        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                        ->withHeader('Content-type', 'application/json')
                        ->withBody(new \Slim\Http\Body(fopen('php://temp', 'r+')))
                        ->write($body);
    }

}
