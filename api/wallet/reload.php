<?php
declare(strict_types=1);
/**
 * Wallet Reload API
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

if (empty($input['amount']) || $input['amount'] < 1.00) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid amount (minimum RM 1.00)']);
    exit;
}

try {
    $walletService = new WalletService();
    $result = $walletService->credit(
        $user['id'],
        (float)$input['amount'],
        "ADMIN-RELOAD-" . time(),
        'ADMIN_CREDIT'
    );

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Wallet reloaded successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to reload wallet']);
    }
} catch (\Exception $e) {
    logEvent('api_error', "Wallet reload error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
