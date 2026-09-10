<?php
/**
 * Main Application Entry Point
 * Handles routing and bootstrap
 */
declare(strict_types=1);

use App\Controllers\OfficerController;
use App\Controllers\CustomerController;
use App\Controllers\AdminController;

// Define base path FIRST
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Load environment variables
require_once __DIR__ . '/../config/env.php';

// Load app config
require_once __DIR__ . '/../config/app.php';

// Load database
require_once __DIR__ . '/../config/database.php';

// Bootstrap application
require_once __DIR__ . '/../config/bootstrap.php';

// Remove the application and public directory prefixes from the request URI.
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
// Ensure $uri starts with /
if ($uri === '') {
    $uri = '/';
}

// Static file handling (CSS, JS, images)
if ($uri !== '' && $uri !== '/') {
    $publicFile = __DIR__ . ltrim($uri, '/');
    if (file_exists($publicFile) && is_file($publicFile)) {
        $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
        $mimeMap = [
            'css' => 'text/css', 'js' => 'application/javascript',
            'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif', 'svg' => 'image/svg+xml',
            'woff' => 'font/woff', 'woff2' => 'font/woff2',
            'ttf' => 'font/ttf', 'ico' => 'image/x-icon',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';
        header("Content-Type: {$mime}");
        readfile($publicFile);
        exit;
    }
}

// Route: /login, /register, /logout, /map
if ($uri === '/login' || $uri === '') {
    (new \App\Controllers\AuthController())->login();
    exit;
}
if ($uri === '/register') {
    (new \App\Controllers\AuthController())->register();
    exit;
}
if ($uri === '/logout') {
    (new \App\Controllers\AuthController())->logout();
    exit;
}
if ($uri === '/map') {
    require __DIR__ . '/map.php';
    exit;
}

// Admin routes
if (strpos($uri, '/admin') === 0) {
    requireAuth();
    $user = currentUser();
    if ($user['role'] !== 'admin' && $user['role'] !== 'super_admin') {
        redirect('/login');
    }
    $ctrl = new AdminController();
    $routes = [
        '/admin' => 'index', '/admin/' => 'index',
        '/admin/users' => 'users', '/admin/zones' => 'zones',
        '/admin/vehicles' => 'vehicles', '/admin/sessions' => 'sessions',
        '/admin/compounds' => 'compounds', '/admin/appeals' => 'appeals',
        '/admin/cameras' => 'cameras', '/admin/reports' => 'reports',
        '/admin/audit' => 'audit', '/admin/settings' => 'settings',
    ];
    $action = $routes[$uri] ?? null;
    if ($action) {
        $ctrl->$action();
    } else {
        http_response_code(404);
        require __DIR__ . '/404.php';
    }
    exit;
}

// Officer routes
if (strpos($uri, '/officer') === 0) {
    requireAuth();
    $user = currentUser();
    if (!in_array($user['role'], ['officer', 'admin', 'super_admin'])) {
        redirect('/login');
    }
    $ctrl = new OfficerController();
    $routes = [
        '/officer' => 'index', '/officer/' => 'index',
        '/officer/scan' => 'scan', '/officer/compounds' => 'compounds',
        '/officer/evidence' => 'evidence',
    ];
    $action = $routes[$uri] ?? null;
    if ($action) {
        $ctrl->$action();
    } else {
        http_response_code(404);
        require __DIR__ . '/404.php';
    }
    exit;
}

// Customer routes
if (strpos($uri, '/customer') === 0) {
    requireAuth();
    $user = currentUser();
    if ($user['role'] !== 'customer') {
        redirect('/login');
    }
    $ctrl = new CustomerController();
    $routes = [
        '/customer' => 'index', '/customer/' => 'index',
        '/customer/vehicles' => 'vehicles', '/customer/parking' => 'parking',
        '/customer/wallet' => 'wallet', '/customer/compounds' => 'compounds',
        '/customer/history' => 'history', '/customer/notifications' => 'notifications',
        '/customer/profile' => 'profile',
    ];
    $action = $routes[$uri] ?? null;
    if ($action) {
        $ctrl->$action();
    } else {
        http_response_code(404);
        require __DIR__ . '/404.php';
    }
    exit;
}

// API routes
if (strpos($uri, '/api/') === 0) {
    $apiFile = __DIR__ . '/../api' . substr($uri, 4) . '.php';
    if (file_exists($apiFile)) {
        require_once $apiFile;
        exit;
    }
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'API endpoint not found']);
    exit;
}

// Default: login page
(new \App\Controllers\AuthController())->login();
