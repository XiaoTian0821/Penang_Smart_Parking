<?php
declare(strict_types=1);
/**
 * Enforcement Review API
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
if (!in_array($user['role'], ['officer', 'admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (empty($input['compound_id']) || empty($input['decision'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Compound ID and decision are required']);
    exit;
}

if (!in_array($input['decision'], ['issued', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid decision']);
    exit;
}

try {
    $compoundService = new CompoundService();
    $result = $compoundService->reviewCompound(
        (int)$input['compound_id'],
        $user['id'],
        $input['decision'],
        $input['remarks'] ?? ''
    );
    echo json_encode($result);
} catch (\Exception $e) {
    logEvent('api_error', "Compound review error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
