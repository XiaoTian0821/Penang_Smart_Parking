<?php
define('BASE_PATH', __DIR__);
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/bootstrap.php';

// Simulate customer route
$_SERVER['REQUEST_URI'] = '/Penang_Smart_Parking/public/customer/parking';
$_SERVER['SCRIPT_NAME'] = '/Penang_Smart_Parking/public/index.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$appPath = rtrim((string)parse_url(APP_URL, PHP_URL_PATH), '/');
$uri = $requestUri;

if ($appPath !== '' && ($uri === $appPath || str_starts_with($uri, $appPath . '/'))) {
    $uri = substr($uri, strlen($appPath));
}

if ($uri === '/public' || str_starts_with($uri, '/public/')) {
    $uri = substr($uri, strlen('/public'));
}

$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

echo "Testing route: $uri\n";

// Check if class exists
if (class_exists('CustomerController')) {
    echo "CustomerController class exists\n";
} else {
    echo "CustomerController class NOT found\n";
    // Try to load it
    require_once __DIR__ . '/app/controllers/CustomerController.php';
    echo "After manual require: " . (class_exists('CustomerController') ? 'EXISTS' : 'NOT FOUND') . "\n";
}
