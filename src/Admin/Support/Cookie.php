<?php

declare(strict_types=1);

namespace App\Admin\Support;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Helpers for issuing cookies on a PSR-7 response without relying on the global
 * setcookie() / Slim emitter interplay. The admin panel stores the JWT (the very
 * same token the React frontend keeps in localStorage) in an HttpOnly cookie so
 * the session is shared across the two projects.
 */
class Cookie
{
    public const TOKEN = 'hanaka_admin_token';
    public const FLASH = 'hanaka_flash';

    /**
     * Append a Set-Cookie header to the response.
     */
    public static function set(
        Response $response,
        string $name,
        string $value,
        int $maxAge,
        bool $secure
    ): Response {
        $parts = [
            $name . '=' . rawurlencode($value),
            'Path=/',
            'Max-Age=' . $maxAge,
            'HttpOnly',
            'SameSite=Lax',
        ];
        if ($secure) {
            $parts[] = 'Secure';
        }

        return $response->withAddedHeader('Set-Cookie', implode('; ', $parts));
    }

    /**
     * Expire a cookie immediately.
     */
    public static function forget(Response $response, string $name, bool $secure): Response
    {
        return self::set($response, $name, '', 0, $secure)
            ->withAddedHeader('Set-Cookie', $name . '=; Path=/; Max-Age=0');
    }

    /**
     * Read a cookie value from the incoming request.
     */
    public static function read(Request $request, string $name): ?string
    {
        $cookies = $request->getCookieParams();
        return isset($cookies[$name]) ? (string) $cookies[$name] : null;
    }

    /**
     * Whether cookies should carry the Secure flag (true when served over HTTPS).
     */
    public static function isSecureRequest(Request $request): bool
    {
        if ($request->getUri()->getScheme() === 'https') {
            return true;
        }
        $forwarded = $request->getHeaderLine('X-Forwarded-Proto');
        return strtolower($forwarded) === 'https';
    }
}
