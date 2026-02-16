<?php
/**
 * Users Management API
 * Admin-only endpoints for managing user accounts
 * 
 * Actions:
 *   GET  ?action=list    — List all users (no passwords)
 *   POST  action=create  — Create new user (requires admin password)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';

requireLogin();

// Only admin can access user management
if (!isAdmin()) {
    ajaxError('Hanya admin yang dapat mengakses manajemen pengguna.', [], 403);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        handleListUsers();
        break;
    case 'create':
        handleCreateUser();
        break;
    case 'update':
        handleUpdateUser();
        break;
    case 'delete':
        handleDeleteUser();
        break;
    default:
        ajaxError('Invalid action');
}

/* =========================================================
   Helper: Verify Admin Password
   ========================================================= */

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

function handleListUsers() {
    try {
        $db = getDB();
        $stmt = $db->query('SELECT id, username, email, role, is_active, created_at FROM users ORDER BY created_at ASC');
        $users = $stmt->fetchAll();
        
        // Remove sensitive fields, ensure booleans
        $result = array_map(function($u) {
            return [
                'id' => (int)$u['id'],
                'username' => $u['username'],
                'email' => $u['email'] ?? '',
                'role' => $u['role'],
                'is_active' => (bool)($u['is_active'] ?? true),
                'created_at' => $u['created_at'] ?? null
            ];
        }, $users);
        
        ajaxSuccess('OK', $result);
    } catch (Throwable $e) {
        error_log('handleListUsers error: ' . $e->getMessage());
        ajaxError('Gagal memuat daftar pengguna.');
    }
}

function handleCreateUser() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ajaxError('Method not allowed', [], 405);
    }
    
    // Verify admin password first
    $adminPassword = $_POST['admin_password'] ?? '';
    if (!verifyAdminPassword($adminPassword)) {
        ajaxError('Password admin salah. Pembuatan akun dibatalkan.');
    }
    
    // Validate inputs
    $username = strtolower(trim($_POST['new_username'] ?? ''));
    $email = trim($_POST['new_email'] ?? '');
    $role = 'staff/guru'; // Only staff/guru can be created
    $password = $_POST['new_password'] ?? '';
    $passwordConfirm = $_POST['new_password_confirm'] ?? '';
    
    // Validations
    if (empty($username)) {
        ajaxError('Username wajib diisi.');
    }
    
    if (strlen($username) < 3) {
        ajaxError('Username minimal 3 karakter.');
    }
    
    if (!preg_match('/^[a-z0-9_]+$/', $username)) {
        ajaxError('Username hanya boleh berisi huruf kecil, angka, dan underscore.');
    }
    
    if (empty($password)) {
        ajaxError('Password wajib diisi.');
    }
    
    if (strlen($password) < 6) {
        ajaxError('Password minimal 6 karakter.');
    }
    
    if ($password !== $passwordConfirm) {
        ajaxError('Password tidak cocok.');
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ajaxError('Format email tidak valid.');
    }
    
    try {
        $db = getDB();
        
        // Check if username already exists
        $stmt = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            ajaxError('Username "' . $username . '" sudah digunakan.');
        }
        
        // Check if email already exists (if provided)
        if (!empty($email)) {
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                ajaxError('Email "' . $email . '" sudah digunakan.');
            }
        }
        
        // Create the user
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare('INSERT INTO users (username, password_hash, email, role, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
        $stmt->execute([
            $username,
            $passwordHash,
            $email ?: null,
            $role
        ]);
        
        logSecurityEvent('USER_CREATED', [
            'created_by' => $_SESSION['username'] ?? 'unknown',
            'new_user' => $username,
            'role' => $role
        ]);
        
        ajaxSuccess('Akun "' . $username . '" berhasil dibuat dengan role ' . $role . '.');
        
    } catch (Throwable $e) {
        error_log('handleCreateUser error: ' . $e->getMessage());
        ajaxError('Gagal membuat akun: ' . $e->getMessage());
    }
}

function handleUpdateUser() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ajaxError('Method not allowed', [], 405);
    }
    
    // Verify admin password first
    $adminPassword = $_POST['admin_password'] ?? '';
    if (!verifyAdminPassword($adminPassword)) {
        ajaxError('Password admin salah. Perubahan dibatalkan.');
    }
    
    $userId = (int)($_POST['edit_user_id'] ?? 0);
    $email = trim($_POST['edit_email'] ?? '');
    $password = $_POST['edit_password'] ?? '';
    $isActive = (int)($_POST['edit_is_active'] ?? 1);
    
    if ($userId <= 0) {
        ajaxError('User ID tidak valid.');
    }
    
    if (!empty($password) && strlen($password) < 6) {
        ajaxError('Password minimal 6 karakter.');
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        ajaxError('Format email tidak valid.');
    }
    
    try {
        $db = getDB();
        
        // Verify user exists
        $stmt = $db->prepare('SELECT id, username, role FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            ajaxError('User tidak ditemukan.');
        }
        
        // Admin status cannot be changed
        if ($user['role'] === 'admin') {
            $isActive = 1;
        }
        
        // Check email uniqueness (if provided)
        if (!empty($email)) {
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                ajaxError('Email "' . $email . '" sudah digunakan.');
            }
        }
        
        // Build update query (no role change)
        $fields = ['email = ?', 'is_active = ?'];
        $params = [$email ?: null, $isActive];
        
        if (!empty($password)) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $params[] = $userId;
        
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        logSecurityEvent('USER_UPDATED', [
            'updated_by' => $_SESSION['username'] ?? 'unknown',
            'target_user' => $user['username'],
            'password_changed' => !empty($password)
        ]);
        
        ajaxSuccess('Akun "' . $user['username'] . '" berhasil diperbarui.');
        
    } catch (Throwable $e) {
        error_log('handleUpdateUser error: ' . $e->getMessage());
        ajaxError('Gagal memperbarui akun: ' . $e->getMessage());
    }
}

function handleDeleteUser() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ajaxError('Method not allowed', [], 405);
    }
    
    // Verify admin password first
    $adminPassword = $_POST['admin_password'] ?? '';
    if (!verifyAdminPassword($adminPassword)) {
        ajaxError('Password admin salah. Penghapusan dibatalkan.');
    }
    
    $userId = (int)($_POST['delete_user_id'] ?? 0);
    
    if ($userId <= 0) {
        ajaxError('User ID tidak valid.');
    }
    
    try {
        $db = getDB();
        
        // Verify user exists and is not admin and is inactive
        $stmt = $db->prepare('SELECT id, username, role, is_active FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            ajaxError('User tidak ditemukan.');
        }
        
        if ($user['role'] === 'admin') {
            ajaxError('Akun admin tidak dapat dihapus.');
        }
        
        if ((int)$user['is_active'] === 1) {
            ajaxError('Hanya akun nonaktif yang dapat dihapus. Nonaktifkan akun terlebih dahulu.');
        }
        
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        
        logSecurityEvent('USER_DELETED', [
            'deleted_by' => $_SESSION['username'] ?? 'unknown',
            'deleted_user' => $user['username']
        ]);
        
        ajaxSuccess('Akun "' . $user['username'] . '" berhasil dihapus.');
        
    } catch (Throwable $e) {
        error_log('handleDeleteUser error: ' . $e->getMessage());
        ajaxError('Gagal menghapus akun: ' . $e->getMessage());
    }
}
