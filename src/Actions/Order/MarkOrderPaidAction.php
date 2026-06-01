<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use App\Infrastructure\Services\SessionService;
use Psr\Http\Message\ResponseInterface as Response;

class MarkOrderPaidAction extends BaseAction
{
    private OrderRepository $orderRepo;

    public function __construct(OrderRepository $orderRepo)
    {
        $this->orderRepo = $orderRepo;
    }

    protected function action(): Response
    {
        $orderId = $this->args['orderId'] ?? '';
        $userId = $this->getUserId();

        $order = null;

        if ($userId) {
            $order = $this->orderRepo->findById($orderId);
            if ($order && $order['user_id'] !== $userId) {
                $order = null;
            }
        } else {
            $sessionToken = SessionService::getSessionToken($this->request);
            if ($sessionToken) {
                $order = $this->orderRepo->findBySession($sessionToken, $orderId);
            }
        }

        if (!$order) {
            return $this->errorResponse('Order tidak ditemukan.', 404);
        }

        if ($order['payment_method'] !== 'qris') {
            return $this->errorResponse('Order ini bukan pembayaran QRIS.', 400);
        }

        if ($order['payment_status'] !== 'pending') {
            return $this->errorResponse('Order ini sudah dibayar.', 400);
        }

        $updated = $this->orderRepo->markAsPaid($orderId);

        return $this->successResponse([
            'order' => OrderRepository::formatOrder($updated),
        ]);
    }
}
