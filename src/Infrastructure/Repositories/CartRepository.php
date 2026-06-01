<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database;
use PDO;

class CartRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByUser(string $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM carts WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $cart = $stmt->fetch();
        return $cart ?: null;
    }

    public function findBySession(string $sessionToken): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM carts WHERE session_token = :session_token LIMIT 1');
        $stmt->execute(['session_token' => $sessionToken]);
        $cart = $stmt->fetch();
        return $cart ?: null;
    }

    public function findOrCreateByUser(string $userId): array
    {
        $cart = $this->findByUser($userId);
        if ($cart) {
            return $cart;
        }

        $id = Database::generateId('cart');
        $stmt = $this->db->prepare('INSERT INTO carts (id, user_id) VALUES (:id, :user_id)');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        return $this->findByUser($userId);
    }

    public function findOrCreateBySession(string $sessionToken): array
    {
        $cart = $this->findBySession($sessionToken);
        if ($cart) {
            return $cart;
        }

        $id = Database::generateId('cart');
        $stmt = $this->db->prepare('INSERT INTO carts (id, session_token) VALUES (:id, :session_token)');
        $stmt->execute(['id' => $id, 'session_token' => $sessionToken]);

        return $this->findBySession($sessionToken);
    }

    public function delete(string $cartId): void
    {
        $stmt = $this->db->prepare('DELETE FROM carts WHERE id = :id');
        $stmt->execute(['id' => $cartId]);
    }

    public function getCartItems(string $cartId): array
    {
        $sql = 'SELECT ci.*, p.name AS product_name, p.short_description AS product_description,
                       p.cover_gradient AS product_gradient, p.cover_image AS product_image,
                       ps.label AS size_label, ps.full_label AS size_full_label, ps.price AS size_price
                FROM cart_items ci
                JOIN products p ON p.id = ci.product_id
                JOIN product_sizes ps ON ps.id = ci.size_id
                WHERE ci.cart_id = :cart_id
                ORDER BY ci.created_at ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['cart_id' => $cartId]);
        return $stmt->fetchAll();
    }

    public function addItem(string $cartId, array $data): string
    {
        $id = Database::generateId('cart');
        $stmt = $this->db->prepare(
            'INSERT INTO cart_items (id, cart_id, product_id, size_id, color_text, theme, message, quantity, unit_price, total_price)
             VALUES (:id, :cart_id, :product_id, :size_id, :color_text, :theme, :message, :quantity, :unit_price, :total_price)'
        );
        $stmt->execute([
            'id' => $id,
            'cart_id' => $cartId,
            'product_id' => $data['productId'],
            'size_id' => $data['sizeId'],
            'color_text' => $data['colorText'] ?? '',
            'theme' => $data['theme'] ?? '',
            'message' => $data['message'] ?? '',
            'quantity' => (int) $data['quantity'],
            'unit_price' => (int) $data['unitPrice'],
            'total_price' => (int) $data['unitPrice'] * (int) $data['quantity'],
        ]);

        return $id;
    }

    public function findItemById(string $itemId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cart_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $itemId]);
        $item = $stmt->fetch();
        return $item ?: null;
    }

    public function updateItem(string $itemId, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE cart_items SET size_id = :size_id, color_text = :color_text, theme = :theme,
             message = :message, quantity = :quantity, unit_price = :unit_price, total_price = :total_price
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $itemId,
            'size_id' => $data['sizeId'],
            'color_text' => $data['colorText'] ?? '',
            'theme' => $data['theme'] ?? '',
            'message' => $data['message'] ?? '',
            'quantity' => (int) $data['quantity'],
            'unit_price' => (int) $data['unitPrice'],
            'total_price' => (int) $data['unitPrice'] * (int) $data['quantity'],
        ]);
    }

    public function updateItemQuantity(string $itemId, int $quantity, int $unitPrice): void
    {
        $stmt = $this->db->prepare(
            'UPDATE cart_items SET quantity = :quantity, total_price = :total_price WHERE id = :id'
        );
        $stmt->execute([
            'id' => $itemId,
            'quantity' => $quantity,
            'total_price' => $unitPrice * $quantity,
        ]);
    }

    public function removeItem(string $itemId): void
    {
        $stmt = $this->db->prepare('DELETE FROM cart_items WHERE id = :id');
        $stmt->execute(['id' => $itemId]);
    }

    public function clearItems(string $cartId): void
    {
        $stmt = $this->db->prepare('DELETE FROM cart_items WHERE cart_id = :cart_id');
        $stmt->execute(['cart_id' => $cartId]);
    }

    public function mergeGuestCart(string $sessionToken, string $userId): void
    {
        $guestCart = $this->findBySession($sessionToken);
        if (!$guestCart) {
            return;
        }

        $guestItems = $this->getCartItems($guestCart['id']);
        if (empty($guestItems)) {
            $this->delete($guestCart['id']);
            return;
        }

        $userCart = $this->findOrCreateByUser($userId);

        $stmt = $this->db->prepare('UPDATE cart_items SET cart_id = :new_cart_id WHERE cart_id = :old_cart_id');
        $stmt->execute([
            'new_cart_id' => $userCart['id'],
            'old_cart_id' => $guestCart['id'],
        ]);

        $this->delete($guestCart['id']);
    }

    public static function formatCartItem(array $row): array
    {
        return [
            'id' => $row['id'],
            'productId' => $row['product_id'],
            'productName' => $row['product_name'],
            'productDescription' => $row['product_description'] ?? '',
            'productGradient' => $row['product_gradient'] ?? '',
            'productImage' => $row['product_image'] ?? null,
            'size' => [
                'id' => $row['size_id'],
                'label' => $row['size_label'],
                'fullLabel' => $row['size_full_label'] ?? '',
                'price' => (int) $row['size_price'],
            ],
            'colorText' => $row['color_text'],
            'theme' => $row['theme'],
            'message' => $row['message'],
            'quantity' => (int) $row['quantity'],
            'unitPrice' => (int) $row['unit_price'],
            'totalPrice' => (int) $row['total_price'],
        ];
    }
}
