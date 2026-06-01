<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use Psr\Http\Message\ServerRequestInterface as Request;

class SessionService
{
    public static function getSessionToken(Request $request): ?string
    {
        // Check header first
        $header = $request->getHeaderLine('X-Session-Token');
        if (!empty($header)) {
            return $header;
        }

        // Check cookie
        $cookies = $request->getCookieParams();
        return $cookies['session_token'] ?? null;
    }

    public static function generateSessionToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function resolveCartIdentity(Request $request): array
    {
        $userId = $request->getAttribute('userId');
        $sessionToken = self::getSessionToken($request);

        return [
            'userId' => $userId,
            'sessionToken' => $sessionToken,
            'isGuest' => empty($userId),
        ];
    }
}
