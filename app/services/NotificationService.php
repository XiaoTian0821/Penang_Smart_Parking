<?php
/**
 * Notification Service
 * Handles system notifications
 */
declare(strict_types=1);

namespace App\Services;

class NotificationService {
    private $notificationModel;

    public function __construct() {
        $this->notificationModel = new \App\Models\NotificationModel();
    }

    public function create(int $userId, string $type, string $title, string $message, ?int $relatedId = null): int {
        return $this->notificationModel->create($userId, $type, $title, $message, $relatedId);
    }

    public function getUnread(int $userId): int {
        return $this->notificationModel->getUnread($userId);
    }

    public function getRecent(int $userId, int $limit = 20): array {
        return $this->notificationModel->getRecent($userId, $limit);
    }

    public function markAsRead(int $notificationId, int $userId): bool {
        return $this->notificationModel->markAsRead($notificationId, $userId);
    }

    public function markAllRead(int $userId): bool {
        return $this->notificationModel->markAllRead($userId);
    }
}
