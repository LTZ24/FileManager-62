<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$out = [
    'ok' => false,
    'server_time' => date('c'),
    'timestamp' => time(),
    'service_account_path' => SERVICE_ACCOUNT_JSON,
    'file_exists' => file_exists(SERVICE_ACCOUNT_JSON),
];

try {
    if (!file_exists(SERVICE_ACCOUNT_JSON)) {
        echo json_encode($out); exit;
    }

    $raw = file_get_contents(SERVICE_ACCOUNT_JSON);
    $json = json_decode($raw, true);
    $out['json_valid'] = is_array($json);
    $out['has_private_key'] = isset($json['private_key']);
    $out['client_email'] = $json['client_email'] ?? null;

    $client = new Google_Client();
    $client->setAuthConfig(SERVICE_ACCOUNT_JSON);
    $client->setScopes([Google_Service_Drive::DRIVE]);

    // Attempt token exchange
    $token = $client->fetchAccessTokenWithAssertion();
    $out['token'] = $token;
    if (is_array($token) && empty($token)) {
        $out['message'] = 'Empty token response';
    }
    if (isset($token['error'])) {
        $out['error_hint'] = $token['error_description'] ?? $token['error'];
    }

    $out['ok'] = true;
} catch (Throwable $e) {
    $out['exception'] = $e->getMessage();
}

echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
