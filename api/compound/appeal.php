<?php
declare(strict_types=1);
/**
 * Compound Appeal API
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

requireAuth();

$user = currentUser();
if ($user['role'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (empty($input['compound_id']) || empty($input['reason'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Compound ID and reason are required']);
    exit;
}

try {
    $appealModel = new \App\Models\AppealModel();
    $existing = $appealModel->findByCompound((int)$input['compound_id']);
    if ($existing) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This compound has already been appealed']);
        exit;
    }

    $appealId = $appealModel->create([
        'compound_id' => (int)$input['compound_id'],
        'customer_id' => $user['id'],
        'reason' => $input['reason'],
    ]);

    $notifService = new NotificationService();
    $notifService->create(1, 'appeal_submitted', 'New Appeal Submitted', "Customer {$user['id']} has submitted an appeal for compound {$input['compound_id']}.");

    echo json_encode(['success' => true, 'appeal_id' => $appealId]);
} catch (\Exception $e) {
    logEvent('api_error', "Appeal submission error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
