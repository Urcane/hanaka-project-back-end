<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
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

        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            return $this->errorResponse('Order tidak ditemukan.', 404);
        }

        return $this->successResponse([
            'order' => OrderRepository::formatOrder($order),
        ]);
    }
}
