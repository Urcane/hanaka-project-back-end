<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Infrastructure\Database;
use PDO;

/**
 * Owner → admin broadcast notices.
 *
 * "Read" is tracked per admin in announcement_reads, so one admin dismissing a
 * notice does not hide it from the rest of the team.
 */
class AnnouncementRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(string $authorId, string $title, string $body, string $priority = 'info'): array
    {
        $id = Database::generateId('ann');

        $stmt = $this->db->prepare(
            'INSERT INTO announcements (id, author_id, title, body, priority)
             VALUES (:id, :author_id, :title, :body, :priority)'
        );
        $stmt->execute([
            'id' => $id,
            'author_id' => $authorId,
            'title' => $title,
            'body' => $body,
            'priority' => in_array($priority, ['info', 'penting'], true) ? $priority : 'info',
        ]);

        $created = $this->findById($id);
        if ($created === null) {
            throw new \RuntimeException('Pemberitahuan gagal disimpan.');
        }

        return $created;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.full_name AS author_name
             FROM announcements a
             LEFT JOIN users u ON u.id = a.author_id
             WHERE a.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->format($row) : null;
    }

    /**
     * All notices, newest first, each with how many admins have read it.
     */
    public function findAll(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.full_name AS author_name,
                    (SELECT COUNT(*) FROM announcement_reads r WHERE r.announcement_id = a.id) AS read_count
             FROM announcements a
             LEFT JOIN users u ON u.id = a.author_id
             ORDER BY a.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = $this->format($row) + ['readCount' => (int) $row['read_count']];
        }

        return $rows;
    }

    /**
     * Notices this admin has not dismissed yet — the banner on every admin page.
     */
    public function findUnreadFor(string $userId, int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, u.full_name AS author_name
             FROM announcements a
             LEFT JOIN users u ON u.id = a.author_id
             LEFT JOIN announcement_reads r
                    ON r.announcement_id = a.id AND r.user_id = :user_id
             WHERE r.announcement_id IS NULL
             ORDER BY a.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('user_id', $userId);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[] = $this->format($row);
        }

        return $rows;
    }

    public function markRead(string $announcementId, string $userId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO announcement_reads (announcement_id, user_id) VALUES (:announcement_id, :user_id)
             ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute(['announcement_id' => $announcementId, 'user_id' => $userId]);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM announcements WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) AS total FROM announcements');
        return (int) $stmt->fetch()['total'];
    }

    private function format(array $row): array
    {
        return [
            'id' => $row['id'],
            'title' => $row['title'],
            'body' => $row['body'],
            'priority' => $row['priority'],
            'authorName' => $row['author_name'] ?? 'Owner',
            'createdAt' => $row['created_at'],
        ];
    }
}
