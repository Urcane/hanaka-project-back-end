<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use Psr\Http\Message\ResponseInterface as Response;

class TrackOrderAction extends BaseAction
{
    private OrderRepository $orderRepo;

    public function __construct(OrderRepository $orderRepo)
    {
        $this->orderRepo = $orderRepo;
    }

    protected function action(): Response
    {
        $params = $this->request->getQueryParams();
        $number = strtoupper(trim((string) ($params['number'] ?? '')));

        if ($number === '') {
            return $this->errorResponse('Pesanan tidak ditemukan.', 404);
        }

        $order = $this->orderRepo->findByOrderNumber($number);
        if (!$order) {
            return $this->errorResponse('Pesanan tidak ditemukan.', 404);
        }

        // Public projection only — never expose customer PII here.
        return $this->successResponse([
            'order' => OrderRepository::formatTracking($order),
        ]);
    }
}
