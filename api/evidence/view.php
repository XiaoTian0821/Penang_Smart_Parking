<?php
declare(strict_types=1);
/**
 * Evidence Viewer API
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/bootstrap.php';

requireAuth();

$filename = $_GET['file'] ?? '';

if (strpos($filename, '/') !== false || strpos($filename, '..') !== false || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|gif)$/', $filename)) {
    http_response_code(400);
    die('Invalid file request');
}

$filePath = EVIDENCE_PATH . '/' . $filename;

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $filePath);
finfo_close($finfo);

header("Content-Type: {$mime}");
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=3600');

readfile($filePath);
logEvent('evidence_access', "File: {$filename} by user " . (currentUser()['id'] ?? 'guest'));
