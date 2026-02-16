<?php
/**
 * System-Wide Google OAuth Client Helper (Offline + Refresh Token)
 *
 * Stores tokens in MySQL table: system_config
 * Keys used:
 *  - google_refresh_token
 *  - google_access_token (JSON)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

/**
 * Get config value from system_config.
 */
function systemConfigGet(PDO $db, string $key): ?string {
    try {
        $stmt = $db->prepare('SELECT config_value FROM system_config WHERE config_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        if ($val === false) return null;
        return is_string($val) ? $val : null;
    } catch (PDOException $e) {
        // Table missing
        if (($e->getCode() === '42S02') || str_contains(strtolower($e->getMessage()), 'system_config')) {
            throw new Exception('Tabel system_config belum ada. Buat tabel tersebut di database terlebih dahulu.');
        }
        throw $e;
    }
}

/**
 * Set config value in system_config (upsert).
 */
function systemConfigSet(PDO $db, string $key, string $value): void {
    try {
        $stmt = $db->prepare(
            'INSERT INTO system_config (config_key, config_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$key, $value]);
    } catch (PDOException $e) {
        if (($e->getCode() === '42S02') || str_contains(strtolower($e->getMessage()), 'system_config')) {
            throw new Exception('Tabel system_config belum ada. Buat tabel tersebut di database terlebih dahulu.');
        }
        throw $e;
    }
}

function systemConfigDelete(PDO $db, string $key): void {
    $stmt = $db->prepare('DELETE FROM system_config WHERE config_key = ?');
    $stmt->execute([$key]);
}

/**
 * getGoogleClient()
 *
 * - Reads google_access_token from DB and sets it.
 * - If token missing/expired: refresh using google_refresh_token.
 * - Saves the new access token back to DB to reduce refresh calls.
 */
function getGoogleClient(?PDO $db = null, ?string $redirectUri = null): Google_Client {
    if ($db === null) {
        $db = getDB();
    }

    $storageConfig = getStorageConfigFromJson();
    $clientId = (string)($storageConfig['google_api']['client_id'] ?? '');
    $clientSecret = (string)($storageConfig['google_api']['client_secret'] ?? '');

    if ($clientId === '' || $clientSecret === '') {
        throw new Exception('Google Client ID/Secret belum diatur. Silakan isi di Pengaturan Penyimpanan.');
    }

    if ($redirectUri === null) {
        $redirectUri = BASE_URL . '/pages/setup-google.php';
    }

    $client = new Google_Client();
    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);

    // IMPORTANT: offline + consent => to get refresh_token
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->setIncludeGrantedScopes(true);

    $client->setScopes([
        Google_Service_Drive::DRIVE,
        Google_Service_Sheets::SPREADSHEETS,
        'openid',
        'email',
        'profile',
    ]);

    // Load access token from DB if exists
    $accessJson = systemConfigGet($db, 'google_access_token');
    if ($accessJson) {
        $decoded = json_decode($accessJson, true);
        if (is_array($decoded)) {
            $client->setAccessToken($decoded);
        }
    }

    // Treat missing token as expired
    $hasToken = (bool)$client->getAccessToken();
    if (!$hasToken || $client->isAccessTokenExpired()) {
        // Ambil Refresh Token dari DB
        $refreshToken = systemConfigGet($db, 'google_refresh_token');

        if ($refreshToken) {
            // Refresh token ke Google
            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            if (is_array($newToken) && isset($newToken['error'])) {
                $err = (string)($newToken['error'] ?? 'unknown');
                $desc = (string)($newToken['error_description'] ?? '');
                $msg = 'OAuth refresh token error: ' . $err;
                if ($desc !== '') $msg .= ' — ' . $desc;
                throw new Exception($msg);
            }

            $client->setAccessToken($newToken);

            // Simpan Access Token baru ke DB agar hemat request berikutnya
            systemConfigSet($db, 'google_access_token', json_encode($client->getAccessToken(), JSON_UNESCAPED_UNICODE));

            // If Google returns a new refresh_token (rare), persist it.
            if (!empty($newToken['refresh_token'])) {
                systemConfigSet($db, 'google_refresh_token', (string)$newToken['refresh_token']);
            }
        } else {
            throw new Exception('Error: Admin belum melakukan otorisasi Google Drive. Buka Pengaturan > Setup OAuth lalu klik "Connect Google Drive".');
        }
    }

    return $client;
}
