<?php

declare(strict_types=1);

namespace App\Admin\Support;

use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * One-shot flash messages carried across a redirect via a short-lived cookie.
 */
class Flash
{
    /**
     * Encode a flash payload for storage in a cookie value.
     */
    public static function encode(string $type, string $text): string
    {
        return base64_encode(json_encode(['type' => $type, 'text' => $text], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Read and decode the flash message from the request cookie, if any.
     *
     * @return array{type:string,text:string}|null
     */
    public static function read(Request $request): ?array
    {
        $raw = Cookie::read($request, Cookie::FLASH);
        if ($raw === null || $raw === '') {
            return null;
        }

        $decoded = json_decode((string) base64_decode($raw, true), true);
        if (!is_array($decoded) || empty($decoded['text'])) {
            return null;
        }

        return [
            'type' => in_array(($decoded['type'] ?? ''), ['success', 'error'], true) ? $decoded['type'] : 'success',
            'text' => (string) $decoded['text'],
        ];
    }
}
