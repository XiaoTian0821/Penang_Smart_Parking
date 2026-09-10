<?php
declare(strict_types=1);
/**
 * AI Plate Recognition API
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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

requireAuth();

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (empty($input['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Image data required']);
    exit;
}

try {
    $enforcementService = new \App\Services\EnforcementService();
    $result = $enforcementService->processDetection([
        'base64' => $input['image'],
        'mime' => $input['mime'] ?? 'image/jpeg',
    ], $input['zone_id'] ?? null, $input['officer_id'] ?? null);

    echo json_encode($result);
} catch (\Throwable $e) {
    logEvent('api_error', "Plate recognition error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => APP_DEBUG ? $e->getMessage() : 'Internal server error',
    ]);
}
