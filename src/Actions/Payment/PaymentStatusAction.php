<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use App\Infrastructure\Services\MidtransService;
use App\Infrastructure\Services\SessionService;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Live QRIS status check. The frontend polls this so payment is confirmed
 * even when the Midtrans webhook cannot reach a local dev server. Payment
 * truth always comes from Midtrans here — never from the client.
 */
class PaymentStatusAction extends BaseAction
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
        $params = $this->request->getQueryParams();
        $orderId = $params['orderId'] ?? '';

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

        // Already settled — no need to ask Midtrans again.
        if (in_array($order['payment_status'], ['paid', 'expired', 'failed'], true)) {
            return $this->respond($order, $order['payment_status']);
        }

        if (!$this->midtrans->isConfigured()) {
            return $this->respond($order, $order['payment_status']);
        }

        try {
            $status = $this->midtrans->getStatus($order['order_number']);
        } catch (\Throwable $e) {
            // Don't fail the poll — just report the last known status.
            return $this->respond($order, $order['payment_status']);
        }

        $mapped = MidtransService::mapPaymentStatus(
            (string) ($status['transaction_status'] ?? 'pending'),
            $status['fraud_status'] ?? null
        );

        $fresh = $this->applyStatus($order, $mapped);

        return $this->respond($fresh, $fresh['payment_status']);
    }

    private function applyStatus(array $order, string $mapped): array
    {
        if ($mapped === $order['payment_status']) {
            return $order;
        }

        if ($mapped === 'paid') {
            return $this->orderRepo->markAsPaid($order['id']) ?? $order;
        }

        if (in_array($mapped, ['expired', 'failed'], true)) {
            return $this->orderRepo->updatePaymentStatus($order['id'], $mapped) ?? $order;
        }

        return $order;
    }

    private function respond(array $order, string $status): Response
    {
        return $this->successResponse([
            'status' => $status,
            'order' => OrderRepository::formatOrder($order),
        ]);
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
}
