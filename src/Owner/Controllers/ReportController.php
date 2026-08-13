<?php

declare(strict_types=1);

namespace App\Owner\Controllers;

use App\Admin\Support\View;
use App\Infrastructure\Repositories\OrderRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReportController extends Controller
{
    private OrderRepository $orderRepo;

    public function __construct(View $view, OrderRepository $orderRepo)
    {
        parent::__construct($view);
        $this->orderRepo = $orderRepo;
    }

    /**
     * GET /owner/reports?from=&to= — daily sales report with a date filter.
     */
    public function index(Request $request, Response $response): Response
    {
        $range = $this->resolveRange($request, 30);

        return $this->render($request, $response, 'reports/index', [
            'active' => 'reports',
            'title' => 'Laporan Penjualan',
            'range' => $range,
            'daily' => $this->orderRepo->getDailyReport($range['from'], $range['to']),
            'summary' => $this->orderRepo->getRangeSummary($range['from'], $range['to']),
            'topProducts' => $this->orderRepo->getTopProducts($range['from'], $range['to'], 10),
            'paymentBreakdown' => $this->orderRepo->getPaymentMethodBreakdown($range['from'], $range['to']),
            'statusBreakdown' => $this->orderRepo->getStatusBreakdown($range['from'], $range['to']),
        ]);
    }

    /**
     * GET /owner/reports/export?from=&to= — same rows as the table, as CSV.
     */
    public function export(Request $request, Response $response): Response
    {
        $range = $this->resolveRange($request, 30);
        $daily = $this->orderRepo->getDailyReport($range['from'], $range['to']);

        $handle = fopen('php://temp', 'r+');
        // BOM so Excel opens the file as UTF-8 rather than mangling it.
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Tanggal', 'Jumlah Order', 'Selesai', 'Dibatalkan', 'Pendapatan (Rp)']);

        foreach ($daily as $row) {
            fputcsv($handle, [
                $row['date'],
                $row['orderCount'],
                $row['completedCount'],
                $row['cancelledCount'],
                $row['revenue'],
            ]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        $filename = "laporan-hanaka-{$range['from']}-sd-{$range['to']}.csv";
        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
