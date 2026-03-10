<?php
/**
 * Authentication & Session Helpers
 */

require_once __DIR__ . '/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Authenticate user with username and password.
 */
function loginUser(string $username, string $password): ?array
{
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['name']      = $user['name'];
        return $user;
    }
    return null;
}

/**
 * Check if user is logged in.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Get current user's role.
 */
function getUserRole(): string
{
    return $_SESSION['role'] ?? '';
}

/**
 * Get current user's ID.
 */
function getUserId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

/**
 * Get current user's name.
 */
function getUserName(): string
{
    return $_SESSION['name'] ?? '';
}

/**
 * Check if current user is admin.
 */
function isAdmin(): bool
{
    return getUserRole() === 'admin';
}

/**
 * Require login – redirect if not authenticated.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Require admin role.
 */
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

/**
 * Logout user.
 */
function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p["path"], $p["domain"], $p["secure"], $p["httponly"]
        );
    }
    session_destroy();
}

/**
 * CSRF token generation and validation.
 */
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Flash message helpers.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
