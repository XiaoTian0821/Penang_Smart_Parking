<?php
/**
 * Penang Smart Parking - Maintenance Cron
 * Run periodically to clean up expired sessions, overdue compounds, etc.
 *
 * Example crontab entry:
 * 5 * * * * cd /path/to/penang_parking && php cron/maintenance.php >> /path/to/logs/cron.log 2>&1
 */
declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/bootstrap.php';

$pdo = db();
$logFile = LOGS_PATH . '/cron.log';

function cronLog(string $message): void {
    global $logFile;
    $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

cronLog("Maintenance cron started");

// 1. Expire old active sessions
$stmt = $pdo->prepare("UPDATE parking_sessions SET status = 'expired' WHERE status = 'active' AND end_time < NOW()");
$expiredCount = $stmt->rowCount();
if ($expiredCount > 0) {
    cronLog("Expired {$expiredCount} parking sessions");
}

// 2. Mark overdue compounds
$stmt = $pdo->prepare("UPDATE compounds SET status = 'overdue' WHERE status = 'issued' AND due_date < NOW()");
$overdueCount = $stmt->rowCount();
if ($overdueCount > 0) {
    cronLog("Marked {$overdueCount} compounds as overdue");
}

// 3. Clean up old temporary files
$cutoff = time() - (86400 * 7);
$dir = new DirectoryIterator(TEMP_PATH);
foreach ($dir as $file) {
    if ($file->isFile() && $file->getMTime() < $cutoff) {
        @unlink($file->getPathname());
    }
}

// 4. Send parking-ending-soon notifications
$stmt = $pdo->prepare(
    "SELECT ps.id, ps.customer_id, ps.end_time, ps.session_number
     FROM parking_sessions ps
     WHERE ps.status = 'active'
     AND ps.end_time > NOW()
     AND ps.end_time <= DATE_ADD(NOW(), INTERVAL 30 MINUTE)
     AND ps.id NOT IN (
         SELECT related_id FROM notifications
         WHERE type = 'parking_ending_soon'
         AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
     )"
);
$stmt->execute();
$soonSessions = $stmt->fetchAll();

foreach ($soonSessions as $session) {
    $notif = new \App\Models\NotificationModel();
    $notif->create(
        $session['customer_id'],
        'parking_ending_soon',
        'Parking Ending Soon',
        "Your parking session {$session['session_number']} will end in 30 minutes. Extend now to avoid a violation."
    );
    cronLog("Sent ending soon notification to customer {$session['customer_id']}");
}

cronLog("Maintenance cron completed");
