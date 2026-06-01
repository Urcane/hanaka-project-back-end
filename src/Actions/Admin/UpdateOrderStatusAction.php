<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use Psr\Http\Message\ResponseInterface as Response;

class UpdateOrderStatusAction extends BaseAction
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
        $status = $body['status'] ?? '';

        if (empty($status)) {
            return $this->errorResponse('Status wajib diisi.', 400);
        }

        $order = $this->orderRepo->findById($orderId);
        if (!$order) {
            return $this->errorResponse('Order tidak ditemukan.', 404);
        }

        $updated = $this->orderRepo->updateStatus($orderId, $status);
        if (!$updated) {
            return $this->errorResponse('Status tidak valid. Gunakan: menunggu konfirmasi, diproses, siap diambil, diantar, selesai, dibatalkan.', 400);
        }

        return $this->successResponse([
            'order' => OrderRepository::formatOrder($updated),
        ]);
    }
}
