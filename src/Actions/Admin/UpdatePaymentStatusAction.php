<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use Psr\Http\Message\ResponseInterface as Response;

class UpdatePaymentStatusAction extends BaseAction
{
    private OrderRepository $orderRepo;

    public function __construct(OrderRepository $orderRepo)
    {
        $this->orderRepo = $orderRepo;
    }

    protected function action(): Response
    {
        $orderId = $this->args['orderId'] ?? '';
        $body = $this->getBody();
        $paymentStatus = $body['paymentStatus'] ?? '';

        if (empty($paymentStatus)) {
            return $this->errorResponse('Status pembayaran wajib diisi.', 400);
        }

        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            return $this->errorResponse('Order tidak ditemukan.', 404);
        }

        $updated = $this->orderRepo->updatePaymentStatus($orderId, $paymentStatus);
        if (!$updated) {
            return $this->errorResponse('Status pembayaran tidak valid. Gunakan: pending, paid, cod.', 400);
        }

        return $this->successResponse([
            'order' => OrderRepository::formatOrder($updated),
        ]);
    }
}
