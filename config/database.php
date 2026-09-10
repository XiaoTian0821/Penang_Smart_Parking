<?php
/**
 * Database Configuration
 */
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_DATABASE') ?: 'penang_parking';
        $username = getenv('DB_USERNAME') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '123456';

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            if (APP_DEBUG) {
                throw $e;
            }
            // Show friendly error
            echo '<!DOCTYPE html><html><body style="font-family:Arial;padding:40px;">';
            echo '<h2>Database Connection Error</h2>';
            echo '<p>Please ensure MySQL is running and the database has been created.</p>';
            echo '<p><strong>Steps to fix:</strong></p>';
            echo '<ol>';
            echo '<li>Open XAMPP Control Panel and start MySQL</li>';
            echo '<li>Go to phpMyAdmin (http://localhost/phpmyadmin)</li>';
            echo '<li>Create database: <code>penang_parking</code></li>';
            echo '<li>Import <code>database/schema.sql</code></li>';
            echo '<li>Optional: Import <code>database/seed.sql</code> for demo data</li>';
            echo '</ol>';
            echo '<p><a href="http://localhost">Back to home</a></p>';
            echo '</body></html>';
            exit;
        }
    }
    return $pdo;
}
