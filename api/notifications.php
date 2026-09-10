<?php
declare(strict_types=1);
/**
 * Notification API
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . APP_URL);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

requireAuth();

$user = currentUser();
$notifService = new NotificationService();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = (int)($_GET['limit'] ?? 20);
    $notifications = $notifService->getRecent($user['id'], $limit);
    echo json_encode(['success' => true, 'data' => $notifications]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if (isset($input['mark_all_read'])) {
        $notifService->markAllRead($user['id']);
        echo json_encode(['success' => true]);
    } elseif (isset($input['notification_id'])) {
        $notifService->markAsRead((int)$input['notification_id'], $user['id']);
        echo json_encode(['success' => true]);
    }
}
