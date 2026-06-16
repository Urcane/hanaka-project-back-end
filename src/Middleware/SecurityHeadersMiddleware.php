<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SecurityHeadersMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        $response = $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('X-XSS-Protection', '1; mode=block');

        // Default to JSON for the API, but preserve an explicitly-set content type
        // (e.g. text/html for the server-rendered admin panel).
        $contentType = $response->getHeaderLine('Content-Type');
        if ($contentType === '' || str_contains($contentType, 'application/json')) {
            $response = $response->withHeader('Content-Type', 'application/json');
        }

        return $response;
    }
}
