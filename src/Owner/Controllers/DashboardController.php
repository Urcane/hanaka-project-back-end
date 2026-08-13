<?php

declare(strict_types=1);

namespace App\Owner\Controllers;

use App\Admin\Support\View;
use App\Infrastructure\Repositories\AnnouncementRepository;
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
    private AnnouncementRepository $announcementRepo;

    public function __construct(
        View $view,
        OrderRepository $orderRepo,
        ProductRepository $productRepo,
        UserRepository $userRepo,
        AnnouncementRepository $announcementRepo
    ) {
        parent::__construct($view);
        $this->orderRepo = $orderRepo;
        $this->productRepo = $productRepo;
        $this->userRepo = $userRepo;
        $this->announcementRepo = $announcementRepo;
    }

    /**
     * GET /owner/dashboard?from=&to=
     */
    public function index(Request $request, Response $response): Response
    {
        // The dashboard defaults to a 14-day window; the reports page is where
        // longer ranges get analysed.
        $range = $this->resolveRange($request, 14);
        $daily = $this->orderRepo->getDailyReport($range['from'], $range['to']);
        $summary = $this->orderRepo->getRangeSummary($range['from'], $range['to']);

        $dailyTarget = (int) ($_ENV['SALES_TARGET_DAILY'] ?? 1500000);
        $todayRevenue = $this->orderRepo->getTodayRevenue();

        return $this->render($request, $response, 'dashboard', [
            'active' => 'dashboard',
            'title' => 'Dashboard Owner',
            'range' => $range,
            'daily' => $daily,
            'summary' => $summary,
            'statusBreakdown' => $this->orderRepo->getStatusBreakdown($range['from'], $range['to']),
            'paymentBreakdown' => $this->orderRepo->getPaymentMethodBreakdown($range['from'], $range['to']),
            'topProducts' => $this->orderRepo->getTopProducts($range['from'], $range['to']),
            'deliveryPoints' => $this->orderRepo->getDeliveryPoints($range['from'], $range['to']),
            'lowStock' => $this->productRepo->findLowStock(),
            'storePoint' => $this->storePoint(),
            'target' => [
                'daily' => $dailyTarget,
                'todayRevenue' => $todayRevenue,
                'percent' => $dailyTarget > 0
                    ? min(100, (int) round($todayRevenue / $dailyTarget * 100))
                    : 0,
            ],
            'totals' => [
                'customers' => $this->userRepo->countByRole('customer'),
                'products' => $this->productRepo->countAll(),
                'allTimeRevenue' => $this->orderRepo->getTotalRevenue(),
                'announcements' => $this->announcementRepo->countAll(),
            ],
        ]);
    }

    /**
     * Shop coordinates (map origin), overridable per environment.
     *
     * @return array{lat: float, lng: float}
     */
    private function storePoint(): array
    {
        return [
            'lat' => (float) ($_ENV['STORE_LAT'] ?? -1.2654),
            'lng' => (float) ($_ENV['STORE_LNG'] ?? 116.8312),
        ];
    }
}
