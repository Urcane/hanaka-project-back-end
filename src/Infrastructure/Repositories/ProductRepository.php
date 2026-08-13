<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database;
use PDO;

class ProductRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(?bool $featured = null): array
    {
        $sql = 'SELECT p.*, ps.id AS size_id, ps.label AS size_label, ps.full_label AS size_full_label, ps.price AS size_price,
                       ps.stock AS size_stock, ps.low_stock_threshold AS size_low_stock
                FROM products p
                LEFT JOIN product_sizes ps ON ps.product_id = p.id';

        $params = [];
        if ($featured !== null) {
            $sql .= ' WHERE p.featured = :featured';
            $params['featured'] = $featured ? 1 : 0;
        }

        $sql .= ' ORDER BY p.name ASC, ps.price ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return $this->groupProducts($rows);
    }

    public function findById(string $id): ?array
    {
        $sql = 'SELECT p.*, ps.id AS size_id, ps.label AS size_label, ps.full_label AS size_full_label, ps.price AS size_price,
                       ps.stock AS size_stock, ps.low_stock_threshold AS size_low_stock
                FROM products p
                LEFT JOIN product_sizes ps ON ps.product_id = p.id
                WHERE p.id = :id
                ORDER BY ps.price ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return null;
        }

        $products = $this->groupProducts($rows);
        return $products[0] ?? null;
    }

    public function findSizeById(string $sizeId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_sizes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $sizeId]);
        $size = $stmt->fetch();
        return $size ?: null;
    }

    public function findSizeByIdAndProduct(string $sizeId, string $productId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM product_sizes WHERE id = :id AND product_id = :product_id LIMIT 1'
        );
        $stmt->execute(['id' => $sizeId, 'product_id' => $productId]);
        $size = $stmt->fetch();
        return $size ?: null;
    }

    // ── Admin CRUD Methods ──

    public function createProduct(array $data): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products (id, name, short_description, long_description, featured, cover_gradient, cover_image, max_message_length)
             VALUES (:id, :name, :short_description, :long_description, :featured, :cover_gradient, :cover_image, :max_message_length)'
        );
        $stmt->execute([
            'id' => $data['id'],
            'name' => $data['name'],
            'short_description' => $data['shortDescription'],
            'long_description' => $data['longDescription'] ?? '',
            'featured' => ($data['featured'] ?? false) ? 1 : 0,
            'cover_gradient' => $data['coverGradient'] ?? null,
            'cover_image' => $data['coverImage'] ?? null,
            'max_message_length' => $data['maxMessageLength'] ?? 60,
        ]);

        return $this->findById($data['id']);
    }

    public function updateProduct(string $id, array $data): ?array
    {
        $fields = [];
        $params = ['id' => $id];

        $map = [
            'name' => 'name',
            'shortDescription' => 'short_description',
            'longDescription' => 'long_description',
            'coverGradient' => 'cover_gradient',
            'coverImage' => 'cover_image',
        ];

        foreach ($map as $camel => $column) {
            if (array_key_exists($camel, $data)) {
                $fields[] = "{$column} = :{$column}";
                $params[$column] = $data[$camel];
            }
        }

        if (array_key_exists('featured', $data)) {
            $fields[] = 'featured = :featured';
            $params['featured'] = $data['featured'] ? 1 : 0;
        }
        if (array_key_exists('maxMessageLength', $data)) {
            $fields[] = 'max_message_length = :max_message_length';
            $params['max_message_length'] = (int) $data['maxMessageLength'];
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $sql = 'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id);
    }

    public function deleteProduct(string $id): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('DELETE FROM product_sizes WHERE product_id = :product_id');
            $stmt->execute(['product_id' => $id]);

            $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
            $stmt->execute(['id' => $id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function createSize(string $productId, array $data): array
    {
        $sizeId = Database::generateId('sz');
        $stmt = $this->db->prepare(
            'INSERT INTO product_sizes (id, product_id, label, full_label, price, stock, low_stock_threshold)
             VALUES (:id, :product_id, :label, :full_label, :price, :stock, :low_stock_threshold)'
        );
        $stmt->execute([
            'id' => $sizeId,
            'product_id' => $productId,
            'label' => $data['label'],
            'full_label' => $data['fullLabel'],
            'price' => (int) $data['price'],
            'stock' => max(0, (int) ($data['stock'] ?? 0)),
            'low_stock_threshold' => max(0, (int) ($data['lowStockThreshold'] ?? 5)),
        ]);

        $size = $this->findSizeById($sizeId);
        if ($size === null) {
            throw new \RuntimeException('Ukuran gagal disimpan.');
        }

        return $this->formatSize($size);
    }

    public function updateSize(string $sizeId, array $data): ?array
    {
        $fields = [];
        $params = ['id' => $sizeId];

        if (array_key_exists('label', $data)) {
            $fields[] = 'label = :label';
            $params['label'] = $data['label'];
        }
        if (array_key_exists('fullLabel', $data)) {
            $fields[] = 'full_label = :full_label';
            $params['full_label'] = $data['fullLabel'];
        }
        if (array_key_exists('price', $data)) {
            $fields[] = 'price = :price';
            $params['price'] = (int) $data['price'];
        }
        if (array_key_exists('stock', $data)) {
            $fields[] = 'stock = :stock';
            $params['stock'] = max(0, (int) $data['stock']);
        }
        if (array_key_exists('lowStockThreshold', $data)) {
            $fields[] = 'low_stock_threshold = :low_stock_threshold';
            $params['low_stock_threshold'] = max(0, (int) $data['lowStockThreshold']);
        }

        if (empty($fields)) {
            $size = $this->findSizeById($sizeId);
            return $size ? $this->formatSize($size) : null;
        }

        $sql = 'UPDATE product_sizes SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $size = $this->findSizeById($sizeId);
        return $size ? $this->formatSize($size) : null;
    }

    public function deleteSize(string $sizeId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM product_sizes WHERE id = :id');
        $stmt->execute(['id' => $sizeId]);
        return $stmt->rowCount() > 0;
    }

    // ── Stock ──

    /**
     * Take `quantity` units off a size. The `stock >= :quantity` guard lives in
     * the WHERE clause so two checkouts racing for the last cake can never drive
     * stock negative — the loser simply updates 0 rows and gets false back.
     */
    public function decrementStock(string $sizeId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return true;
        }

        $stmt = $this->db->prepare(
            'UPDATE product_sizes SET stock = stock - :quantity
             WHERE id = :id AND stock >= :required'
        );
        $stmt->execute([
            'id' => $sizeId,
            'quantity' => $quantity,
            'required' => $quantity,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Put units back — used when an order is cancelled.
     */
    public function restoreStock(string $sizeId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $stmt = $this->db->prepare('UPDATE product_sizes SET stock = stock + :quantity WHERE id = :id');
        $stmt->execute(['id' => $sizeId, 'quantity' => $quantity]);
    }

    /**
     * Sizes at or below their low-stock threshold, newest shortage first.
     * Feeds the owner dashboard's restock widget.
     */
    public function findLowStock(): array
    {
        $stmt = $this->db->query(
            'SELECT ps.id, ps.stock, ps.low_stock_threshold, ps.full_label, p.name AS product_name
             FROM product_sizes ps
             JOIN products p ON p.id = ps.product_id
             WHERE ps.stock <= ps.low_stock_threshold
             ORDER BY ps.stock ASC, p.name ASC'
        );

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = [
                'id' => $row['id'],
                'productName' => $row['product_name'],
                'fullLabel' => $row['full_label'],
                'stock' => (int) $row['stock'],
                'lowStockThreshold' => (int) $row['low_stock_threshold'],
            ];
        }

        return $rows;
    }

    private function formatSize(array $size): array
    {
        $stock = (int) ($size['stock'] ?? 0);
        $threshold = (int) ($size['low_stock_threshold'] ?? 0);

        return [
            'id' => $size['id'],
            'label' => $size['label'],
            'fullLabel' => $size['full_label'],
            'price' => (int) $size['price'],
            'stock' => $stock,
            'lowStockThreshold' => $threshold,
            'inStock' => $stock > 0,
            'lowStock' => $stock > 0 && $stock <= $threshold,
        ];
    }

    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as total FROM products');
        return (int) $stmt->fetch()['total'];
    }

    private function groupProducts(array $rows): array
    {
        $products = [];
        $index = [];

        foreach ($rows as $row) {
            $pid = $row['id'];

            if (!isset($index[$pid])) {
                $index[$pid] = count($products);
                $products[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'shortDescription' => $row['short_description'],
                    'longDescription' => $row['long_description'],
                    'featured' => (bool) $row['featured'],
                    'coverGradient' => $row['cover_gradient'],
                    'coverImage' => $row['cover_image'],
                    'maxMessageLength' => (int) $row['max_message_length'],
                    'sizes' => [],
                    'startingPrice' => null,
                    'totalStock' => 0,
                ];
            }

            if ($row['size_id'] !== null) {
                $price = (int) $row['size_price'];
                $stock = (int) ($row['size_stock'] ?? 0);
                $threshold = (int) ($row['size_low_stock'] ?? 0);
                $products[$index[$pid]]['sizes'][] = [
                    'id' => $row['size_id'],
                    'label' => $row['size_label'],
                    'fullLabel' => $row['size_full_label'],
                    'price' => $price,
                    'stock' => $stock,
                    'lowStockThreshold' => $threshold,
                    'inStock' => $stock > 0,
                    'lowStock' => $stock > 0 && $stock <= $threshold,
                ];

                $products[$index[$pid]]['totalStock'] += $stock;

                $current = $products[$index[$pid]]['startingPrice'];
                if ($current === null || $price < $current) {
                    $products[$index[$pid]]['startingPrice'] = $price;
                }
            }
        }

        return $products;
    }
}
