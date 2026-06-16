<?php

declare(strict_types=1);

namespace App\Admin\Middleware;

use App\Admin\Support\Cookie;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Services\JwtService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

/**
 * Web auth guard for the server-rendered admin panel.
 *
 * Unlike the API's JwtMiddleware (which reads an Authorization: Bearer header),
 * browser navigation cannot send that header, so the JWT is read from the
 * `hanaka_admin_token` cookie — the same token the React frontend issued at login.
 * Anyone who is not a logged-in admin is bounced to the frontend login screen.
 */
class AdminAuthMiddleware implements Middleware
{
    private JwtService $jwtService;
    private UserRepository $userRepo;

    public function __construct(JwtService $jwtService, UserRepository $userRepo)
    {
        $this->jwtService = $jwtService;
        $this->userRepo = $userRepo;
    }

    public function process(Request $request, RequestHandler $handler): \Psr\Http\Message\ResponseInterface
    {
        $token = Cookie::read($request, Cookie::TOKEN);
        $payload = $token ? $this->jwtService->verifyToken($token) : null;

        if ($payload === null || ($payload['role'] ?? '') !== 'admin') {
            return $this->bounceToLogin();
        }

        $user = $this->userRepo->findById((string) $payload['sub']);
        if ($user === null || ($user['role'] ?? '') !== 'admin') {
            return $this->bounceToLogin();
        }

        $request = $request
            ->withAttribute('userId', $user['id'])
            ->withAttribute('userEmail', $user['email'])
            ->withAttribute('userName', $user['full_name'])
            ->withAttribute('userRole', $user['role']);

        return $handler->handle($request);
    }

    private function bounceToLogin(): \Psr\Http\Message\ResponseInterface
    {
        $base = rtrim($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173', '/');
        $response = new Response();
        return $response
            ->withHeader('Location', $base . '/login')
            ->withStatus(302);
    }
}
