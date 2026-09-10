<?php
declare(strict_types=1);
/**
 * Parking End API
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . APP_URL);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
ini_set('display_errors', '0');

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

if (empty($input['session_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Session ID is required']);
    exit;
}

try {
    $parkingService = new \App\Services\ParkingService();
    $result = $parkingService->endSession((int)$input['session_id'], $user['id']);
    echo json_encode($result);
} catch (\Throwable $e) {
    logEvent('api_error', "Parking end error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
