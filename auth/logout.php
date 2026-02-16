<?php
require_once __DIR__ . '/../includes/config.php';

// Log the logout event before destroying
if (isLoggedIn()) {
    logSecurityEvent('LOGOUT', ['user' => $_SESSION['username'] ?? 'unknown']);
}

// Clear all session data
$_SESSION = [];

// Expire session cookie
$params = session_get_cookie_params();
$cookieName = session_name();
if (!empty($_COOKIE[$cookieName])) {
    setcookie(
        $cookieName,
        '',
        [
            'expires' => time() - 3600,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool)($params['secure'] ?? false),
            'httponly' => (bool)($params['httponly'] ?? true),
            'samesite' => 'Lax',
        ]
    );
}

@session_destroy();

redirect(BASE_URL . '/auth/login?logout=1');
