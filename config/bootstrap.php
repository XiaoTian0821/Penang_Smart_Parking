<?php
/**
 * Application Bootstrap
 * Sets up autoloading, session, and common functions
 */
declare(strict_types=1);

// Start session with secure settings
ini_set('session.cookie_httponly', getenv('SESSION_HTTP_ONLY') ? '1' : '0');
ini_set('session.cookie_secure', getenv('SESSION_SECURE') ? '1' : '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', (string)(getenv('SESSION_LIFETIME') ?: 7200));

session_start();

// Ensure storage directories exist
foreach ([STORAGE_PATH, EVIDENCE_PATH, LOGS_PATH, TEMP_PATH, UPLOADS_PATH] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
}

// Simple autoloader for namespaced classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = BASE_PATH . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load all models (global classes)
$modelDir = BASE_PATH . '/app/models/';
if (is_dir($modelDir)) {
    foreach (glob($modelDir . '*.php') as $modelFile) {
        require_once $modelFile;
    }
}

// Load all services (global classes)
$serviceDir = BASE_PATH . '/app/services/';
if (is_dir($serviceDir)) {
    foreach (glob($serviceDir . '*.php') as $serviceFile) {
        require_once $serviceFile;
    }
}

// Include helpers (these define global functions)
require_once __DIR__ . '/../app/helpers/security.php';
require_once __DIR__ . '/../app/helpers/csrf.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/plate.php';
require_once __DIR__ . '/../app/helpers/logger.php';

// Include controllers
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/CustomerController.php';
require_once __DIR__ . '/../app/controllers/OfficerController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';

/**
 * Get current logged-in user or null
 */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user']['id']) && $_SESSION['user']['id'] > 0;
}

/**
 * Check user role
 */
function hasRole(string $role): bool {
    $user = currentUser();
    return $user !== null && $user['role'] === $role;
}

/**
 * Check user permissions
 */
function hasPermission(string $permission): bool {
    $user = currentUser();
    if ($user === null) {
        return false;
    }

    // Super admin has all permissions
    if ($user['role'] === 'super_admin') {
        return true;
    }

    $permissions = $user['permissions'] ?? [];
    return in_array($permission, $permissions, true) || in_array('*', $permissions, true);
}

/**
 * Redirect with flash message
 */
function redirect(string $url, string $message = '', string $type = 'success'): void {
    if ($message !== '') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: " . APP_URL . $url);
    exit;
}

/**
 * Render a view
 */
function render(string $view, array $data = []): void {
    extract($data);
    require __DIR__ . '/../app/views/partials/header.php';
    require __DIR__ . '/../app/views/' . $view . '.php';
    require __DIR__ . '/../app/views/partials/footer.php';
}
