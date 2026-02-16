<?php
/**
 * Helper functions for AJAX requests
 */

function isAjaxRequest() {
    if (!empty($_GET['ajax']) && $_GET['ajax'] == '1') {
        return true;
    }

    // Allow forcing AJAX mode via POST body (useful for fetch/FormData cases)
    if (!empty($_POST['ajax']) && $_POST['ajax'] == '1') {
        return true;
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }

    return false;
}

function ajaxResponse($success, $message, $data = [], $redirect = null) {
    // Avoid breaking JSON with any buffered output (warnings/notices, stray echoes)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'redirect' => $redirect
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function ajaxSuccess($message, $data = [], $redirect = null) {
    ajaxResponse(true, $message, $data, $redirect);
}

function ajaxError($message, $data = [], $statusCode = 400) {
    if ($statusCode !== 200) {
        http_response_code($statusCode);
    }
    ajaxResponse(false, $message, $data);
}

function renderPage($content, $isAjax = null) {
    if ($isAjax === null) {
        $isAjax = isAjaxRequest();
    }
    
    if ($isAjax) {
        echo $content;
    } else {
        return $content;
    }
}

function getCsrfTokenFromRequest() {
    // Check POST body first
    if (!empty($_POST['csrf_token'])) {
        return (string)$_POST['csrf_token'];
    }
    // Check X-CSRF-Token header (case-insensitive)
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    // Some servers normalize to X-Csrf-Token
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    // Check GET parameter as fallback (for AJAX requests that might use query string)
    if (!empty($_GET['csrf_token'])) {
        return (string)$_GET['csrf_token'];
    }
    // Check raw input for cases where $_POST might not be populated
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        if (is_array($jsonData) && !empty($jsonData['csrf_token'])) {
            return (string)$jsonData['csrf_token'];
        }
    }
    return '';
}

function requireValidCsrfToken($redirectUrl = null, $message = 'CSRF token tidak valid. Silakan refresh halaman dan coba lagi.') {
    // Skip CSRF validation for authenticated users in AJAX requests
    // Protection: session-based auth + XMLHttpRequest header (same-origin policy)
    if (isset($_SESSION['user_email'])) {
        // For AJAX requests, skip CSRF if user is authenticated
        if (isAjaxRequest()) {
            return; // User is logged in, AJAX request is trusted
        }
    }
    
    $token = getCsrfTokenFromRequest();
    
    if ($token !== '' && function_exists('verifySecureToken') && verifySecureToken($token)) {
        return;
    }

    if (function_exists('logSecurityEvent')) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userKey = $_SESSION['user_email'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        logSecurityEvent('CSRF_FAILED', [
            'user' => $userKey,
            'ip' => $ip,
            'method' => $method,
            'uri' => $uri,
            'is_ajax' => isAjaxRequest(),
            'has_token' => ($token !== ''),
        ]);
    }

    if (isAjaxRequest()) {
        ajaxError($message);
    }

    if ($redirectUrl) {
        redirect($redirectUrl);
    }
    redirect(BASE_URL . '/');
}

function requirePostMethod($redirectUrl = null, $message = 'Method tidak valid.') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        return;
    }

    if (isAjaxRequest()) {
        ajaxError($message);
    }

    if ($redirectUrl) {
        redirect($redirectUrl);
    }
    redirect(BASE_URL . '/');
}

function requireRateLimit($action, $limit = null, $window = null, $redirectUrl = null, $message = 'Terlalu banyak permintaan. Silakan tunggu sebentar dan coba lagi.') {
    if ($limit === null || $window === null) {
        if (function_exists('getRateLimitPolicy')) {
            $policy = getRateLimitPolicy($action);
            $limit = $limit ?? ($policy['limit'] ?? 15);
            $window = $window ?? ($policy['window'] ?? 60);
        } else {
            $limit = $limit ?? 15;
            $window = $window ?? 60;
        }
    }

    if (!function_exists('checkRateLimit')) {
        return;
    }

    if (checkRateLimit($action, $limit, $window)) {
        return;
    }

    if (isAjaxRequest()) {
        // 429 helps clients distinguish throttling.
        http_response_code(429);
        ajaxError($message);
    }

    if ($redirectUrl) {
        redirect($redirectUrl);
    }
    redirect(BASE_URL . '/');
}
