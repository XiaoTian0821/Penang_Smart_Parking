<?php
/**
 * Authentication Helpers
 */
declare(strict_types=1);

/**
 * Hash a password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify a password
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Login a user
 */
function loginUser(int $userId): bool {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, email, full_name, role, status, permissions FROM users WHERE id = ? AND status = ?');
    $stmt->execute([$userId, 'active']);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    // Parse permissions
    $permissions = [];
    if (!empty($user['permissions'])) {
        $permissions = json_decode($user['permissions'], true) ?: [];
    }

    // Regenerate session
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'role' => $user['role'],
        'permissions' => $permissions,
    ];

    // Log login
    logEvent('login', "User ID {$userId} logged in from " . getClientIp());

    return true;
}

/**
 * Logout current user
 */
function logoutUser(): void {
    $userId = $_SESSION['user']['id'] ?? null;
    if ($userId) {
        logEvent('logout', "User ID {$userId} logged out");
    }
    session_destroy();
    session_start();
}

/**
 * Require authentication
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

/**
 * Require role
 */
function requireRole(string $role): void {
    requireAuth();
    if (!hasRole($role)) {
        http_response_code(403);
        die('Access denied.');
    }
}

/**
 * Require permission
 */
function requirePermission(string $permission): void {
    requireAuth();
    if (!hasPermission($permission)) {
        http_response_code(403);
        die('Access denied.');
    }
}
