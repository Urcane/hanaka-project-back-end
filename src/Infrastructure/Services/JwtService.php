<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtService
{
    private string $secret;
    private int $expiry;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? '';
        $this->expiry = (int) ($_ENV['JWT_EXPIRY'] ?? 86400);
    }

    public function createToken(string $userId, string $email, string $role = 'customer'): string
    {
        $now = time();
        $payload = [
            'sub' => $userId,
            'email' => $email,
            'role' => $role,
            'iat' => $now,
            'exp' => $now + $this->expiry,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function verifyToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
