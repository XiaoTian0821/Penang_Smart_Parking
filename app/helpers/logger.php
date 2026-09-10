<?php
/**
 * Logger Helper
 */
declare(strict_types=1);

/**
 * Log an event to the application log
 */
function logEvent(string $type, string $message, array $context = []): void {
    $logFile = LOGS_PATH . '/' . date('Y-m') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $user = currentUser();
    $userId = $user['id'] ?? 'guest';
    $ip = getClientIp();

    $logLine = sprintf(
        "[%s] [%s] [User:%s] [IP:%s] [%s] %s",
        $timestamp,
        $type,
        $userId,
        $ip,
        $message,
        !empty($context) ? json_encode($context) : ''
    ) . PHP_EOL;

    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

/**
 * Log AI detection
 */
function logAiDetection(string $plate, float $confidence, string $model, float $processingTime, bool $success): void {
    $logFile = LOGS_PATH . '/ai_detections.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = sprintf(
        "[%s] plate=%s confidence=%.2f model=%s time=%.3fs success=%s\n",
        $timestamp,
        $plate,
        $confidence,
        $model,
        $processingTime,
        $success ? '1' : '0'
    );
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Log enforcement action
 */
function logEnforcement(string $violation, string $plate, int $sessionId, int $officerId): void {
    logEvent('enforcement', "Violation: {$violation} | Plate: {$plate} | Session: {$sessionId} | Officer: {$officerId}");
}
