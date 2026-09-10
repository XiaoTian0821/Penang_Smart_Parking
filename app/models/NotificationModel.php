<?php
/**
 * Notification Model
 */

namespace App\Models;

class NotificationModel {
    private $pdo;

    public function __construct() {
        $this->pdo = db();
    }

    public function create(int $userId, string $type, string $title, string $message, ?int $relatedId = null): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, type, title, message, related_id, is_read)
             VALUES (?, ?, ?, ?, ?, 0)'
        );
        $stmt->execute([$userId, $type, $title, $message, $relatedId]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getUnread(int $userId): int {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getRecent(int $userId, int $limit = 20): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function markAsRead(int $notificationId, int $userId): bool {
        $stmt = $this->pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        return $stmt->execute([$notificationId, $userId]);
    }

    public function markAllRead(int $userId): bool {
        $stmt = $this->pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        return $stmt->execute([$userId]);
    }
}
