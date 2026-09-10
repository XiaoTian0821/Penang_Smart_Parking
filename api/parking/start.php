<?php
declare(strict_types=1);
/**
 * Parking Start API
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

if (empty($input['vehicle_id']) || empty($input['zone_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vehicle ID and Zone ID are required']);
    exit;
}

try {
    $parkingService = new \App\Services\ParkingService();
    $result = $parkingService->startSession(
        $user['id'],
        (int)$input['vehicle_id'],
        (int)$input['zone_id'],
        isset($input['gps_lat']) && $input['gps_lat'] !== '' ? (float)$input['gps_lat'] : null,
        isset($input['gps_lng']) && $input['gps_lng'] !== '' ? (float)$input['gps_lng'] : null,
        $input['plate_snapshot'] ?? null
    );

    echo json_encode($result);
} catch (\Throwable $e) {
    logEvent('api_error', "Parking start error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
