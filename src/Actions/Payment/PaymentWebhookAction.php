<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use App\Infrastructure\Services\MidtransService;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Midtrans HTTP notification (webhook) handler.
 *
 * Midtrans requires HTTP 200 for every notification — even if we reject it
 * for security or the order is not found. Any non-2xx causes Midtrans to
 * retry indefinitely and flag the endpoint as unhealthy.
 *
 * Set the Notification URL in the Midtrans dashboard to:
 * https://<your-domain>/api/payments/webhook
 */
class PaymentWebhookAction extends BaseAction
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
        $notif = $this->getBody();

        // Missing required fields — acknowledge and ignore.
        if (empty($notif['order_id']) || empty($notif['signature_key'])) {
            return $this->ok('ignored: missing fields');
        }

        // Invalid signature — reject silently (do NOT update anything).
        if (!$this->midtrans->verifySignature($notif)) {
            return $this->ok('ignored: invalid signature');
        }

        $order = $this->orderRepo->findByOrderNumber((string) $notif['order_id']);

        // Order not in our DB (e.g. Midtrans dashboard "Test" button uses a
        // fake order_id) — acknowledge so Midtrans doesn't retry.
        if (!$order) {
            return $this->ok('ignored: order not found');
        }

        $mapped = MidtransService::mapPaymentStatus(
            (string) ($notif['transaction_status'] ?? 'pending'),
            $notif['fraud_status'] ?? null
        );

        if ($mapped !== $order['payment_status']) {
            if ($mapped === 'paid') {
                $this->orderRepo->markAsPaid($order['id']);
            } elseif (in_array($mapped, ['expired', 'failed'], true)) {
                $this->orderRepo->updatePaymentStatus($order['id'], $mapped);
            }
        }

        return $this->ok('processed');
    }

    private function ok(string $message): Response
    {
        return $this->successResponse(['received' => true, 'message' => $message]);
    }
}
