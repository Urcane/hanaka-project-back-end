<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Support\Cookie;
use App\Admin\Support\View;
use App\Infrastructure\Services\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Handles the cross-project login handoff.
 *
 * Customers/admins log in on the React frontend, which keeps the JWT in
 * localStorage. When an admin logs in, the frontend redirects here with that
 * token; we verify it, drop it into an HttpOnly cookie (same token, now usable
 * by server-rendered pages) and forward to the dashboard.
 */
class SessionController extends Controller
{
    private JwtService $jwtService;

    public function __construct(View $view, JwtService $jwtService)
    {
        parent::__construct($view);
        $this->jwtService = $jwtService;
    }

    /**
     * GET /admin/login?token=<jwt> — establish the admin session cookie.
     */
    public function login(Request $request, Response $response): Response
    {
        $token = (string) ($request->getQueryParams()['token'] ?? '');
        $payload = $token !== '' ? $this->jwtService->verifyToken($token) : null;

        if ($payload === null || ($payload['role'] ?? '') !== 'admin') {
            return $this->redirect($response, $this->frontendLoginUrl());
        }

        $expiry = (int) ($_ENV['JWT_EXPIRY'] ?? 86400);
        $response = Cookie::set(
            $response,
            Cookie::TOKEN,
            $token,
            $expiry,
            Cookie::isSecureRequest($request)
        );

        return $this->redirect($response, '/admin/dashboard');
    }

    /**
     * GET /admin/logout — drop the session cookie and return to the frontend login.
     */
    public function logout(Request $request, Response $response): Response
    {
        $response = Cookie::forget($response, Cookie::TOKEN, Cookie::isSecureRequest($request));
        return $this->redirect($response, $this->frontendLoginUrl());
    }
}
