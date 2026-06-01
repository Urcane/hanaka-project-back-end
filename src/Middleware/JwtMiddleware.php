<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Infrastructure\Services\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class JwtMiddleware implements Middleware
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!empty($authHeader) && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = $this->jwtService->verifyToken($token);

            if ($payload !== null) {
                $request = $request->withAttribute('userId', $payload['sub']);
                $request = $request->withAttribute('userEmail', $payload['email']);
                $request = $request->withAttribute('userRole', $payload['role'] ?? 'customer');
            }
        }

        return $handler->handle($request);
    }
}
