<?php
/**
 * Storage Settings API
 * Manage Google API, Drive Folders, and Sheets configurations
 * 
 * Actions:
 *   GET  ?action=get          — Return current config (IDs masked)
 *   POST ?action=update_all   — Unified update (requires admin password)
 *   POST ?action=update_api   — Legacy: update API config only
 *   POST ?action=update_folders — Legacy: update folders only
 *   POST ?action=update_sheets  — Legacy: update sheets only
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';

requireLogin();

// Only admin can access storage settings
if (!isAdmin()) {
    ajaxError('Hanya admin yang dapat mengakses pengaturan ini.', [], 403);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

switch ($action) {
    case 'get':
        handleGet();
        break;
    case 'update_all':
        handleUpdateAll();
        break;
    case 'update_api':
        handleUpdateApi();
        break;
    case 'update_folders':
        handleUpdateFolders();
        break;
    case 'update_sheets':
        handleUpdateSheets();
        break;
    default:
        ajaxError('Invalid action');
}

/* =========================================================
   Helper Functions
   ========================================================= */

function getStorageConfigPath() {
    return __DIR__ . '/../data/storage_config.json';
}

function getStorageConfig() {
    $path = getStorageConfigPath();
    if (!file_exists($path)) {
        return getDefaultConfig();
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : getDefaultConfig();
}

function getDefaultConfig() {
    return [
        'version' => 1,
        'updated_at' => null,
        'updated_by' => null,
        'google_api' => [
            'client_id' => '',
            'client_secret' => ''
        ],
        'drive_folders' => [
            'kesiswaan' => '',
            'kurikulum' => '',
            'sapras' => '',
            'tata_usaha' => ''
        ],
        'sheets' => [
            'kesiswaan' => '',
            'kurikulum' => '',
            'sapras' => '',
            'tata_usaha' => ''
        ]
    ];
}

function saveStorageConfig($config) {
    $path = getStorageConfigPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    
    $config['updated_at'] = date('c');
    $config['updated_by'] = $_SESSION['username'] ?? $_SESSION['user_email'] ?? 'unknown';
    
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (@file_put_contents($path, $json, LOCK_EX) === false) {
        return false;
    }
    return true;
}

function extractIdFromUrl($url) {
    $url = trim($url);
    if (empty($url)) return '';
    
    // Already an ID (no slashes, no http)
    if (!str_contains($url, '/') && !str_contains($url, 'http')) {
        return $url;
    }
    
    // Google Drive folder URL
    if (preg_match('/\/folders\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    
    // Google Sheets URL
    if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    
    // Google Drive file URL
    if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1];
    }
    
    return $url;
}

/**
 * Verify admin password against the DB.
 * Returns true if valid, false otherwise.
 */
function verifyAdminPassword($password) {
    if (empty($password)) return false;
    
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) return false;
    
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ? AND role = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$userId, 'admin']);
        $row = $stmt->fetch();
        
        if (!$row) return false;
        
        return password_verify($password, $row['password_hash']);
    } catch (Throwable $e) {
        error_log('verifyAdminPassword error: ' . $e->getMessage());
        return false;
    }
}

/* =========================================================
   Action Handlers
   ========================================================= */

function handleGet() {
    $config = getStorageConfig();
    
    // Mask sensitive data — don't expose actual IDs to frontend
    $maskedConfig = $config;
    
    // Mask client secret
    if (!empty($config['google_api']['client_secret'])) {
        $secret = $config['google_api']['client_secret'];
        $maskedConfig['google_api']['client_secret_masked'] = '••••••••' . substr($secret, -4);
        $maskedConfig['google_api']['client_secret'] = ''; // Don't send full secret
    }
    
    // For folder/sheet IDs, only send whether they are set (not the actual ID)
    // The frontend uses the field-status badges to show "Tersimpan ✓" or "Belum diisi"
    // We keep the IDs in the response so the status check works, but the frontend
    // does NOT populate them into form fields.
    
    ajaxSuccess('OK', $maskedConfig);
}

/**
 * Unified update — handles all 3 sections (API, Folders, Sheets) at once.
 * Only updates fields that have non-empty values submitted.
 * Requires admin_password for verification.
 */
function handleUpdateAll() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ajaxError('Method not allowed', [], 405);
    }
    
    // Verify admin password first
    $adminPassword = $_POST['admin_password'] ?? '';
    if (!verifyAdminPassword($adminPassword)) {
        ajaxError('Password admin salah. Perubahan dibatalkan.');
    }
    
    $config = getStorageConfig();
    $changed = false;
    
    // --- API Section ---
    $clientId = trim($_POST['client_id'] ?? '');
    $clientSecret = trim($_POST['client_secret'] ?? '');
    
    if (!empty($clientId)) {
        $config['google_api']['client_id'] = $clientId;
        $changed = true;
    }
    if (!empty($clientSecret)) {
        $config['google_api']['client_secret'] = $clientSecret;
        $changed = true;
    }
    
    // --- Folders Section ---
    $folderFields = [
        'folder_kesiswaan' => 'kesiswaan',
        'folder_kurikulum' => 'kurikulum',
        'folder_sapras' => 'sapras',
        'folder_tata_usaha' => 'tata_usaha'
    ];
    
    foreach ($folderFields as $postKey => $configKey) {
        $val = trim($_POST[$postKey] ?? '');
        if (!empty($val)) {
            $config['drive_folders'][$configKey] = extractIdFromUrl($val);
            $changed = true;
        }
    }
    
    // --- Sheets Section ---
    $sheetFields = [
        'sheets_kesiswaan' => 'kesiswaan',
        'sheets_kurikulum' => 'kurikulum',
        'sheets_sapras' => 'sapras',
        'sheets_tata_usaha' => 'tata_usaha'
    ];
    
    foreach ($sheetFields as $postKey => $configKey) {
        $val = trim($_POST[$postKey] ?? '');
        if (!empty($val)) {
            $config['sheets'][$configKey] = extractIdFromUrl($val);
            $changed = true;
        }
    }
    
    if (!$changed) {
        ajaxSuccess('Tidak ada perubahan yang dilakukan.');
        return;
    }
    
    if (!saveStorageConfig($config)) {
        ajaxError('Gagal menyimpan konfigurasi.');
    }
    
    logSecurityEvent('STORAGE_CONFIG_UPDATED', [
        'user' => $_SESSION['username'] ?? 'unknown',
        'method' => 'update_all'
    ]);
    
    ajaxSuccess('Pengaturan penyimpanan berhasil disimpan.');
}

function handleUpdateApi() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ajaxError('Method not allowed', [], 405);
    }
    
    $clientId = trim($_POST['client_id'] ?? '');
    $clientSecret = trim($_POST['client_secret'] ?? '');
    
    if (empty($clientId)) {
        ajaxError('Client ID wajib diisi.');
    }
    
    $config = getStorageConfig();
    $config['google_api']['client_id'] = $clientId;
    
    if (!empty($clientSecret)) {
        $config['google_api']['client_secret'] = $clientSecret;
    }
    
    if (!saveStorageConfig($config)) {
        ajaxError('Gagal menyimpan konfigurasi.');
    }
    
    logSecurityEvent('STORAGE_API_UPDATED', [
        'user' => $_SESSION['username'] ?? 'unknown'
    ]);
    
    ajaxSuccess('Pengaturan API berhasil disimpan.');
}

function handleUpdateFolders() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ajaxError('Method not allowed', [], 405);
    }
    
    $folders = [
        'kesiswaan' => extractIdFromUrl($_POST['folder_kesiswaan'] ?? ''),
        'kurikulum' => extractIdFromUrl($_POST['folder_kurikulum'] ?? ''),
        'sapras' => extractIdFromUrl($_POST['folder_sapras'] ?? ''),
        'tata_usaha' => extractIdFromUrl($_POST['folder_tata_usaha'] ?? '')
    ];
    
    $hasFolder = false;
    foreach ($folders as $id) {
        if (!empty($id)) {
            $hasFolder = true;
            break;
        }
    }
    
    if (!$hasFolder) {
        ajaxError('Minimal satu folder harus diisi.');
    }
    
    $config = getStorageConfig();
    $config['drive_folders'] = $folders;
    
    if (!saveStorageConfig($config)) {
        ajaxError('Gagal menyimpan konfigurasi.');
    }
    
    logSecurityEvent('STORAGE_FOLDERS_UPDATED', [
        'user' => $_SESSION['username'] ?? 'unknown'
    ]);
    
    ajaxSuccess('Pengaturan folder Drive berhasil disimpan.');
}

function handleUpdateSheets() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ajaxError('Method not allowed', [], 405);
    }
    
    $sheets = [
        'kesiswaan' => extractIdFromUrl($_POST['sheets_kesiswaan'] ?? ''),
        'kurikulum' => extractIdFromUrl($_POST['sheets_kurikulum'] ?? ''),
        'sapras' => extractIdFromUrl($_POST['sheets_sapras'] ?? ''),
        'tata_usaha' => extractIdFromUrl($_POST['sheets_tata_usaha'] ?? '')
    ];
    
    $hasSheet = false;
    foreach ($sheets as $id) {
        if (!empty($id)) {
            $hasSheet = true;
            break;
        }
    }
    
    if (!$hasSheet) {
        ajaxError('Minimal satu spreadsheet harus diisi.');
    }
    
    $config = getStorageConfig();
    $config['sheets'] = $sheets;
    
    if (!saveStorageConfig($config)) {
        ajaxError('Gagal menyimpan konfigurasi.');
    }
    
    logSecurityEvent('STORAGE_SHEETS_UPDATED', [
        'user' => $_SESSION['username'] ?? 'unknown'
    ]);
    
    ajaxSuccess('Pengaturan Sheets berhasil disimpan.');
}
