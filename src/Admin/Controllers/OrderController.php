<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Support\View;
use App\Infrastructure\Repositories\OrderRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OrderController extends Controller
{
    private const LIMIT = 20;

    private OrderRepository $orderRepo;

    public function __construct(View $view, OrderRepository $orderRepo)
    {
        parent::__construct($view);
        $this->orderRepo = $orderRepo;
    }

    /**
     * GET /admin/orders
     */
    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $status = isset($params['status']) && $params['status'] !== '' ? (string) $params['status'] : null;
        $offset = max((int) ($params['offset'] ?? 0), 0);

        $orders = array_map(
            [OrderRepository::class, 'formatOrder'],
            $this->orderRepo->findAll($status, null, self::LIMIT, $offset)
        );
        $total = $this->orderRepo->countAll($status);

        return $this->render($request, $response, 'orders/index', [
            'active' => 'orders',
            'title' => 'Order Management',
            'orders' => $orders,
            'total' => $total,
            'status' => $status ?? '',
            'offset' => $offset,
            'limit' => self::LIMIT,
        ]);
    }

    /**
     * GET /admin/orders/{orderId}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $order = $this->orderRepo->findById((string) $args['orderId']);
        if ($order === null) {
            return $this->render($request, $response, 'orders/not-found', [
                'active' => 'orders',
                'title' => 'Order tidak ditemukan',
            ])->withStatus(404);
        }

        return $this->render($request, $response, 'orders/show', [
            'active' => 'orders',
            'title' => 'Order ' . $order['order_number'],
            'order' => OrderRepository::formatOrder($order),
        ]);
    }

    /**
     * POST /admin/orders/{orderId}/status
     */
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $orderId = (string) $args['orderId'];
        $body = (array) $request->getParsedBody();
        $status = trim((string) ($body['status'] ?? ''));
        $back = $this->safeBack($body['back'] ?? null, '/admin/orders');

        if ($status === '') {
            return $this->redirectWithFlash($request, $response, $back, 'error', 'Status wajib diisi.');
        }

        $updated = $this->orderRepo->updateStatus($orderId, $status);
        if ($updated === null) {
            return $this->redirectWithFlash($request, $response, $back, 'error', 'Status tidak valid.');
        }

        return $this->redirectWithFlash($request, $response, $back, 'success', 'Status order diperbarui.');
    }

    /**
     * POST /admin/orders/{orderId}/payment-status
     */
    public function updatePaymentStatus(Request $request, Response $response, array $args): Response
    {
        $orderId = (string) $args['orderId'];
        $body = (array) $request->getParsedBody();
        $paymentStatus = trim((string) ($body['paymentStatus'] ?? ''));
        $back = $this->safeBack($body['back'] ?? null, '/admin/orders/' . $orderId);

        if ($paymentStatus === '') {
            return $this->redirectWithFlash($request, $response, $back, 'error', 'Status pembayaran wajib diisi.');
        }

        $updated = $this->orderRepo->updatePaymentStatus($orderId, $paymentStatus);
        if ($updated === null) {
            return $this->redirectWithFlash($request, $response, $back, 'error', 'Status pembayaran tidak valid.');
        }

        return $this->redirectWithFlash($request, $response, $back, 'success', 'Status pembayaran diperbarui.');
    }

    /**
     * Only allow redirecting back to local /admin paths.
     */
    private function safeBack($value, string $fallback): string
    {
        $value = (string) ($value ?? '');
        if ($value !== '' && str_starts_with($value, '/admin')) {
            return $value;
        }
        return $fallback;
    }
}
