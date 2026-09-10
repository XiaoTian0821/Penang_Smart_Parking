<?php
/**
 * Application Configuration
 */
declare(strict_types=1);

// Define paths first (needed by env.php)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
define('CONFIG_PATH', BASE_PATH . '/config');
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', __DIR__);
define('STORAGE_PATH', BASE_PATH . '/storage');
define('EVIDENCE_PATH', STORAGE_PATH . '/evidence');
define('LOGS_PATH', STORAGE_PATH . '/logs');
define('TEMP_PATH', BASE_PATH . '/storage/temp');
define('UPLOADS_PATH', BASE_PATH . '/storage/uploads');

define('APP_NAME', getenv('APP_NAME') ?: 'Penang Smart Parking');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', (int)getenv('APP_DEBUG') ?: 0);
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'Asia/Kuala_Lumpur');
define('APP_KEY', getenv('APP_KEY') ?: 'default-insecure-key-change-me');

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}
