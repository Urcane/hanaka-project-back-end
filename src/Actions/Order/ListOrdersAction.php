<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ListOrdersAction extends BaseAction
{
    private OrderRepository $orderRepo;

    public function __construct(OrderRepository $orderRepo)
    {
        $this->orderRepo = $orderRepo;
    }

    protected function action(): Response
    {
        $userId = $this->getUserId();

        if (!$userId) {
            return $this->errorResponse('Token tidak valid atau sudah expired.', 401);
        }

        $orders = $this->orderRepo->findByUser($userId);

        $formatted = array_map([OrderRepository::class, 'formatOrder'], $orders);

        return $this->successResponse([
            'orders' => $formatted,
        ]);
    }
}
