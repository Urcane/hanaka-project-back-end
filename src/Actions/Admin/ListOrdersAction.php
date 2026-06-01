<?php

declare(strict_types=1);

namespace App\Actions\Admin;

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
        $params = $this->request->getQueryParams();

        $status = $params['status'] ?? null;
        $paymentStatus = $params['paymentStatus'] ?? null;
        $limit = min((int) ($params['limit'] ?? 50), 100);
        $offset = max((int) ($params['offset'] ?? 0), 0);

        $orders = $this->orderRepo->findAll($status, $paymentStatus, $limit, $offset);
        $total = $this->orderRepo->countAll($status);

        $formatted = array_map([OrderRepository::class, 'formatOrder'], $orders);

        return $this->successResponse([
            'orders' => $formatted,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }
}
