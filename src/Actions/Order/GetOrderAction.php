<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use App\Infrastructure\Services\SessionService;
use Psr\Http\Message\ResponseInterface as Response;

class GetOrderAction extends BaseAction
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
            // Authenticated user — must own the order
            $order = $this->orderRepo->findById($orderId);
            if ($order && $order['user_id'] !== $userId) {
                $order = null;
            }
        } else {
            // Guest — verify via session token
            $sessionToken = SessionService::getSessionToken($this->request);
            if ($sessionToken) {
                $order = $this->orderRepo->findBySession($sessionToken, $orderId);
            }
        }

        if (!$order) {
            return $this->errorResponse('Order tidak ditemukan.', 404);
        }

        return $this->successResponse([
            'order' => OrderRepository::formatOrder($order),
        ]);
    }
}
