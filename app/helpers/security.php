<?php
/**
 * Security Helpers
 */
declare(strict_types=1);

/**
 * Escape output for HTML
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function csrf_verify(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Get CSRF token HTML input
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validate and sanitize email
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Malaysian format)
 */
function validatePhone(string $phone): bool {
    return preg_match('/^(\+60|01)[0-9]{8,10}$/', $phone) === 1;
}

/**
 * Validate Malaysian IC number
 */
function validateIC(string $ic): bool {
    // Format: XXXXXX-XX-XXXX or XXXXXX XX XXXX
    $clean = preg_replace('/[-\s]/', '', $ic);
    return preg_match('/^\d{12}$/', $clean) === 1;
}

/**
 * Validate vehicle plate (Malaysian format)
 */
function validatePlate(string $plate): bool {
    // Accept formats like WXY1234, WXY 1234, WXY-1234
    $clean = preg_replace('/[\s-]/', '', strtoupper($plate));
    return preg_match('/^[A-Z]{2,4}\d{1,6}$/', $clean) === 1;
}

/**
 * Rate limiting
 */
function checkRateLimit(string $key, int $maxAttempts = 5, int $window = 300): bool {
    $cacheFile = TEMP_PATH . '/rate_' . md5($key) . '.txt';
    $now = time();

    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true) ?: ['attempts' => 0, 'reset' => 0];

        if ($now > $data['reset']) {
            // Window expired, reset
            file_put_contents($cacheFile, json_encode(['attempts' => 1, 'reset' => $now + $window]));
            return true;
        }

        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }

        $data['attempts']++;
        file_put_contents($cacheFile, json_encode($data));
        return true;
    }

    file_put_contents($cacheFile, json_encode(['attempts' => 1, 'reset' => $now + $window]));
    return true;
}

/**
 * Get IP address
 */
function getClientIp(): string {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
