<?php
declare(strict_types=1);
/**
 * PayPal Webhook API
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: application/json');

$body = file_get_contents('php://input');
$headers = getallheaders();

try {
    $paypalService = new PayPalService();
    $result = $paypalService->handleWebhook($body, $headers);
    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
} catch (\Exception $e) {
    logEvent('api_error', "PayPal webhook error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
