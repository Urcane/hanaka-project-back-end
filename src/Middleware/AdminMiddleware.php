<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AdminMiddleware implements Middleware
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $userId = $request->getAttribute('userId');
        $userRole = $request->getAttribute('userRole');

        if (empty($userId)) {
            $response = new \Slim\Psr7\Response();
            $body = json_encode([
                'ok' => false,
                'error' => 'Token tidak valid atau sudah expired.',
            ]);
            $response->getBody()->write($body);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        if ($userRole !== 'admin') {
            $response = new \Slim\Psr7\Response();
            $body = json_encode([
                'ok' => false,
                'error' => 'Akses ditolak. Anda bukan admin.',
            ]);
            $response->getBody()->write($body);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        return $handler->handle($request);
    }
}
