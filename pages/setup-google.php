<?php
/**
 * Setup Google Drive — Secured
 *
 * Security layers:
 *  1. Must be logged in as admin
 *  2. URL must contain a valid HMAC setup token (generated from Settings page)
 *  3. Admin must verify password before accessing controls
 *  4. Password verification is session-locked (10-min TTL)
 *  5. All actions use CSRF protection
 *  6. OAuth state validated via session + DB fallback
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/google_client.php';

// ── Layer 1: Must be logged-in admin ─────────────────────
requireLogin();
if (!isAdmin()) {
    http_response_code(403);
    logSecurityEvent('setup_google_forbidden', ['user' => $_SESSION['username'] ?? '?']);
    echo 'Forbidden';
    exit;
}

$db = getDB();

// ── Layer 2: Validate HMAC setup token ───────────────────
// OAuth callback from Google won't have our token — we stored a flag
// in session before redirecting to Google.
$isOAuthCallback = !empty($_GET['code']);
$hasSetupPass = !empty($_SESSION['setup_google_verified'])
    && ($_SESSION['setup_google_verified_at'] ?? 0) > (time() - 600);

if (!$isOAuthCallback) {
    $setupToken = $_GET['token'] ?? '';
    if (!verifySetupToken($setupToken)) {
        http_response_code(403);
        logSecurityEvent('setup_google_bad_token', [
            'user' => $_SESSION['username'] ?? '?',
            'ip'   => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
        echo '<!doctype html><html><head><meta charset="UTF-8"><title>Akses Ditolak</title>'
           . '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;'
           . 'min-height:100vh;background:#f5f5f5;margin:0}'
           . '.box{background:#fff;padding:2rem;border-radius:12px;text-align:center;max-width:400px;'
           . 'box-shadow:0 2px 12px rgba(0,0,0,.08)}'
           . 'a{color:#50e3c2;text-decoration:none}</style></head>'
           . '<body><div class="box"><h2>&#128274; Akses Ditolak</h2>'
           . '<p>Link setup tidak valid atau sudah kedaluwarsa.</p>'
           . '<p><a href="' . htmlspecialchars(BASE_URL) . '/pages/settings">&#8592; Kembali ke Pengaturan</a></p>'
           . '</div></body></html>';
        exit;
    }
}

// ── Layer 3: Admin password verification ─────────────────
$passwordError = '';
$needPassword = !$hasSetupPass;

if (!$isOAuthCallback && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['gate_action'] ?? '') === 'verify_password') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifySecureToken($csrfToken)) {
        $passwordError = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $adminPassword = $_POST['admin_password'] ?? '';
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId && $adminPassword !== '') {
            $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ? AND role = ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$userId, 'admin']);
            $row = $stmt->fetch();

            if ($row && password_verify($adminPassword, $row['password_hash'])) {
                $_SESSION['setup_google_verified'] = true;
                $_SESSION['setup_google_verified_at'] = time();
                $needPassword = false;
                logSecurityEvent('setup_google_password_ok', ['user' => $_SESSION['username'] ?? '?']);
            } else {
                $passwordError = 'Password admin salah.';
                logSecurityEvent('setup_google_password_fail', [
                    'user' => $_SESSION['username'] ?? '?',
                    'ip'   => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
            }
        } else {
            $passwordError = 'Password tidak boleh kosong.';
        }
    }
}

// Show password gate if not verified yet (and not an OAuth callback)
if ($needPassword && !$isOAuthCallback) {
    $csrf = generateSecureToken();
    $currentToken = $_GET['token'] ?? '';
?><!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Admin - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/smk62.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
        .gate-container {
            max-width: 420px;
            margin: 80px auto;
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0,0,0,.08);
        }
        .gate-container h2 { margin: 0 0 0.5rem; font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
        .gate-container p { color: #666; font-size: 0.875rem; margin: 0 0 1.5rem; }
        .gate-field { margin-bottom: 1rem; }
        .gate-field label { display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.375rem; }
        .gate-field input {
            width: 100%;
            padding: 0.625rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .gate-field input:focus { outline: none; border-color: #50e3c2; box-shadow: 0 0 0 3px rgba(80,227,194,.15); }
        .gate-error { color: #ef4444; font-size: 0.85rem; margin-bottom: 1rem; padding: 0.5rem 0.75rem; background: #fef2f2; border-radius: 6px; }
        .gate-actions { display: flex; gap: 0.75rem; align-items: center; }
        .gate-actions .btn { flex: 1; text-align: center; }
        .back-link { font-size: 0.85rem; color: #888; text-decoration: none; }
        .back-link:hover { color: #50e3c2; }
    </style>
</head>
<body>
<div class="gate-container">
    <h2><i class="fas fa-shield-alt" style="color:#50e3c2"></i> Verifikasi Admin</h2>
    <p>Masukkan password admin Anda untuk mengakses Setup Google OAuth.</p>

    <?php if ($passwordError): ?>
        <div class="gate-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($passwordError); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo htmlspecialchars(BASE_URL . '/pages/setup-google?token=' . urlencode($currentToken)); ?>">
        <input type="hidden" name="gate_action" value="verify_password">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

        <div class="gate-field">
            <label for="admin_password"><i class="fas fa-lock"></i> Password Admin</label>
            <input type="password" id="admin_password" name="admin_password" required autofocus
                   autocomplete="current-password" placeholder="Masukkan password Anda">
        </div>

        <div class="gate-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Verifikasi</button>
        </div>
    </form>

    <div style="margin-top:1rem;text-align:center;">
        <a href="<?php echo htmlspecialchars(BASE_URL); ?>/pages/settings" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Pengaturan
        </a>
    </div>
</div>
</body>
</html>
<?php
    exit;
}

// ── Past this point: admin is verified  ──────────────────

$success = '';
$error = '';

// Handle disconnect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'disconnect') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifySecureToken($csrfToken)) {
        $error = 'Token keamanan tidak valid.';
    } else {
        try {
            systemConfigDelete($db, 'google_refresh_token');
            systemConfigDelete($db, 'google_access_token');
            $success = 'Token Google berhasil dihapus dari Database. Silakan Connect ulang.';
            logSecurityEvent('setup_google_disconnect', ['user' => $_SESSION['username'] ?? '?']);
        } catch (Throwable $e) {
            $error = 'Gagal menghapus token: ' . sanitizeErrorMessage($e->getMessage());
        }
    }
}

// Start OAuth connect
if (($_GET['action'] ?? '') === 'connect') {
    $storageConfig = getStorageConfigFromJson();
    $clientId = (string)($storageConfig['google_api']['client_id'] ?? '');
    $clientSecret = (string)($storageConfig['google_api']['client_secret'] ?? '');

    if ($clientId === '' || $clientSecret === '') {
        $error = 'Client ID/Secret belum diisi. Isi dulu di Pengaturan Penyimpanan.';
    } else {
        $client = new Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri(BASE_URL . '/pages/setup-google.php');
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

        $state = bin2hex(random_bytes(16));

        // Save state to SESSION
        $_SESSION['setup_google_oauth_state'] = $state;

        // Also save to DB as fallback (session may be lost on redirect)
        try {
            systemConfigSet($db, '_oauth_state', $state);
            systemConfigSet($db, '_oauth_state_time', (string)time());
        } catch (Throwable $e) {
            // DB fallback is optional, session is primary
        }

        $client->setState($state);
        $url = $client->createAuthUrl();

        // CRITICAL: Force PHP to write session to disk BEFORE redirecting.
        session_write_close();

        header('Location: ' . $url);
        exit;
    }
}

// OAuth callback
if ($isOAuthCallback) {
    $state = (string)($_GET['state'] ?? '');

    // Try session first
    $expected = (string)($_SESSION['setup_google_oauth_state'] ?? '');
    unset($_SESSION['setup_google_oauth_state']);

    // Fallback to DB if session state is empty
    if ($expected === '' && $state !== '') {
        try {
            $expected = (string)(systemConfigGet($db, '_oauth_state') ?? '');
            $stateTime = (int)(systemConfigGet($db, '_oauth_state_time') ?? '0');
            if ($expected !== '' && (time() - $stateTime) > 600) {
                $expected = '';
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    // Clean up DB state
    try {
        systemConfigDelete($db, '_oauth_state');
        systemConfigDelete($db, '_oauth_state_time');
    } catch (Throwable $e) {
        // ignore
    }

    $stateValid = ($expected !== '' && $state !== '' && hash_equals($expected, $state));

    if (!$stateValid) {
        $diag = [];
        if ($expected === '') $diag[] = 'session/DB state kosong (session hilang saat redirect)';
        if ($state === '') $diag[] = 'state dari Google kosong';
        if ($expected !== '' && $state !== '' && !hash_equals($expected, $state)) $diag[] = 'state tidak cocok';
        $error = 'State OAuth tidak valid: ' . implode('; ', $diag) . '. Silakan klik Connect lagi.';
        logSecurityEvent('setup_google_state_invalid', ['diag' => implode('; ', $diag)]);
    } else {
        try {
            $storageConfig = getStorageConfigFromJson();
            $clientId = (string)($storageConfig['google_api']['client_id'] ?? '');
            $clientSecret = (string)($storageConfig['google_api']['client_secret'] ?? '');

            $client = new Google_Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri(BASE_URL . '/pages/setup-google.php');
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

            $token = $client->fetchAccessTokenWithAuthCode((string)$_GET['code']);
            if (is_array($token) && isset($token['error'])) {
                $desc = (string)($token['error_description'] ?? $token['error'] ?? 'unknown');
                throw new Exception('OAuth error: ' . $desc);
            }

            $refreshToken = (string)($token['refresh_token'] ?? '');
            if ($refreshToken === '') {
                throw new Exception('Refresh token tidak diterima. Silakan revoke akses di https://myaccount.google.com/permissions lalu Connect ulang.');
            }

            systemConfigSet($db, 'google_refresh_token', $refreshToken);
            systemConfigSet($db, 'google_access_token', json_encode($token, JSON_UNESCAPED_UNICODE));

            $success = 'Google Drive berhasil terhubung. Sistem sekarang bisa upload/delete tanpa login Google per user.';
            logSecurityEvent('setup_google_connected', ['user' => $_SESSION['username'] ?? '?']);
        } catch (Throwable $e) {
            $error = sanitizeErrorMessage($e->getMessage());
        }
    }
}

$hasRefresh = false;
try {
    $hasRefresh = (bool)systemConfigGet($db, 'google_refresh_token');
} catch (Throwable $e) {
    $error = $error ?: ('Database error: ' . sanitizeErrorMessage($e->getMessage()));
}

$csrf = generateSecureToken();
$setupToken = generateSetupToken(); // For action links on this page

?><!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Google Drive - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/smk62.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
        .wrap {
            max-width: 700px;
            margin: 40px auto;
            background: #fff;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0,0,0,.08);
        }
        .wrap h2 { margin: 0 0 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .muted { color: #666; font-size: 14px; }
        .btn { cursor: pointer; }
        .security-notice {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.8rem;
            color: #166534;
            margin-top: 1.5rem;
        }
        .security-notice i { margin-right: 0.25rem; }
        .back-link { font-size: 0.85rem; color: #888; text-decoration: none; display: inline-block; margin-top: 1.5rem; }
        .back-link:hover { color: #50e3c2; }
    </style>
</head>
<body>
<div class="wrap">
    <h2><i class="fab fa-google" style="color:#4285f4"></i> Setup Google Drive</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <p class="muted">
        Halaman ini hanya untuk Admin. Setelah berhasil, semua user aplikasi akan memakai token Admin secara otomatis untuk upload/view/delete file.
    </p>

    <div class="row" style="margin-top:16px;">
        <?php if (!$hasRefresh): ?>
            <a class="btn btn-primary" href="<?php echo htmlspecialchars(BASE_URL . '/pages/setup-google?token=' . urlencode($setupToken) . '&action=connect'); ?>">
                <i class="fas fa-plug"></i> Connect Google Drive
            </a>
            <span class="muted">Belum ada refresh token di Database.</span>
        <?php else: ?>
            <span class="badge badge-success"><i class="fas fa-check"></i> Token tersimpan di Database</span>
            <a class="btn btn-outline" href="<?php echo htmlspecialchars(BASE_URL . '/pages/setup-google?token=' . urlencode($setupToken) . '&action=connect'); ?>">
                <i class="fas fa-sync-alt"></i> Connect ulang
            </a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus token Google dari DB?');">
                <input type="hidden" name="action" value="disconnect">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                <button type="submit" class="btn btn-outline"><i class="fas fa-unlink"></i> Putuskan</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="security-notice">
        <i class="fas fa-shield-alt"></i>
        Halaman ini dilindungi dengan link terenkripsi dan verifikasi password admin.
        Akses otomatis kedaluwarsa setelah 10 menit tidak aktif.
    </div>

    <a href="<?php echo htmlspecialchars(BASE_URL); ?>/pages/settings" class="back-link">
        <i class="fas fa-arrow-left"></i> Kembali ke Pengaturan
    </a>
</div>
</body>
</html>
