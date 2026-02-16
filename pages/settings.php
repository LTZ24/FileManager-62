<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';

requireLogin();

$success = '';
$error = '';

if (!empty($_GET['success'])) {
    $success = (string)$_GET['success'];
}

if (!empty($_GET['error'])) {
    $error = (string)$_GET['error'];
}

// Check if user is admin
$isAdmin = isAdmin();

$storageOAuthConnected = false;
$storageOAuthLabel = '';
if ($isAdmin) {
    try {
        $helperPath = __DIR__ . '/../includes/google_client.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
            $db = getDB();
            $refresh = systemConfigGet($db, 'google_refresh_token');
            if (!empty($refresh)) {
                $storageOAuthConnected = true;
                $storageOAuthLabel = 'Token tersimpan di DB';
            }
        }
    } catch (Throwable $e) {
        // If system_config missing, keep as not connected
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/smk62.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
            min-width: 0;
        }
        
        .settings-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .settings-section h2 {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            font-size: 1.5rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .setting-item {
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .setting-info {
            flex: 1;
            min-width: 250px;
        }
        
        .setting-info h3 {
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .setting-info p {
            color: var(--text-color);
            font-size: 0.875rem;
            line-height: 1.5;
        }
        
        .setting-action {
            flex-shrink: 0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
        }

        .setting-action .btn {
            min-width: 170px;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .badge-success {
            background: #10b981;
            color: white;
        }
        
        .badge-warning {
            background: #f59e0b;
            color: white;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .settings-section {
                padding: 1rem;
            }
            
            .setting-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .setting-info {
                min-width: 0;
                width: 100%;
            }
            
            .setting-action {
                width: 100%;
                flex-wrap: wrap;
            }
            
            .setting-action button,
            .setting-action .btn,
            .setting-action form {
                width: 100%;
            }
            
            .user-table-wrap {
                margin: 0 -1rem;
                padding: 0 1rem;
            }
        }

        /* ===== Unified Storage Modal ===== */
        .storage-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .storage-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .storage-modal {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow: hidden;
            transform: scale(0.9) translateY(20px);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .storage-modal-overlay.active .storage-modal {
            transform: scale(1) translateY(0);
        }
        
        .storage-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        
        .storage-modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .storage-modal-header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .storage-modal-close {
            background: rgba(255,255,255,0.2);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            color: white;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .storage-modal-close:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .storage-modal-body {
            padding: 0;
            overflow-y: auto;
            flex: 1;
        }

        /* Tabs */
        .modal-tabs {
            display: flex;
            border-bottom: 2px solid #e2e8f0;
            background: #f8fafc;
        }

        .modal-tab {
            flex: 1;
            padding: 0.875rem 1rem;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            border: none;
            background: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .modal-tab:hover {
            color: #3b82f6;
            background: #eff6ff;
        }

        .modal-tab.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
            background: white;
        }

        .tab-panel {
            display: none;
            padding: 1.5rem;
        }

        .tab-panel.active {
            display: block;
        }
        
        .storage-form-group {
            margin-bottom: 1.25rem;
        }
        
        .storage-form-group:last-child {
            margin-bottom: 0;
        }
        
        .storage-form-group label {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #334155;
            font-size: 0.875rem;
        }
        
        .storage-form-group label i {
            margin-right: 0.5rem;
            color: #3b82f6;
        }
        
        .storage-form-group input,
        .storage-form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f8fafc;
        }
        
        .storage-form-group input:focus,
        .storage-form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: white;
        }
        
        .storage-form-group input::placeholder {
            color: #94a3b8;
        }
        
        .storage-form-hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.375rem;
        }

        .field-status {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 4px;
            margin-left: 8px;
            white-space: nowrap;
        }

        .field-status.configured {
            color: #10b981;
            background: #ecfdf5;
        }

        .field-status.empty {
            color: #f59e0b;
            background: #fffbeb;
        }

        /* Password confirmation section in modal */
        .password-confirm-section {
            padding: 1rem 1.5rem;
            background: #fef3c7;
            border-top: 1px solid #fde68a;
        }

        .password-confirm-section label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #92400e;
            font-size: 0.8rem;
        }

        .password-confirm-section label i {
            margin-right: 0.5rem;
        }

        .password-confirm-section input {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 2px solid #fde68a;
            border-radius: 8px;
            font-size: 0.875rem;
            background: white;
            transition: border-color 0.2s;
        }

        .password-confirm-section input:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        .password-confirm-section .hint {
            font-size: 0.7rem;
            color: #92400e;
            margin-top: 0.375rem;
        }
        
        .storage-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            background: #f8fafc;
        }
        
        .storage-modal-footer .btn {
            min-width: 100px;
        }

        /* ===== Create User Modal Header ===== */
        .create-user-modal-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }

        /* Info Popup Styles */
        .info-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .info-popup-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .info-popup {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            max-height: 80vh;
            overflow: hidden;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        
        .info-popup-overlay.active .info-popup {
            transform: scale(1);
        }
        
        .info-popup-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f1f5f9;
        }
        
        .info-popup-header h4 {
            margin: 0;
            font-size: 0.95rem;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-popup-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: #64748b;
            padding: 0;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: background 0.2s;
        }
        
        .info-popup-close:hover {
            background: #e2e8f0;
        }
        
        .info-popup-body {
            padding: 1.25rem;
            font-size: 0.8rem;
            color: #475569;
            line-height: 1.6;
            overflow-y: auto;
            max-height: 60vh;
        }
        
        .info-popup-body ol {
            margin: 0;
            padding-left: 1.25rem;
        }
        
        .info-popup-body li {
            margin-bottom: 0.5rem;
        }
        
        .info-popup-body code {
            background: #f1f5f9;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #3b82f6;
        }
        
        .info-popup-body a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .info-popup-body a:hover {
            text-decoration: underline;
        }

        /* User list table */
        .user-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -0.5rem;
            padding: 0 0.5rem;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
            min-width: 480px;
            white-space: nowrap;
        }

        .user-table th {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 2px solid #e2e8f0;
            background: #f1f5f9;
            font-weight: 600;
            color: #475569;
            font-size: 0.8rem;
        }

        .user-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .role-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .role-badge.admin {
            background: #eff6ff;
            color: #3b82f6;
        }

        .role-badge.operator {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .status-dot.active { background: #10b981; }
        .status-dot.inactive { background: #ef4444; }
        
        /* Mobile Responsive for Modals */
        @media (max-width: 768px) {
            .storage-modal {
                max-width: 100%;
                max-height: 85vh;
                border-radius: 16px 16px 0 0;
                margin-top: auto;
            }
            
            .storage-modal-overlay {
                align-items: flex-end;
                padding: 0;
            }
            
            .storage-modal-overlay.active .storage-modal {
                transform: translateY(0);
            }
            
            .storage-modal-header {
                padding: 1rem 1.25rem;
            }

            .modal-tab {
                font-size: 0.7rem;
                padding: 0.75rem 0.5rem;
            }

            .tab-panel {
                padding: 1.25rem;
            }
            
            .storage-modal-footer {
                flex-direction: column;
            }
            
            .storage-modal-footer .btn {
                width: 100%;
            }
            
            .info-popup {
                max-width: 100%;
                margin: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <?php include __DIR__ . '/../includes/page-navigation.php'; ?>
            <div class="settings-container">
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($isAdmin): ?>
                <!-- Pengaturan Penyimpanan (Admin Only) -->
                <div class="settings-section">
                    <h2><i class="fas fa-cloud"></i> Pengaturan Penyimpanan</h2>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Konfigurasi Google Storage</h3>
                            <p>Kelola Client ID, folder Drive, dan Spreadsheet dalam satu pengaturan</p>
                        </div>
                        <div class="setting-action">
                            <button type="button" class="btn btn-primary" onclick="openStorageModal()">
                                <i class="fas fa-cog"></i> Konfigurasi
                            </button>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Setup OAuth Google</h3>
                            <p>Hubungkan akun Google untuk akses penulisan ke Drive</p>
                        </div>
                        <div class="setting-action">
                            <a class="btn btn-outline" href="<?php echo htmlspecialchars(BASE_URL . '/pages/setup-google?token=' . urlencode(generateSetupToken())); ?>">
                                <i class="fab fa-google"></i> Setup OAuth
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Kelola Pengguna (Admin Only) -->
                <div class="settings-section">
                    <h2><i class="fas fa-users-cog"></i> Kelola Pengguna</h2>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Buat Akun Baru</h3>
                            <p>Tambahkan pengguna baru ke sistem (operator atau admin)</p>
                        </div>
                        <div class="setting-action">
                            <button type="button" class="btn btn-primary" onclick="openCreateUserModal()" style="background: #10b981; border-color: #10b981;">
                                <i class="fas fa-user-plus"></i> Buat Akun
                            </button>
                        </div>
                    </div>

                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Daftar Pengguna</h3>
                            <p>Lihat semua akun yang terdaftar di sistem</p>
                        </div>
                        <div class="setting-action">
                            <button type="button" class="btn btn-outline" onclick="toggleUserList()" id="btnToggleUsers">
                                <i class="fas fa-list"></i> Tampilkan
                            </button>
                        </div>
                    </div>
                    
                    <!-- User List Table (hidden by default) -->
                    <div id="userListTable" style="display: none; padding-top: 1rem;">
                        <div class="user-table-wrap">
                        <table class="user-table">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th style="text-align: center;">Status</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="userListBody">
                                <tr><td colspan="5" style="padding: 1rem; text-align: center; color: #94a3b8;">Memuat...</td></tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tentang Aplikasi -->
                <div class="settings-section">
                    <h2><i class="fas fa-info-circle"></i> Tentang Aplikasi</h2>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Nama Aplikasi</h3>
                            <p><?php echo APP_NAME; ?></p>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Versi</h3>
                            <p><?php echo APP_VERSION; ?></p>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>GitHub Repository</h3>
                            <p><a href="https://github.com/LTZ24/FileManager-62.git" target="_blank" rel="noopener" style="color: #667eea; text-decoration: none;"><i class="fab fa-github"></i> LTZ24/FileManager-62</a></p>
                        </div>
                        <div class="setting-action">
                            <a href="https://github.com/LTZ24/FileManager-62" target="_blank" rel="noopener" class="btn btn-sm" style="background: #24292e; color: #fff; padding: 0.4rem 1rem; border-radius: 6px; font-size: 0.85rem; text-decoration: none;"><i class="fab fa-github"></i> View</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>

    <?php if ($isAdmin): ?>
    <!-- ===== Unified Storage Settings Modal ===== -->
    <div class="storage-modal-overlay" id="storageModal">
        <div class="storage-modal">
            <div class="storage-modal-header">
                <h3><i class="fas fa-cloud"></i> Konfigurasi Penyimpanan</h3>
                <div class="storage-modal-header-actions">
                    <button type="button" class="storage-modal-close" onclick="closeStorageModal()">&times;</button>
                </div>
            </div>
            <form id="storageForm" onsubmit="saveAllStorageSettings(event)">
                <div class="modal-tabs">
                    <button type="button" class="modal-tab active" onclick="switchTab('tabApi', this)">
                        <i class="fas fa-key"></i> API
                    </button>
                    <button type="button" class="modal-tab" onclick="switchTab('tabFolders', this)">
                        <i class="fas fa-folder"></i> Folder Drive
                    </button>
                    <button type="button" class="modal-tab" onclick="switchTab('tabSheets', this)">
                        <i class="fas fa-table"></i> Sheets
                    </button>
                </div>
                <div class="storage-modal-body">
                    <!-- Tab: API Config -->
                    <div class="tab-panel active" id="tabApi">
                        <div class="storage-form-group">
                            <label>
                                <i class="fas fa-id-card"></i> Google Client ID
                                <span class="field-status empty" id="statusClientId">Belum diisi</span>
                            </label>
                            <input type="text" name="client_id" id="fieldClientId" placeholder="Masukkan Client ID baru (kosongkan jika tidak diubah)">
                            <div class="storage-form-hint">Kosongkan jika tidak ingin mengubah nilai yang tersimpan</div>
                        </div>
                        <div class="storage-form-group">
                            <label>
                                <i class="fas fa-key"></i> Google Client Secret
                                <span class="field-status empty" id="statusClientSecret">Belum diisi</span>
                            </label>
                            <input type="password" name="client_secret" id="fieldClientSecret" placeholder="Masukkan Client Secret baru (kosongkan jika tidak diubah)">
                            <div class="storage-form-hint">Kosongkan jika tidak ingin mengubah nilai yang tersimpan</div>
                        </div>
                    </div>

                    <!-- Tab: Folders -->
                    <div class="tab-panel" id="tabFolders">
                        <div class="storage-form-group">
                            <label>
                                <i class="fas fa-users" style="color: #3b82f6;"></i> Folder Kesiswaan
                                <span class="field-status empty" id="statusFolderKesiswaan">Belum diisi</span>
                            </label>
                            <input type="text" name="folder_kesiswaan" id="fieldFolderKesiswaan" placeholder="Paste link folder atau ID baru">
                            <div class="storage-form-hint">ID otomatis diekstrak dari link. Kosongkan jika tidak diubah.</div>
                        </div>
                        <div class="storage-form-group">
                            <label>
                                <i class="fas fa-book" style="color: #10b981;"></i> Folder Kurikulum
                                <span class="field-status empty" id="statusFolderKurikulum">Belum diisi</span>
                            </label>
                            <input type="text" name="folder_kurikulum" id="fieldFolderKurikulum" placeholder="Paste link folder atau ID baru">
                        </div>
                        <div class="storage-form-group">
                            <label>
                                <i class="fas fa-building" style="color: #f59e0b;"></i> Folder Sapras & Humas
                                <span class="field-status empty" id="statusFolderSapras">Belum diisi</span>
                            </label>
                            <input type="text" name="folder_sapras" id="fieldFolderSapras" placeholder="Paste link folder atau ID baru">
                        </div>
                        <div class="storage-form-group">
                            <label>
                                <i class="fas fa-users-cog" style="color: #8b5cf6;"></i> Folder Tata Usaha
                                <span class="field-status empty" id="statusFolderTataUsaha">Belum diisi</span>
                            </label>
                            <input type="text" name="folder_tata_usaha" id="fieldFolderTataUsaha" placeholder="Paste link folder atau ID baru">
                        </div>
                    </div>

                    <!-- Tab: Sheets -->
                    <div class="tab-panel" id="tabSheets">
                        <div class="storage-form-group">
                            <label>
                                <i class="fas fa-users" style="color: #3b82f6;"></i> Spreadsheet Kesiswaan
                                <span class="field-status empty" id="statusSheetKesiswaan">Belum diisi</span>
                            </label>
                            <input type="text" name="sheets_kesiswaan" id="fieldSheetKesiswaan" placeholder="Paste link spreadsheet atau ID baru">
                            <div class="storage-form-hint">ID otomatis diekstrak dari link. Kosongkan jika tidak diubah.</div>
                        </div>
                        <div class="storage-form-group">
                            <label>
                                <i class="fas fa-book" style="color: #10b981;"></i> Spreadsheet Kurikulum
                                <span class="field-status empty" id="statusSheetKurikulum">Belum diisi</span>
                            </label>
                            <input type="text" name="sheets_kurikulum" id="fieldSheetKurikulum" placeholder="Paste link spreadsheet atau ID baru">
                        </div>
                        <div class="storage-form-group">
                            <label>
                                <i class="fas fa-building" style="color: #f59e0b;"></i> Spreadsheet Sapras & Humas
                                <span class="field-status empty" id="statusSheetSapras">Belum diisi</span>
                            </label>
                            <input type="text" name="sheets_sapras" id="fieldSheetSapras" placeholder="Paste link spreadsheet atau ID baru">
                        </div>
                        <div class="storage-form-group">
                            <label>
                                <i class="fas fa-users-cog" style="color: #8b5cf6;"></i> Spreadsheet Tata Usaha
                                <span class="field-status empty" id="statusSheetTataUsaha">Belum diisi</span>
                            </label>
                            <input type="text" name="sheets_tata_usaha" id="fieldSheetTataUsaha" placeholder="Paste link spreadsheet atau ID baru">
                        </div>
                    </div>
                </div>

                <!-- Admin Password Confirmation -->
                <div class="password-confirm-section">
                    <label><i class="fas fa-lock"></i> Password Admin (wajib untuk menyimpan)</label>
                    <input type="password" name="admin_password" id="storageAdminPassword" placeholder="Masukkan password akun admin Anda" required>
                    <div class="hint">Diperlukan untuk mengonfirmasi perubahan pengaturan</div>
                </div>

                <div class="storage-modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeStorageModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveStorage"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== Create User Modal ===== -->
    <div class="storage-modal-overlay" id="createUserModal">
        <div class="storage-modal">
            <div class="storage-modal-header create-user-modal-header">
                <h3><i class="fas fa-user-plus"></i> Buat Akun Baru</h3>
                <div class="storage-modal-header-actions">
                    <button type="button" class="storage-modal-close" onclick="closeCreateUserModal()">&times;</button>
                </div>
            </div>
            <form id="createUserForm" onsubmit="saveNewUser(event)">
                <div class="storage-modal-body" style="overflow-y: auto; max-height: 50vh;">
                    <div style="padding: 1.5rem;">
                        <div class="storage-form-group">
                            <label><i class="fas fa-user"></i> Username</label>
                            <input type="text" name="new_username" id="newUsername" placeholder="Username (huruf kecil, tanpa spasi)" required pattern="[a-z0-9_]+" minlength="3" maxlength="50">
                            <div class="storage-form-hint">Minimal 3 karakter, hanya huruf kecil, angka, dan underscore</div>
                        </div>
                        <div class="storage-form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="new_email" id="newEmail" placeholder="Email pengguna (opsional)">
                        </div>
                        <input type="hidden" name="new_role" value="staff/guru">
                        <div class="storage-form-group">
                            <label><i class="fas fa-lock"></i> Password Akun Baru</label>
                            <input type="password" name="new_password" id="newPassword" placeholder="Password untuk akun baru" required minlength="6">
                            <div class="storage-form-hint">Minimal 6 karakter</div>
                        </div>
                        <div class="storage-form-group">
                            <label><i class="fas fa-lock"></i> Konfirmasi Password</label>
                            <input type="password" name="new_password_confirm" id="newPasswordConfirm" placeholder="Ketik ulang password" required>
                        </div>
                    </div>
                </div>

                <!-- Admin Password Confirmation -->
                <div class="password-confirm-section">
                    <label><i class="fas fa-lock"></i> Password Admin Anda (konfirmasi)</label>
                    <input type="password" name="admin_password" id="createUserAdminPassword" placeholder="Masukkan password akun admin Anda" required>
                    <div class="hint">Diperlukan untuk mengonfirmasi pembuatan akun</div>
                </div>

                <div class="storage-modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeCreateUserModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: #10b981; border-color: #10b981;" id="btnCreateUser"><i class="fas fa-user-plus"></i> Buat Akun</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== Edit User Modal ===== -->
    <div class="storage-modal-overlay" id="editUserModal">
        <div class="storage-modal">
            <div class="storage-modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <h3><i class="fas fa-user-edit"></i> Edit Akun</h3>
                <div class="storage-modal-header-actions">
                    <button type="button" class="storage-modal-close" onclick="closeEditUserModal()">&times;</button>
                </div>
            </div>
            <form id="editUserForm" onsubmit="saveEditUser(event)">
                <input type="hidden" name="edit_user_id" id="editUserId">
                <div class="storage-modal-body" style="overflow-y: auto; max-height: 50vh;">
                    <div style="padding: 1.5rem;">
                        <div class="storage-form-group">
                            <label><i class="fas fa-user"></i> Username</label>
                            <input type="text" id="editUsername" disabled style="background: #e2e8f0; cursor: not-allowed;">
                            <div class="storage-form-hint">Username tidak dapat diubah</div>
                        </div>
                        <div class="storage-form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="edit_email" id="editEmail" placeholder="Email pengguna (opsional)">
                        </div>
                        <div class="storage-form-group">
                            <label><i class="fas fa-lock"></i> Password Baru</label>
                            <input type="password" name="edit_password" id="editPassword" placeholder="Kosongkan jika tidak diubah" minlength="6">
                            <div class="storage-form-hint">Kosongkan jika tidak ingin mengubah password</div>
                        </div>
                        <div class="storage-form-group" id="editIsActiveGroup">
                            <label><i class="fas fa-toggle-on"></i> Status Akun</label>
                            <select name="edit_is_active" id="editIsActive">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Admin Password Confirmation -->
                <div class="password-confirm-section">
                    <label><i class="fas fa-lock"></i> Password Admin Anda (konfirmasi)</label>
                    <input type="password" name="admin_password" id="editUserAdminPassword" placeholder="Masukkan password akun admin Anda" required>
                    <div class="hint">Diperlukan untuk mengonfirmasi perubahan akun</div>
                </div>

                <div class="storage-modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeEditUserModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: #f59e0b; border-color: #f59e0b;" id="btnEditUser"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Popup -->
    <div class="info-popup-overlay" id="infoPopup">
        <div class="info-popup">
            <div class="info-popup-header">
                <h4><i class="fas fa-info-circle"></i> <span id="infoPopupTitle">Informasi</span></h4>
                <button type="button" class="info-popup-close" onclick="closeInfoPopup()">&times;</button>
            </div>
            <div class="info-popup-body" id="infoPopupBody"></div>
        </div>
    </div>
    
    <script>
        const STORAGE_API_URL = '<?php echo BASE_URL; ?>/api/storage-settings';
        const USERS_API_URL = '<?php echo BASE_URL; ?>/api/users';
        let storageConfig = null;
        
        document.addEventListener('DOMContentLoaded', loadStorageConfig);

        // ===== Tab Switching =====
        function switchTab(tabId, tabBtn) {
            document.querySelectorAll('#storageModal .tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('#storageModal .modal-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            tabBtn.classList.add('active');
        }
        
        // ===== Load Storage Config =====
        async function loadStorageConfig() {
            try {
                const resp = await fetch(STORAGE_API_URL + '?action=get', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': window.APP_CSRF_TOKEN || ''
                    }
                });
                const data = await resp.json();
                if (data && data.success && data.data) {
                    storageConfig = data.data;
                    updateFieldStatuses();
                }
            } catch (e) {
                console.error('Failed to load storage config:', e);
            }
        }

        // ===== Update Status Badges (without showing IDs) =====
        function updateFieldStatuses() {
            if (!storageConfig) return;

            const setStatus = (id, hasValue) => {
                const el = document.getElementById(id);
                if (!el) return;
                if (hasValue) {
                    el.textContent = 'Tersimpan \u2713';
                    el.className = 'field-status configured';
                } else {
                    el.textContent = 'Belum diisi';
                    el.className = 'field-status empty';
                }
            };

            // API
            setStatus('statusClientId', !!storageConfig.google_api?.client_id);
            setStatus('statusClientSecret', !!storageConfig.google_api?.client_secret_masked);

            // Folders
            setStatus('statusFolderKesiswaan', !!storageConfig.drive_folders?.kesiswaan);
            setStatus('statusFolderKurikulum', !!storageConfig.drive_folders?.kurikulum);
            setStatus('statusFolderSapras', !!storageConfig.drive_folders?.sapras);
            setStatus('statusFolderTataUsaha', !!storageConfig.drive_folders?.tata_usaha);

            // Sheets
            setStatus('statusSheetKesiswaan', !!storageConfig.sheets?.kesiswaan);
            setStatus('statusSheetKurikulum', !!storageConfig.sheets?.kurikulum);
            setStatus('statusSheetSapras', !!storageConfig.sheets?.sapras);
            setStatus('statusSheetTataUsaha', !!storageConfig.sheets?.tata_usaha);
        }

        // ===== Storage Modal Open / Close =====
        function openStorageModal() {
            document.getElementById('storageForm').reset();
            switchTab('tabApi', document.querySelector('#storageModal .modal-tab'));
            updateFieldStatuses();

            const modal = document.getElementById('storageModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeStorageModal() {
            document.getElementById('storageModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        // ===== Save All Storage Settings =====
        async function saveAllStorageSettings(event) {
            event.preventDefault();

            const adminPw = document.getElementById('storageAdminPassword').value;
            if (!adminPw) {
                showToast('Password admin wajib diisi.', 'error');
                return;
            }

            const form = event.target;
            const fd = new FormData(form);
            fd.append('csrf_token', window.APP_CSRF_TOKEN || '');

            const btn = document.getElementById('btnSaveStorage');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            try {
                const resp = await fetch(STORAGE_API_URL + '?action=update_all', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': window.APP_CSRF_TOKEN || ''
                    },
                    body: fd
                });
                
                const data = await resp.json();
                
                if (data && data.success) {
                    closeStorageModal();
                    showToast(data.message || 'Pengaturan berhasil disimpan!', 'success');
                    await loadStorageConfig();
                } else {
                    showToast(data.message || 'Gagal menyimpan pengaturan.', 'error');
                }
            } catch (e) {
                console.error('Save error:', e);
                showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Simpan';
            }
        }

        // ===== Create User Modal =====
        function openCreateUserModal() {
            document.getElementById('createUserForm').reset();
            document.getElementById('createUserModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCreateUserModal() {
            document.getElementById('createUserModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        async function saveNewUser(event) {
            event.preventDefault();

            const pw = document.getElementById('newPassword').value;
            const pwConfirm = document.getElementById('newPasswordConfirm').value;
            if (pw !== pwConfirm) {
                showToast('Password akun baru tidak cocok.', 'error');
                return;
            }

            const adminPw = document.getElementById('createUserAdminPassword').value;
            if (!adminPw) {
                showToast('Password admin wajib diisi.', 'error');
                return;
            }

            const form = event.target;
            const fd = new FormData(form);
            fd.append('csrf_token', window.APP_CSRF_TOKEN || '');
            fd.append('action', 'create');

            const btn = document.getElementById('btnCreateUser');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membuat...';

            try {
                const resp = await fetch(USERS_API_URL, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': window.APP_CSRF_TOKEN || ''
                    },
                    body: fd
                });
                
                const data = await resp.json();
                
                if (data && data.success) {
                    closeCreateUserModal();
                    showToast(data.message || 'Akun berhasil dibuat!', 'success');
                    // Refresh user list if it's visible
                    if (document.getElementById('userListTable').style.display !== 'none') {
                        userListLoaded = false;
                        loadUserList();
                    }
                } else {
                    showToast(data.message || 'Gagal membuat akun.', 'error');
                }
            } catch (e) {
                console.error('Create user error:', e);
                showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Buat Akun';
            }
        }

        // ===== Edit User Modal =====
        function openEditUserModal(user) {
            document.getElementById('editUserForm').reset();
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editEmail').value = user.email || '';
            document.getElementById('editIsActive').value = user.is_active ? '1' : '0';
            document.getElementById('editPassword').value = '';

            // Admin status cannot be changed
            const statusGroup = document.getElementById('editIsActiveGroup');
            if (user.role === 'admin') {
                statusGroup.style.display = 'none';
            } else {
                statusGroup.style.display = '';
            }

            document.getElementById('editUserModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        async function saveEditUser(event) {
            event.preventDefault();

            const adminPw = document.getElementById('editUserAdminPassword').value;
            if (!adminPw) {
                showToast('Password admin wajib diisi.', 'error');
                return;
            }

            const form = event.target;
            const fd = new FormData(form);
            fd.append('csrf_token', window.APP_CSRF_TOKEN || '');
            fd.append('action', 'update');

            const btn = document.getElementById('btnEditUser');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            try {
                const resp = await fetch(USERS_API_URL, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': window.APP_CSRF_TOKEN || ''
                    },
                    body: fd
                });
                
                const data = await resp.json();
                
                if (data && data.success) {
                    closeEditUserModal();
                    showToast(data.message || 'Akun berhasil diperbarui!', 'success');
                    userListLoaded = false;
                    loadUserList();
                } else {
                    showToast(data.message || 'Gagal memperbarui akun.', 'error');
                }
            } catch (e) {
                console.error('Edit user error:', e);
                showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Simpan';
            }
        }

        // ===== User List =====
        let userListLoaded = false;

        function toggleUserList() {
            const table = document.getElementById('userListTable');
            const btn = document.getElementById('btnToggleUsers');
            const isVisible = table.style.display !== 'none';
            
            if (isVisible) {
                table.style.display = 'none';
                btn.innerHTML = '<i class="fas fa-list"></i> Tampilkan';
            } else {
                table.style.display = 'block';
                btn.innerHTML = '<i class="fas fa-eye-slash"></i> Sembunyikan';
                if (!userListLoaded) {
                    loadUserList();
                }
            }
        }

        async function loadUserList() {
            try {
                const resp = await fetch(USERS_API_URL + '?action=list', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': window.APP_CSRF_TOKEN || ''
                    }
                });
                const data = await resp.json();
                
                if (data && data.success && data.data) {
                    const tbody = document.getElementById('userListBody');
                    if (data.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" style="padding: 1rem; text-align: center; color: #94a3b8;">Tidak ada pengguna.</td></tr>';
                        return;
                    }
                    
                    tbody.innerHTML = data.data.map(u => {
                        const jsonStr = JSON.stringify(u).replace(/'/g, "&#39;");
                        let actions = `<button type="button" class="btn btn-outline" style="min-width:auto; padding:4px 12px; font-size:0.75rem;" onclick='openEditUserModal(${jsonStr})'><i class="fas fa-edit"></i></button>`;
                        // Show delete button only for inactive non-admin users
                        if (!u.is_active && u.role !== 'admin') {
                            actions += ` <button type="button" class="btn btn-outline" style="min-width:auto; padding:4px 12px; font-size:0.75rem; color:#ef4444; border-color:#ef4444;" onclick="deleteUser(${u.id}, '${escHtml(u.username)}')" title="Hapus akun"><i class="fas fa-trash-alt"></i></button>`;
                        }
                        return `
                        <tr>
                            <td style="font-weight: 500;">${escHtml(u.username)}</td>
                            <td>${escHtml(u.email || '-')}</td>
                            <td>
                                <span class="role-badge ${u.role === 'admin' ? 'admin' : 'operator'}">${escHtml(u.role)}</span>
                            </td>
                            <td style="text-align: center;">
                                <span class="status-dot ${u.is_active ? 'active' : 'inactive'}"></span>
                            </td>
                            <td style="text-align: center;">
                                ${actions}
                            </td>
                        </tr>`;
                    }).join('');
                    userListLoaded = true;
                }
            } catch (e) {
                console.error('Load users error:', e);
                document.getElementById('userListBody').innerHTML = '<tr><td colspan="4" style="padding: 1rem; text-align: center; color: #ef4444;">Gagal memuat data pengguna.</td></tr>';
            }
        }

        async function deleteUser(userId, username) {
            if (!confirm('Yakin ingin menghapus akun "' + username + '"? Tindakan ini tidak dapat dibatalkan.')) return;

            const adminPw = prompt('Masukkan password admin Anda untuk konfirmasi:');
            if (!adminPw) return;

            try {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('delete_user_id', userId);
                fd.append('admin_password', adminPw);
                fd.append('csrf_token', window.APP_CSRF_TOKEN || '');

                const resp = await fetch(USERS_API_URL, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': window.APP_CSRF_TOKEN || ''
                    },
                    body: fd
                });
                const data = await resp.json();
                if (data && data.success) {
                    showToast(data.message || 'Akun berhasil dihapus.', 'success');
                    userListLoaded = false;
                    loadUserList();
                } else {
                    showToast(data.message || 'Gagal menghapus akun.', 'error');
                }
            } catch (e) {
                console.error('Delete user error:', e);
                showToast('Terjadi kesalahan.', 'error');
            }
        }

        function escHtml(str) {
            if (!str) return '';
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        // ===== Info Popup =====
        function showInfoPopup(type) {
            const popup = document.getElementById('infoPopup');
            const title = document.getElementById('infoPopupTitle');
            const body = document.getElementById('infoPopupBody');
            
            if (type === 'api') {
                title.textContent = 'Pengaturan API Google';
                body.innerHTML = `
                    <ol>
                        <li>Buka <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                        <li>Buat project baru atau pilih project yang ada</li>
                        <li>Pergi ke <strong>APIs & Services</strong> > <strong>Credentials</strong></li>
                        <li>Buat <strong>OAuth 2.0 Client ID</strong></li>
                        <li>Salin Client ID dan Client Secret</li>
                    </ol>
                `;
            } else if (type === 'folders') {
                title.textContent = 'Cara Mendapatkan Folder ID';
                body.innerHTML = `
                    <ol>
                        <li>Buka <a href="https://drive.google.com/" target="_blank">Google Drive</a></li>
                        <li>Buka folder yang diinginkan</li>
                        <li>Salin link folder, lalu paste di kolom input</li>
                    </ol>
                    <p style="margin-top: 0.75rem; padding: 0.5rem; background: #dbeafe; border-radius: 6px; font-size: 0.75rem;">
                        <code>https://drive.google.com/drive/folders/FOLDER_ID</code>
                    </p>
                `;
            } else if (type === 'sheets') {
                title.textContent = 'Cara Mendapatkan Spreadsheet ID';
                body.innerHTML = `
                    <ol>
                        <li>Buka <a href="https://sheets.google.com/" target="_blank">Google Sheets</a></li>
                        <li>Buka spreadsheet, lalu salin link dan paste di kolom input</li>
                    </ol>
                    <p style="margin-top: 0.75rem; padding: 0.5rem; background: #dbeafe; border-radius: 6px; font-size: 0.75rem;">
                        <code>https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit</code>
                    </p>
                `;
            }
            
            popup.classList.add('active');
        }
        
        function closeInfoPopup() {
            document.getElementById('infoPopup').classList.remove('active');
        }
        
        // ===== Close modals on overlay click =====
        document.querySelectorAll('.storage-modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
        
        document.getElementById('infoPopup')?.addEventListener('click', (e) => {
            if (e.target.id === 'infoPopup') closeInfoPopup();
        });
        
        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.storage-modal-overlay.active').forEach(m => {
                    m.classList.remove('active');
                });
                closeInfoPopup();
                document.body.style.overflow = '';
            }
        });
        
        // Simple toast function (fallback)
        function showToast(message, type = 'success') {
            // Try to use the global showToast from main.js if available
            if (window._mainShowToast) {
                window._mainShowToast(message, type);
                return;
            }
            
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: ${type === 'success' ? '#10b981' : '#ef4444'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                max-width: 90vw;
                font-size: 0.875rem;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
    <?php endif; ?>
    
</body>
</html>
