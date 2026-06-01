<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use RuntimeException;

/**
 * Thin client for the Midtrans Core API (QRIS).
 *
 * Uses Server Key (HTTP Basic auth) only — no client key needed for the
 * server-side /v2/charge flow. Sandbox vs production is toggled via
 * MIDTRANS_IS_PRODUCTION.
 */
class MidtransService
{
    private string $serverKey;
    private bool $isProduction;
    private string $acquirer;

    public function __construct()
    {
        $this->serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? '';
        $this->isProduction = filter_var($_ENV['MIDTRANS_IS_PRODUCTION'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->acquirer = $_ENV['MIDTRANS_QRIS_ACQUIRER'] ?? 'gopay';
    }

    public function isConfigured(): bool
    {
        return $this->serverKey !== '';
    }

    private function baseUrl(): string
    {
        return $this->isProduction
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    /**
     * Create a QRIS charge. Returns the raw Midtrans response which includes
     * `qr_string`, `actions[]` (generate-qr-code url), `expiry_time`,
     * `transaction_id`, and `transaction_status`.
     */
    public function chargeQris(string $orderId, int $grossAmount, array $customer = []): array
    {
        $payload = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'qris' => [
                'acquirer' => $this->acquirer,
            ],
        ];

        if (!empty($customer)) {
            $payload['customer_details'] = $customer;
        }

        return $this->request('POST', '/v2/charge', $payload);
    }

    /**
     * Fetch the live transaction status for an order id.
     */
    public function getStatus(string $orderId): array
    {
        return $this->request('GET', '/v2/' . rawurlencode($orderId) . '/status');
    }

    /**
     * Verify the SHA-512 signature key Midtrans sends with webhook
     * notifications: sha512(order_id + status_code + gross_amount + serverKey).
     */
    public function verifySignature(array $notif): bool
    {
        $expected = hash(
            'sha512',
            ($notif['order_id'] ?? '')
            . ($notif['status_code'] ?? '')
            . ($notif['gross_amount'] ?? '')
            . $this->serverKey
        );

        return hash_equals($expected, (string) ($notif['signature_key'] ?? ''));
    }

    /**
     * Map a Midtrans transaction_status (+ fraud_status) to our internal
     * payment_status enum: pending | paid | expired | failed.
     */
    public static function mapPaymentStatus(string $transactionStatus, ?string $fraudStatus = null): string
    {
        switch ($transactionStatus) {
            case 'capture':
                return ($fraudStatus === null || $fraudStatus === 'accept') ? 'paid' : 'pending';
            case 'settlement':
                return 'paid';
            case 'expire':
                return 'expired';
            case 'deny':
            case 'cancel':
            case 'failure':
                return 'failed';
            case 'pending':
            default:
                return 'pending';
        }
    }

    /**
     * Pull the hosted QR image url out of a charge response, if present.
     */
    public static function extractQrUrl(array $charge): ?string
    {
        foreach ($charge['actions'] ?? [] as $action) {
            if (($action['name'] ?? '') === 'generate-qr-code') {
                return $action['url'] ?? null;
            }
        }
        return null;
    }

    /**
     * Normalize the expiry returned by Midtrans into a UTC DateTimeImmutable.
     *
     * Midtrans returns `expiry_time` as "Y-m-d H:i:s" in WIB (Asia/Jakarta)
     * with NO offset, so we must attach the zone explicitly rather than rely
     * on PHP's ambiguous default timezone. Falls back to now+15min (the QRIS
     * default lifetime) when the field is missing or malformed.
     */
    public static function parseExpiry(?string $midtransExpiry): \DateTimeImmutable
    {
        $utc = new \DateTimeZone('UTC');

        if (!empty($midtransExpiry)) {
            $wib = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $midtransExpiry,
                new \DateTimeZone('Asia/Jakarta')
            );
            if ($wib !== false) {
                return $wib->setTimezone($utc);
            }
        }

        return (new \DateTimeImmutable('now', $utc))->modify('+15 minutes');
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Midtrans server key belum dikonfigurasi.');
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi PHP cURL tidak tersedia.');
        }

        $ch = curl_init($this->baseUrl() . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':'),
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $raw = curl_exec($ch);

        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Gagal menghubungi Midtrans: ' . $error);
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Respons Midtrans tidak valid.');
        }

        // Midtrans returns its own status_code (string) inside the body.
        $code = (int) ($data['status_code'] ?? $httpCode);
        if ($code >= 400) {
            $message = $data['status_message'] ?? ('Midtrans error ' . $code);
            throw new RuntimeException($message, $code);
        }

        return $data;
    }
}
