<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database;
use PDO;

class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(string $id, string $fullName, string $email, string $phone, string $passwordHash, string $role = 'customer'): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES (:id, :full_name, :email, :phone, :password_hash, :role)'
        );
        $stmt->execute([
            'id' => $id,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => $passwordHash,
            'role' => $role,
        ]);

        return $this->findById($id);
    }

    public function findByPhoneExcept(string $phone, string $exceptId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE phone = :phone AND id != :id LIMIT 1'
        );
        $stmt->execute(['phone' => $phone, 'id' => $exceptId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updateProfile(string $id, string $fullName, string $phone): ?array
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET full_name = :full_name, phone = :phone WHERE id = :id'
        );
        $stmt->execute([
            'full_name' => $fullName,
            'phone' => $phone,
            'id' => $id,
        ]);

        return $this->findById($id);
    }

    public function findAllCustomers(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as total FROM users WHERE role = :role');
        $stmt->execute(['role' => $role]);
        return (int) $stmt->fetch()['total'];
    }

    public static function formatUser(array $row): array
    {
        return [
            'id' => $row['id'],
            'fullName' => $row['full_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'role' => $row['role'] ?? 'customer',
            'createdAt' => $row['created_at'],
        ];
    }
}
