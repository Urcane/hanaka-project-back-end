<?php

declare(strict_types=1);

namespace App\Owner\Middleware;

use App\Admin\Support\Cookie;
use App\Infrastructure\Repositories\UserRepository;
use App\Infrastructure\Services\JwtService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

/**
 * Web auth guard for the server-rendered owner panel.
 *
 * Mirrors AdminAuthMiddleware — same handoff cookie, same JWT — but demands the
 * `owner` role. An admin who lands here is sent back to their own panel rather
 * than to the login screen, so a mistyped URL never looks like a failed login.
 */
class OwnerAuthMiddleware implements Middleware
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

        if ($payload === null) {
            return $this->redirectTo($this->frontendLoginUrl());
        }

        $user = $this->userRepo->findById((string) $payload['sub']);
        if ($user === null) {
            return $this->redirectTo($this->frontendLoginUrl());
        }

        $role = (string) ($user['role'] ?? '');
        if ($role === 'admin') {
            return $this->redirectTo('/admin/dashboard');
        }
        if ($role !== 'owner') {
            return $this->redirectTo($this->frontendLoginUrl());
        }

        $request = $request
            ->withAttribute('userId', $user['id'])
            ->withAttribute('userEmail', $user['email'])
            ->withAttribute('userName', $user['full_name'])
            ->withAttribute('userRole', $user['role']);

        return $handler->handle($request);
    }

    private function redirectTo(string $location): \Psr\Http\Message\ResponseInterface
    {
        $response = new Response();
        return $response->withHeader('Location', $location)->withStatus(302);
    }

    private function frontendLoginUrl(): string
    {
        return rtrim($_ENV['FRONTEND_URL'] ?? 'http://localhost:5173', '/') . '/login';
    }
}
