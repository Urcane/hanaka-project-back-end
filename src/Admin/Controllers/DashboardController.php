<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Support\View;
use App\Infrastructure\Repositories\OrderRepository;
use App\Infrastructure\Repositories\ProductRepository;
use App\Infrastructure\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends Controller
{
    private OrderRepository $orderRepo;
    private ProductRepository $productRepo;
    private UserRepository $userRepo;

    public function __construct(
        View $view,
        OrderRepository $orderRepo,
        ProductRepository $productRepo,
        UserRepository $userRepo
    ) {
        parent::__construct($view);
        $this->orderRepo = $orderRepo;
        $this->productRepo = $productRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * GET /admin/dashboard
     */
    public function index(Request $request, Response $response): Response
    {
        $stats = [
            'totalOrders' => $this->orderRepo->countAll(),
            'pendingOrders' => $this->orderRepo->countAll('menunggu konfirmasi'),
            'processingOrders' => $this->orderRepo->countAll('diproses'),
            'completedOrders' => $this->orderRepo->countAll('selesai'),
            'cancelledOrders' => $this->orderRepo->countAll('dibatalkan'),
            'totalCustomers' => $this->userRepo->countByRole('customer'),
            'totalProducts' => $this->productRepo->countAll(),
            'todayRevenue' => $this->orderRepo->getTodayRevenue(),
            'totalRevenue' => $this->orderRepo->getTotalRevenue(),
        ];

        return $this->render($request, $response, 'dashboard', [
            'active' => 'dashboard',
            'title' => 'Dashboard',
            'stats' => $stats,
        ]);
    }
}
