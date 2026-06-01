<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database;
use PDO;

class OrderRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(array $orderData, array $cartItems): array
    {
        $this->db->beginTransaction();

        try {
            $orderId = Database::generateId('ord');
            $orderNumber = $this->generateOrderNumber();

            $paymentStatus = $orderData['paymentMethod'] === 'cash' ? 'cod' : 'pending';

            $stmt = $this->db->prepare(
                'INSERT INTO orders (id, order_number, user_id, session_token, customer_name, customer_phone,
                 fulfillment_method, pickup_date, pickup_time, delivery_address, address_note,
                 payment_method, payment_status, status, total_price)
                 VALUES (:id, :order_number, :user_id, :session_token, :customer_name, :customer_phone,
                 :fulfillment_method, :pickup_date, :pickup_time, :delivery_address, :address_note,
                 :payment_method, :payment_status, :status, :total_price)'
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

        $stmt = $this->db->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['id' => $orderId, 'status' => $status]);

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
            'customerName' => $row['customer_name'],
            'customerPhone' => $row['customer_phone'],
            'fulfillmentMethod' => $row['fulfillment_method'],
            'pickupDate' => $row['pickup_date'],
            'pickupTime' => $row['pickup_time'],
            'deliveryAddress' => $row['delivery_address'],
            'addressNote' => $row['address_note'],
            'paymentMethod' => $row['payment_method'],
            'paymentStatus' => $row['payment_status'],
            'status' => $row['status'],
            'items' => $items,
            'totalPrice' => (int) $row['total_price'],
            'createdAt' => $row['created_at'],
        ];
    }
}
