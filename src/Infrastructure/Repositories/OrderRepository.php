<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Order\InsufficientStockException;
use App\Infrastructure\Database;
use PDO;

class OrderRepository
{
    private PDO $db;
    private ProductRepository $productRepo;

    public function __construct(ProductRepository $productRepo)
    {
        $this->db = Database::getConnection();
        $this->productRepo = $productRepo;
    }

    public function create(array $orderData, array $cartItems): array
    {
        $this->db->beginTransaction();

        try {
            $orderId = Database::generateId('ord');
            $orderNumber = $this->generateOrderNumber();

            $paymentStatus = $orderData['paymentMethod'] === 'cash' ? 'cod' : 'pending';
            $isDelivery = ($orderData['pickupMethod'] ?? '') === 'delivery';

            $stmt = $this->db->prepare(
                'INSERT INTO orders (id, order_number, user_id, session_token, customer_name, customer_phone,
                 fulfillment_method, pickup_date, pickup_time, delivery_address, delivery_lat, delivery_lng,
                 address_note, payment_method, payment_status, status, total_price)
                 VALUES (:id, :order_number, :user_id, :session_token, :customer_name, :customer_phone,
                 :fulfillment_method, :pickup_date, :pickup_time, :delivery_address, :delivery_lat, :delivery_lng,
                 :address_note, :payment_method, :payment_status, :status, :total_price)'
            );

            $totalPrice = 0;
            foreach ($cartItems as $item) {
                $totalPrice += (int) $item['total_price'];
            }

            $stmt->execute([
                'id' => $orderId,
                'order_number' => $orderNumber,
                'user_id' => $orderData['userId'] ?? null,
                'session_token' => $orderData['sessionToken'] ?? null,
                'customer_name' => $orderData['customerName'],
                'customer_phone' => $orderData['phone'],
                'fulfillment_method' => $orderData['pickupMethod'],
                'pickup_date' => $orderData['pickupDate'] ?? null,
                'pickup_time' => $orderData['pickupTime'] ?? null,
                'delivery_address' => $orderData['address'] ?? null,
                // Coordinates only make sense for delivery orders.
                'delivery_lat' => $isDelivery ? ($orderData['deliveryLat'] ?? null) : null,
                'delivery_lng' => $isDelivery ? ($orderData['deliveryLng'] ?? null) : null,
                'address_note' => $orderData['addressNote'] ?? '',
                'payment_method' => $orderData['paymentMethod'],
                'payment_status' => $paymentStatus,
                'status' => 'menunggu konfirmasi',
                'total_price' => $totalPrice,
            ]);

            // Insert order items (snapshot)
            $itemStmt = $this->db->prepare(
                'INSERT INTO order_items (id, order_id, product_id, product_name, size_id, size_label,
                 color_text, theme, message, quantity, unit_price, total_price)
                 VALUES (:id, :order_id, :product_id, :product_name, :size_id, :size_label,
                 :color_text, :theme, :message, :quantity, :unit_price, :total_price)'
            );

            foreach ($cartItems as $item) {
                $itemStmt->execute([
                    'id' => Database::generateId('oi'),
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'size_id' => $item['size_id'],
                    'size_label' => $item['size_label'],
                    'color_text' => $item['color_text'],
                    'theme' => $item['theme'],
                    'message' => $item['message'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['unit_price'],
                    'total_price' => (int) $item['total_price'],
                ]);

                // Reserve the stock in the same transaction as the order itself.
                // The cart was validated when the item was added, but stock can
                // change in between — this is the authoritative check.
                $taken = $this->productRepo->decrementStock(
                    (string) $item['size_id'],
                    (int) $item['quantity']
                );

                if (!$taken) {
                    $size = $this->productRepo->findSizeById((string) $item['size_id']);
                    throw InsufficientStockException::forItem(
                        (string) $item['product_name'],
                        (string) $item['size_label'],
                        (int) ($size['stock'] ?? 0)
                    );
                }
            }

            $this->db->commit();

            return $this->findById($orderId);
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();

        if (!$order) {
            return null;
        }

        $order['items'] = $this->getOrderItems($id);
        return $order;
    }

    public function findByUser(string $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }

        return $orders;
    }

    public function findBySession(string $sessionToken, string $orderId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM orders WHERE id = :id AND session_token = :session_token LIMIT 1'
        );
        $stmt->execute(['id' => $orderId, 'session_token' => $sessionToken]);
        $order = $stmt->fetch();

        if (!$order) {
            return null;
        }

        $order['items'] = $this->getOrderItems($order['id']);
        return $order;
    }

    public function markAsPaid(string $orderId): ?array
    {
        $stmt = $this->db->prepare(
            "UPDATE orders SET payment_status = 'paid', status = 'diproses' WHERE id = :id"
        );
        $stmt->execute(['id' => $orderId]);

        return $this->findById($orderId);
    }

    public function findByOrderNumber(string $orderNumber): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE order_number = :order_number LIMIT 1');
        $stmt->execute(['order_number' => $orderNumber]);
        $order = $stmt->fetch();

        if (!$order) {
            return null;
        }

        $order['items'] = $this->getOrderItems($order['id']);
        return $order;
    }

    public function savePaymentCharge(string $orderId, array $payment): void
    {
        $stmt = $this->db->prepare(
            'UPDATE orders SET payment_provider = :provider, payment_reference = :reference,
             qr_string = :qr_string, qr_url = :qr_url, payment_expires_at = :expires_at
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $orderId,
            'provider' => $payment['provider'] ?? 'midtrans',
            'reference' => $payment['reference'] ?? null,
            'qr_string' => $payment['qrString'] ?? null,
            'qr_url' => $payment['qrUrl'] ?? null,
            'expires_at' => $payment['expiresAt'] ?? null,
        ]);
    }

    // ── Admin Methods ──

    public function findAll(?string $status = null, ?string $paymentStatus = null, int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT * FROM orders WHERE 1=1';
        $params = [];

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        if ($paymentStatus !== null) {
            $sql .= ' AND payment_status = :payment_status';
            $params['payment_status'] = $paymentStatus;
        }

        $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }

        return $orders;
    }

    public function countAll(?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) as total FROM orders';
        $params = [];

        if ($status !== null) {
            $sql .= ' WHERE status = :status';
            $params['status'] = $status;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'];
    }

    public function updateStatus(string $orderId, string $status): ?array
    {
        $allowedStatuses = ['menunggu konfirmasi', 'diproses', 'siap diambil', 'diantar', 'selesai', 'dibatalkan'];
        if (!in_array($status, $allowedStatuses, true)) {
            return null;
        }

        $current = $this->findById($orderId);
        if ($current === null) {
            return null;
        }

        $stmt = $this->db->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['id' => $orderId, 'status' => $status]);

        // Cancelling frees the reserved stock. Guarded on the previous status so
        // re-saving an already-cancelled order does not hand back stock twice.
        if ($status === 'dibatalkan' && $current['status'] !== 'dibatalkan') {
            foreach ($current['items'] as $item) {
                $this->productRepo->restoreStock((string) $item['size_id'], (int) $item['quantity']);
            }
        }

        return $this->findById($orderId);
    }

    public function updatePaymentStatus(string $orderId, string $paymentStatus): ?array
    {
        $allowed = ['pending', 'paid', 'cod', 'expired', 'failed'];
        if (!in_array($paymentStatus, $allowed, true)) {
            return null;
        }

        $stmt = $this->db->prepare('UPDATE orders SET payment_status = :payment_status WHERE id = :id');
        $stmt->execute(['id' => $orderId, 'payment_status' => $paymentStatus]);

        return $this->findById($orderId);
    }

    public function getTodayRevenue(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total_price), 0) as revenue FROM orders
             WHERE DATE(created_at) = CURDATE() AND payment_status IN ('paid', 'cod')"
        );
        $stmt->execute();
        return (int) $stmt->fetch()['revenue'];
    }

    public function getTotalRevenue(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total_price), 0) as revenue FROM orders WHERE payment_status IN ('paid', 'cod')"
        );
        $stmt->execute();
        return (int) $stmt->fetch()['revenue'];
    }

    // ── Owner Reporting ──
    //
    // Every method below takes an inclusive date range (Y-m-d). Revenue figures
    // only count orders that were actually paid or are cash-on-delivery, the
    // same rule getTodayRevenue() uses.

    /**
     * One row per calendar day in the range — days without orders are filled in
     * with zeroes so charts keep a continuous x-axis.
     */
    public function getDailyReport(string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE(created_at) AS day,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(CASE WHEN payment_status IN ('paid', 'cod') THEN total_price ELSE 0 END), 0) AS revenue,
                    COALESCE(SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END), 0) AS completed_count,
                    COALESCE(SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END), 0) AS cancelled_count
             FROM orders
             WHERE DATE(created_at) BETWEEN :from AND :to
             GROUP BY DATE(created_at)"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        $byDay = [];
        foreach ($stmt->fetchAll() as $row) {
            $byDay[$row['day']] = [
                'date' => $row['day'],
                'orderCount' => (int) $row['order_count'],
                'revenue' => (int) $row['revenue'],
                'completedCount' => (int) $row['completed_count'],
                'cancelledCount' => (int) $row['cancelled_count'],
            ];
        }

        $days = [];
        $cursor = new \DateTimeImmutable($from);
        $end = new \DateTimeImmutable($to);
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d');
            $days[] = $byDay[$key] ?? [
                'date' => $key,
                'orderCount' => 0,
                'revenue' => 0,
                'completedCount' => 0,
                'cancelledCount' => 0,
            ];
            $cursor = $cursor->modify('+1 day');
        }

        return $days;
    }

    /**
     * Headline totals for the range.
     */
    public function getRangeSummary(string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS order_count,
                    COALESCE(SUM(CASE WHEN payment_status IN ('paid', 'cod') THEN total_price ELSE 0 END), 0) AS revenue,
                    COALESCE(SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END), 0) AS completed_count,
                    COALESCE(SUM(CASE WHEN status = 'dibatalkan' THEN 1 ELSE 0 END), 0) AS cancelled_count
             FROM orders
             WHERE DATE(created_at) BETWEEN :from AND :to"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);
        $row = $stmt->fetch() ?: [];

        $orderCount = (int) ($row['order_count'] ?? 0);
        $revenue = (int) ($row['revenue'] ?? 0);

        return [
            'orderCount' => $orderCount,
            'revenue' => $revenue,
            'completedCount' => (int) ($row['completed_count'] ?? 0),
            'cancelledCount' => (int) ($row['cancelled_count'] ?? 0),
            'averageOrderValue' => $orderCount > 0 ? (int) round($revenue / $orderCount) : 0,
        ];
    }

    /**
     * @return array<string, int> status => count
     */
    public function getStatusBreakdown(string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            'SELECT status, COUNT(*) AS total FROM orders
             WHERE DATE(created_at) BETWEEN :from AND :to
             GROUP BY status'
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(string) $row['status']] = (int) $row['total'];
        }

        return $result;
    }

    /**
     * @return array<string, array{count:int, revenue:int}> payment method => totals
     */
    public function getPaymentMethodBreakdown(string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT payment_method, COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN payment_status IN ('paid', 'cod') THEN total_price ELSE 0 END), 0) AS revenue
             FROM orders
             WHERE DATE(created_at) BETWEEN :from AND :to
             GROUP BY payment_method"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(string) $row['payment_method']] = [
                'count' => (int) $row['total'],
                'revenue' => (int) $row['revenue'],
            ];
        }

        return $result;
    }

    /**
     * Best-selling products in the range, by units sold.
     */
    public function getTopProducts(string $from, string $to, int $limit = 5): array
    {
        $sql = "SELECT oi.product_name, SUM(oi.quantity) AS units, SUM(oi.total_price) AS revenue
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                WHERE DATE(o.created_at) BETWEEN :from AND :to
                  AND o.status <> 'dibatalkan'
                GROUP BY oi.product_name
                ORDER BY units DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('from', $from);
        $stmt->bindValue('to', $to);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'productName' => (string) $row['product_name'],
                'units' => (int) $row['units'],
                'revenue' => (int) $row['revenue'],
            ];
        }

        return $rows;
    }

    /**
     * Delivery orders in the range that carry a drop-off coordinate — the dots
     * on the owner map. Orders without a point (pickup, or placed before the
     * map picker existed) are simply left out.
     */
    public function getDeliveryPoints(string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, order_number, status, total_price, delivery_lat, delivery_lng, created_at
             FROM orders
             WHERE fulfillment_method = 'delivery'
               AND delivery_lat IS NOT NULL AND delivery_lng IS NOT NULL
               AND DATE(created_at) BETWEEN :from AND :to
             ORDER BY created_at DESC"
        );
        $stmt->execute(['from' => $from, 'to' => $to]);

        $points = [];
        foreach ($stmt->fetchAll() as $row) {
            $points[] = [
                'id' => $row['id'],
                'orderNumber' => $row['order_number'],
                'status' => $row['status'],
                'totalPrice' => (int) $row['total_price'],
                'lat' => (float) $row['delivery_lat'],
                'lng' => (float) $row['delivery_lng'],
                'createdAt' => $row['created_at'],
            ];
        }

        return $points;
    }

    private function getOrderItems(string $orderId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM order_items WHERE order_id = :order_id');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    private function generateOrderNumber(): string
    {
        $now = new \DateTime();
        $datePart = $now->format('Ymd');
        $timePart = $now->format('His');
        $randomPart = str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
        return "HNK-{$datePart}-{$timePart}-{$randomPart}";
    }

    public static function formatOrder(array $row): array
    {
        $items = [];
        foreach ($row['items'] ?? [] as $item) {
            $items[] = [
                'id' => $item['id'],
                'productId' => $item['product_id'],
                'productName' => $item['product_name'],
                'sizeId' => $item['size_id'],
                'sizeLabel' => $item['size_label'],
                'colorText' => $item['color_text'],
                'theme' => $item['theme'],
                'message' => $item['message'],
                'quantity' => (int) $item['quantity'],
                'unitPrice' => (int) $item['unit_price'],
                'totalPrice' => (int) $item['total_price'],
            ];
        }

        return [
            'id' => $row['id'],
            'orderNumber' => $row['order_number'],
            'userId' => $row['user_id'],
            // NOTE: includes PII (customerName, customerPhone, deliveryAddress).
            // Do NOT use this for public/unauthenticated endpoints — use
            // formatTracking() instead.
            'customerName' => $row['customer_name'],
            'customerPhone' => $row['customer_phone'],
            'fulfillmentMethod' => $row['fulfillment_method'],
            'pickupDate' => $row['pickup_date'],
            'pickupTime' => $row['pickup_time'],
            'deliveryAddress' => $row['delivery_address'],
            'deliveryLat' => isset($row['delivery_lat']) ? (float) $row['delivery_lat'] : null,
            'deliveryLng' => isset($row['delivery_lng']) ? (float) $row['delivery_lng'] : null,
            'addressNote' => $row['address_note'],
            'paymentMethod' => $row['payment_method'],
            'paymentStatus' => $row['payment_status'],
            'status' => $row['status'],
            'items' => $items,
            'totalPrice' => (int) $row['total_price'],
            'createdAt' => $row['created_at'],
        ];
    }

    /**
     * Public projection for the order-tracking endpoint. Deliberately omits all
     * PII (customer name, phone, delivery address, notes) so the order number
     * alone cannot expose personal data of whoever placed the order.
     */
    public static function formatTracking(array $row): array
    {
        $items = [];
        foreach ($row['items'] ?? [] as $item) {
            $items[] = [
                'id' => $item['id'],
                'productName' => $item['product_name'],
                'sizeLabel' => $item['size_label'],
                'quantity' => (int) $item['quantity'],
                'totalPrice' => (int) $item['total_price'],
            ];
        }

        return [
            'orderNumber' => $row['order_number'],
            'status' => $row['status'],
            'paymentStatus' => $row['payment_status'],
            'fulfillmentMethod' => $row['fulfillment_method'],
            'totalPrice' => (int) $row['total_price'],
            'createdAt' => $row['created_at'],
            // Drop-off coordinate only — the written address stays private. The
            // point is what the tracking map needs, and the customer tracking a
            // delivery already knows where it is going.
            'deliveryLat' => isset($row['delivery_lat']) ? (float) $row['delivery_lat'] : null,
            'deliveryLng' => isset($row['delivery_lng']) ? (float) $row['delivery_lng'] : null,
            'items' => $items,
        ];
    }
}
