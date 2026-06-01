<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Actions\BaseAction;
use App\Infrastructure\Repositories\OrderRepository;
use App\Infrastructure\Repositories\ProductRepository;
use App\Infrastructure\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;

class DashboardAction extends BaseAction
{
    private OrderRepository $orderRepo;
    private ProductRepository $productRepo;
    private UserRepository $userRepo;

    public function __construct(
        OrderRepository $orderRepo,
        ProductRepository $productRepo,
        UserRepository $userRepo
    ) {
        $this->orderRepo = $orderRepo;
        $this->productRepo = $productRepo;
        $this->userRepo = $userRepo;
    }

    protected function action(): Response
    {
        return $this->successResponse([
            'dashboard' => [
                'totalOrders' => $this->orderRepo->countAll(),
                'pendingOrders' => $this->orderRepo->countAll('menunggu konfirmasi'),
                'processingOrders' => $this->orderRepo->countAll('diproses'),
                'completedOrders' => $this->orderRepo->countAll('selesai'),
                'cancelledOrders' => $this->orderRepo->countAll('dibatalkan'),
                'totalCustomers' => $this->userRepo->countByRole('customer'),
                'totalProducts' => $this->productRepo->countAll(),
                'todayRevenue' => $this->orderRepo->getTodayRevenue(),
                'totalRevenue' => $this->orderRepo->getTotalRevenue(),
            ],
        ]);
    }
}
