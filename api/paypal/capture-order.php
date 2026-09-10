<?php
declare(strict_types=1);
/**
 * PayPal Capture Order API
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

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (empty($input['order_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

try {
    $paypalService = new PayPalService();
    $result = $paypalService->captureOrder($input['order_id']);

    if ($result['success']) {
        $walletService = new WalletService();
        if (preg_match('/CUST(\d+)_/', $input['order_id'], $matches)) {
            $customerId = (int)$matches[1];
            $walletService->credit($customerId, $result['amount'], $result['transaction_id'] ?? $input['order_id'], 'PAYPAL_RELOAD');
            $notifService = new NotificationService();
            $notifService->create($customerId, 'wallet_reloaded', 'Wallet Reloaded', "Your wallet has been reloaded with RM {$result['amount']}.");
        }
    }

    echo json_encode($result);
} catch (\Exception $e) {
    logEvent('api_error', "PayPal capture error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
