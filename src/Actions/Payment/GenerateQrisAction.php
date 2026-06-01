<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use App\Infrastructure\Services\MidtransService;
use App\Infrastructure\Services\SessionService;
use Psr\Http\Message\ResponseInterface as Response;

class GenerateQrisAction extends BaseAction
{
    private OrderRepository $orderRepo;
    private MidtransService $midtrans;

    public function __construct(OrderRepository $orderRepo, MidtransService $midtrans)
    {
        $this->orderRepo = $orderRepo;
        $this->midtrans = $midtrans;
    }

    protected function action(): Response
    {
        $body = $this->getBody();
        $orderId = $body['orderId'] ?? '';

        if (empty($orderId)) {
            return $this->errorResponse('Order ID wajib diisi.', 400);
        }

        $order = $this->resolveOrder($orderId);
        if (!$order) {
            return $this->errorResponse('Order tidak ditemukan.', 404);
        }

        if ($order['payment_method'] !== 'qris') {
            return $this->errorResponse('Order ini bukan pembayaran QRIS.', 400);
        }

        if ($order['payment_status'] === 'paid') {
            return $this->errorResponse('Order ini sudah dibayar.', 400);
        }

        // Reuse an existing, still-valid QR so a page refresh does not
        // re-charge Midtrans (it rejects a re-used order_id).
        if (!empty($order['qr_string']) && $this->isStillValid($order['payment_expires_at'] ?? null)) {
            return $this->successResponse(['payment' => $this->formatPayment($order)], 200);
        }

        if (!$this->midtrans->isConfigured()) {
            return $this->errorResponse('Pembayaran QRIS belum dikonfigurasi di server.', 503);
        }

        try {
            $charge = $this->midtrans->chargeQris(
                $order['order_number'],
                (int) $order['total_price'],
                [
                    'first_name' => $order['customer_name'],
                    'phone' => $order['customer_phone'],
                ]
            );
        } catch (\Throwable $e) {
            return $this->errorResponse('Gagal membuat pembayaran QRIS: ' . $e->getMessage(), 502);
        }

        // Stored as UTC so comparisons are unaffected by PHP/MySQL local tz.
        $expiresAt = MidtransService::parseExpiry($charge['expiry_time'] ?? null);

        $this->orderRepo->savePaymentCharge($order['id'], [
            'provider' => 'midtrans',
            'reference' => $charge['transaction_id'] ?? null,
            'qrString' => $charge['qr_string'] ?? null,
            'qrUrl' => MidtransService::extractQrUrl($charge),
            'expiresAt' => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        $fresh = $this->orderRepo->findById($order['id']);

        return $this->successResponse(['payment' => $this->formatPayment($fresh)], 201);
    }

    private function resolveOrder(string $orderId): ?array
    {
        $userId = $this->getUserId();

        if ($userId) {
            $order = $this->orderRepo->findById($orderId);
            if ($order && $order['user_id'] === $userId) {
                return $order;
            }
            return null;
        }

        $sessionToken = SessionService::getSessionToken($this->request);
        if ($sessionToken) {
            return $this->orderRepo->findBySession($sessionToken, $orderId);
        }

        return null;
    }

    private function isStillValid(?string $expiresAt): bool
    {
        if (empty($expiresAt)) {
            return false;
        }
        $utc = new \DateTimeZone('UTC');
        $exp = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $expiresAt, $utc);
        if ($exp === false) {
            return false;
        }
        return $exp > new \DateTimeImmutable('now', $utc);
    }

    private function formatPayment(array $order): array
    {
        return [
            'orderId' => $order['id'],
            'orderNumber' => $order['order_number'],
            'amount' => (int) $order['total_price'],
            'qrString' => $order['qr_string'] ?? null,
            'qrImageUrl' => $order['qr_url'] ?? null,
            'expiresAt' => $this->toIso($order['payment_expires_at'] ?? null),
            'status' => $order['payment_status'],
        ];
    }

    private function toIso(?string $dateTime): ?string
    {
        if (empty($dateTime)) {
            return null;
        }
        // Stored value is UTC; emit ISO-8601 with offset so any browser tz
        // computes the countdown correctly.
        $utc = new \DateTimeZone('UTC');
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime, $utc);
        if ($dt === false) {
            return null;
        }
        return $dt->format('c');
    }
}
