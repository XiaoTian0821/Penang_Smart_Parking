<?php
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

requireAuth();
$user = currentUser();
if ($user['role'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
if (!csrf_verify((string)($input['csrf_token'] ?? ''))) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$vehicleModel = new \App\Models\VehicleModel();
$action = $input['action'] ?? '';

try {
    if ($action === 'delete') {
        $vehicleId = (int)($input['vehicle_id'] ?? 0);
        if ($vehicleId < 1 || !$vehicleModel->delete($vehicleId, (int)$user['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unable to delete vehicle']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Vehicle deleted successfully']);
        exit;
    }

    if ($action === 'create') {
        $plate = trim((string)($input['plate'] ?? ''));
        if ($plate === '' || !validatePlate($plate)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please enter a valid plate number']);
            exit;
        }

        $vehicleId = $vehicleModel->create([
            'owner_id' => (int)$user['id'],
            'plate' => $plate,
            'vehicle_type' => trim((string)($input['vehicle_type'] ?? 'car')),
            'color' => trim((string)($input['color'] ?? '')),
            'make' => trim((string)($input['make'] ?? '')),
            'model' => trim((string)($input['model'] ?? '')),
            'year' => ($input['year'] ?? '') !== '' ? (int)$input['year'] : null,
        ]);

        echo json_encode(['success' => true, 'vehicle_id' => $vehicleId, 'message' => 'Vehicle added successfully']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid vehicle action']);
} catch (Throwable $exception) {
    logEvent('api_error', 'Vehicle API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to process vehicle request']);
}
