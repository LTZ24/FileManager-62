<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';

requireLogin();

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';

$categories = getFormCategories();
// Data loaded via AJAX with skeleton loading
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Forms - <?php echo APP_NAME; ?></title>
    
    <!-- Resource Hints for Faster Loading -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://www.googleapis.com" crossorigin>
    
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/smk62.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/ajax.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    <style>
        .forms-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem;
        }
        
        .forms-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .forms-header h2 {
            font-size: 1.25rem;
            color: var(--dark-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .category-filter {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .category-filter-dropdown {
            display: none;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .category-filter-dropdown select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            font-size: 0.875rem;
            background: white;
            color: var(--dark-color);
            cursor: pointer;
        }
        
        .category-btn {
            padding: 0.5rem 0.875rem;
            border: 1px solid var(--border-color);
            background: white;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.15s ease;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            color: var(--dark-color);
            font-size: 0.875rem;
        }
        
        .category-btn:hover {
            background: #f8fafc;
        }
        
        .category-btn.active {
            color: white;
            border-color: transparent;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        thead {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }
        
        thead th {
            padding: 0.625rem 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }
        
        tbody tr:hover {
            background: #f8fafc;
        }
        
        tbody td {
            padding: 0.625rem 0.75rem;
            vertical-align: middle;
        }
        
        .file-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .file-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            border-radius: 0.375rem;
            flex-shrink: 0;
        }
        
        .file-icon i {
            font-size: 1rem;
            color: var(--primary-color);
        }
        
        .file-details {
            min-width: 0;
            flex: 1;
        }
        
        .file-name {
            font-weight: 500;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        
        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.125rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 3rem;
            opacity: 0.5;
            margin-bottom: 1rem;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            border-radius: 0.75rem;
            max-width: 800px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.125rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .close {
            color: #64748b;
            font-size: 1.75rem;
            font-weight: 400;
            line-height: 1;
            border: none;
            background: none;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        
        .close:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .forms-container {
                padding: 0.75rem;
            }
            
            .category-filter {
                display: none;
            }
            
            .category-filter-dropdown {
                display: block;
            }
            
            table {
                font-size: 0.8125rem;
            }
            
            thead th,
            tbody td {
                padding: 0.5rem;
            }
            
            .modal-content {
                margin: 2% auto;
                width: 95%;
                max-width: 95%;
                max-height: 90vh;
            }
            
            .modal-body {
                padding: 1rem;
                max-height: calc(90vh - 120px);
                overflow-y: auto;
            }
        }
        
        /* Skeleton Loading */
        .skeleton-loader { overflow-x: auto; }
        .skeleton-loader table { width: 100%; border-collapse: collapse; }
        .skeleton-row td { padding: 0.625rem 0.75rem; border-bottom: 1px solid #f1f5f9; }
        .skeleton-cell { display: flex; align-items: center; gap: 0.5rem; }
        .skeleton-icon { width: 32px; height: 32px; border-radius: 0.375rem; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; flex-shrink: 0; }
        .skeleton-text { flex: 1; display: flex; flex-direction: column; gap: 0.375rem; }
        .skeleton-line { height: 12px; border-radius: 0.25rem; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
        .skeleton-line.short { width: 40%; }
        .skeleton-line.medium { width: 55%; }
        .skeleton-actions { display: flex; gap: 0.25rem; justify-content: center; }
        .skeleton-btn { width: 30px; height: 30px; border-radius: 0.375rem; background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <?php include __DIR__ . '/../../includes/page-navigation.php'; ?>
            
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
            
            <div class="forms-container">
                <div class="forms-header">
                    <h2><i class="fas fa-file-alt"></i> Daftar Forms (<span id="formsCount">...</span>)</h2>
                    <button type="button" class="btn btn-primary" style="padding: 0.5rem 0.875rem; font-size: 0.875rem;" onclick="openAddFormModal()">
                        <i class="fas fa-plus"></i> Tambah Form
                    </button>
                </div>
                
                <!-- Mobile Category Dropdown -->
                <div class="category-filter-dropdown">
                    <select onchange="window.location.href=this.value">
                        <option value="./" <?php echo empty($selectedCategory) ? 'selected' : ''; ?>>Semua Kategori</option>
                        <?php foreach ($categories as $key => $category): ?>
                            <option value="./?category=<?php echo $key; ?>" 
                                    <?php echo $selectedCategory === $key ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Desktop Category Buttons -->
                <div class="category-filter">
                    <a href="./" 
                       class="category-btn <?php echo empty($selectedCategory) ? 'active' : ''; ?>"
                       style="<?php echo empty($selectedCategory) ? 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);' : ''; ?>">
                        <i class="fas fa-th"></i>
                        Semua
                    </a>
                    <?php foreach ($categories as $key => $category): ?>
                        <a href="./?category=<?php echo $key; ?>" 
                           class="category-btn <?php echo $selectedCategory === $key ? 'active' : ''; ?>"
                           style="<?php echo $selectedCategory === $key ? 'background: ' . $category['color'] . ';' : ''; ?>">
                            <i class="fas <?php echo $category['icon']; ?>"></i>
                            <?php echo $category['name']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Skeleton Loader -->
                <div id="skeletonForms" class="skeleton-loader">
                    <table>
                        <thead><tr><th style="width: 35%;">Judul</th><th style="width: 25%;">URL</th><th style="width: 12%;">Kategori</th><th style="width: 28%; text-align: center;">Aksi</th></tr></thead>
                        <tbody>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-line short"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-line short"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-line short"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-line short"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-line short"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- Data Table -->
                <div id="formsTableWrapper" style="display: none;">
                    <div id="formsEmptyState" class="empty-state" style="display: none;"><i class="fas fa-inbox"></i><p>Belum ada form. Klik tombol "Tambah Form" untuk menambahkan.</p></div>
                    <div style="overflow-x: auto;">
                        <table id="forms-table">
                            <thead><tr><th style="width: 35%;">Judul</th><th style="width: 25%;">URL</th><th style="width: 12%;">Kategori</th><th style="width: 28%; text-align: center;">Aksi</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../../includes/footer.php'; ?>
        </div>
    </div>

    <!-- Add Form Modal -->
    <div id="addFormModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Tambah Form</h3>
                <button class="close" onclick="closeAddFormModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div id="addFormAlert" style="display: none; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;"></div>
                <form id="addFormForm" onsubmit="submitAddForm(event)">
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="addFormTitle" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.875rem;">
                            <i class="fas fa-heading"></i> Judul Form
                        </label>
                        <input type="text" id="addFormTitle" name="title" required
                               style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s; box-sizing: border-box;"
                               onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                               onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="addFormUrl" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.875rem;">
                            <i class="fas fa-link"></i> URL
                        </label>
                        <input type="url" id="addFormUrl" name="url" required
                               style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s; box-sizing: border-box;"
                               onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                               onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="addFormCategory" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.875rem;">
                            <i class="fas fa-layer-group"></i> Kategori
                        </label>
                        <select id="addFormCategory" name="category" required
                                style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s; box-sizing: border-box; background: white;"
                                onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                                onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                            <option value="" disabled>Pilih Kategori</option>
                            <?php foreach ($categories as $key => $category): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($selectedCategory === $key) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                        <button id="addFormSubmitBtn" type="submit" class="btn btn-primary" style="flex: 1; padding: 0.75rem 1.5rem; font-size: 0.875rem; font-weight: 600;">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <button type="button" onclick="closeAddFormModal()" class="btn btn-secondary" style="flex: 1; padding: 0.75rem 1.5rem; font-size: 0.875rem; font-weight: 600;">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Form Modal -->
    <div id="editFormModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Form</h3>
                <button class="close" onclick="closeEditFormModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 2rem;">
                <div id="editFormAlert" style="display: none; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;"></div>
                <form id="editFormForm" onsubmit="submitEditForm(event)">
                    <input type="hidden" id="editFormId" name="id">
                    <input type="hidden" id="editFormCategory" name="category">
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="editFormTitle" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.875rem;">
                            <i class="fas fa-heading"></i> Judul Form
                        </label>
                        <input type="text" 
                               id="editFormTitle" 
                               name="title" 
                               required
                               style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s; box-sizing: border-box;"
                               onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                               onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="editFormUrl" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #334155; font-size: 0.875rem;">
                            <i class="fas fa-link"></i> URL
                        </label>
                        <input type="url" 
                               id="editFormUrl" 
                               name="url" 
                               required
                               style="width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s; box-sizing: border-box;"
                               onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'"
                               onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'">
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; margin-top: 2rem;">
                        <button type="submit" 
                                class="btn btn-primary" 
                                style="flex: 1; padding: 0.75rem 1.5rem; font-size: 0.875rem; font-weight: 600;">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <button type="button" 
                                onclick="closeEditFormModal()" 
                                class="btn btn-secondary" 
                                style="flex: 1; padding: 0.75rem 1.5rem; font-size: 0.875rem; font-weight: 600;">
                            <i class="fas fa-times"></i> Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Detail Form Modal -->
    <div id="detailFormModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Detail Form</h3>
                <button class="close" onclick="closeDetailFormModal()">&times;</button>
            </div>
            <div class="modal-body" id="detailFormContent" style="padding: 1.5rem;">
                <!-- Detail content will be inserted here -->
            </div>
        </div>
    </div>
    
    <script>
        // Store forms data for JavaScript access
        let formsData = [];
        const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
        const FORMS_DATA_URL = BASE_URL + '/api/forms-data';
    </script>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/table-pagination.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script>
        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getSelectedCategoryFromUrl() {
            const params = new URLSearchParams(window.location.search);
            return params.get('category') || '';
        }

        async function fetchJson(url, options = {}) {
            if (options && options.body && (options.body instanceof FormData)) {
                if (window.APP_CSRF_TOKEN && !options.body.has('csrf_token')) {
                    options.body.append('csrf_token', window.APP_CSRF_TOKEN);
                }
            }

            const res = await fetch(url, Object.assign({
                headers: Object.assign({
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': (window.APP_CSRF_TOKEN || '')
                }, options.headers || {})
            }, options));

            const text = await res.text();
            let data;
            try {
                data = text ? JSON.parse(text) : {};
            } catch (e) {
                const snippet = (text || '').slice(0, 180).replace(/\s+/g, ' ').trim();
                throw new Error(`Response server bukan JSON (${res.status}). Awal respons: ${snippet}`);
            }

            if (!res.ok || (data && data.success === false)) {
                throw new Error((data && data.message) ? data.message : 'Network error');
            }

            return data;
        }

        function renderFormsTable() {
            const tbody = document.querySelector('#forms-table tbody');
            const tableEl = document.getElementById('forms-table');
            const emptyState = document.getElementById('formsEmptyState');
            const countEl = document.getElementById('formsCount');
            if (!tbody) return;

            if (countEl) countEl.textContent = (formsData || []).length;

            // Handle empty state
            if (!formsData || formsData.length === 0) {
                if (tableEl) tableEl.style.display = 'none';
                if (emptyState) emptyState.style.display = '';
                return;
            } else {
                if (tableEl) tableEl.style.display = '';
                if (emptyState) emptyState.style.display = 'none';
            }

            const rowsHtml = (formsData || []).map((form, index) => {
                const title = escapeHtml(form.title);
                const url = String(form.url || '');
                const urlDisplay = escapeHtml(url.length > 40 ? (url.slice(0, 40) + '...') : url);
                const safeUrl = escapeHtml(url);

                const badge = form.category_name
                    ? `<span class=\"category-badge\" style=\"background: ${escapeHtml(form.category_color)}\">
                            <i class=\"fas ${escapeHtml((form.category_icon || '').toString())}\"></i>
                            ${escapeHtml(form.category_name)}
                       </span>`
                    : `<span style=\"color: #64748b; font-size: 0.8125rem;\">-</span>`;

                return `
                    <tr>
                        <td>
                            <div class=\"file-info\">
                                <div class=\"file-icon\"><i class=\"fas fa-file-alt\"></i></div>
                                <div class=\"file-details\"><span class=\"file-name\">${title}</span></div>
                            </div>
                        </td>
                        <td style=\"color: #64748b; font-size: 0.8125rem;\">
                            <a href=\"${safeUrl}\" target=\"_blank\" style=\"color: var(--primary-color); text-decoration: none;\">
                                ${urlDisplay}
                                <i class=\"fas fa-external-link-alt\" style=\"font-size: 0.75rem; margin-left: 0.25rem;\"></i>
                            </a>
                        </td>
                        <td>${badge}</td>
                        <td style=\"text-align: center;\">
                            <div class=\"btn-group\" style=\"display: inline-flex; gap: 0.25rem;\">
                                <button onclick=\"viewFormDetail(${index})\" class=\"btn btn-sm btn-info\" title=\"Detail\" style=\"padding: 0.375rem 0.625rem; font-size: 0.8125rem;\"><i class=\"fas fa-info-circle\"></i></button>
                                <button onclick=\"editForm(${index})\" class=\"btn btn-sm btn-warning\" title=\"Edit\" style=\"padding: 0.375rem 0.625rem; font-size: 0.8125rem;\"><i class=\"fas fa-edit\"></i></button>
                                <button onclick=\"deleteForm(${index})\" class=\"btn btn-sm btn-danger\" title=\"Hapus\" style=\"padding: 0.375rem 0.625rem; font-size: 0.8125rem;\"><i class=\"fas fa-trash\"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            tbody.innerHTML = rowsHtml;

            const inst = window.tablePaginationInstances && window.tablePaginationInstances['forms-table'];
            if (inst && typeof inst.refresh === 'function') {
                inst.refresh();
            }
        }

        async function reloadFormsTable() {
            const skeleton = document.getElementById('skeletonForms');
            const wrapper = document.getElementById('formsTableWrapper');
            const emptyState = document.getElementById('formsEmptyState');
            const tableEl = document.getElementById('forms-table');

            try {
                const category = getSelectedCategoryFromUrl();
                const url = new URL(FORMS_DATA_URL, window.location.origin);
                if (category) url.searchParams.set('category', category);
                url.searchParams.set('ajax', '1');
                url.searchParams.set('_t', Date.now());

                const data = await fetchJson(url.toString());
                formsData = (data && data.data && data.data.forms) ? data.data.forms : [];
                renderFormsTable();
            } catch (e) {
                console.error('Failed to load forms:', e);
                if (emptyState) {
                    emptyState.innerHTML = '<i class="fas fa-exclamation-triangle"></i><h3>Gagal Memuat Data</h3><p>Terjadi kesalahan saat memuat form. <a href="javascript:void(0)" onclick="reloadFormsTable()">Coba lagi</a></p>';
                    emptyState.style.display = '';
                }
                if (tableEl) tableEl.style.display = 'none';
                const countEl = document.getElementById('formsCount');
                if (countEl) countEl.textContent = '!';
            } finally {
                if (skeleton) skeleton.style.display = 'none';
                if (wrapper) wrapper.style.display = '';
            }
        }

        function openAddFormModal() {
            document.getElementById('addFormModal').style.display = 'block';
            document.getElementById('addFormAlert').style.display = 'none';
            const cat = getSelectedCategoryFromUrl();
            if (cat) {
                const sel = document.getElementById('addFormCategory');
                if (sel) sel.value = cat;
            }
        }

        function closeAddFormModal() {
            document.getElementById('addFormModal').style.display = 'none';
            const f = document.getElementById('addFormForm');
            if (f) f.reset();
            const a = document.getElementById('addFormAlert');
            if (a) a.style.display = 'none';
        }

        function showInlineAlert(el, message, ok) {
            el.style.display = 'block';
            el.style.background = ok ? '#d1fae5' : '#fee2e2';
            el.style.color = ok ? '#065f46' : '#991b1b';
            el.style.border = ok ? '1px solid #a7f3d0' : '1px solid #fecaca';
            el.innerHTML = (ok ? '<i class="fas fa-check-circle"></i> ' : '<i class="fas fa-exclamation-circle"></i> ') + escapeHtml(message);
        }

        function submitAddForm(event) {
            event.preventDefault();

            const form = document.getElementById('addFormForm');
            const alertDiv = document.getElementById('addFormAlert');
            const submitBtn = document.getElementById('addFormSubmitBtn');
            const originalBtnText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            const formData = new FormData(form);

            fetchJson(BASE_URL + '/pages/forms/add?ajax=1', {
                method: 'POST',
                body: formData
            })
            .then(async (data) => {
                showInlineAlert(alertDiv, data.message || 'Form berhasil ditambahkan!', true);
                setTimeout(async () => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                    closeAddFormModal();
                    try {
                        await reloadFormsTable();
                    } catch (e) {
                        window.location.reload();
                    }
                }, 600);
            })
            .catch((error) => {
                showInlineAlert(alertDiv, error.message || 'Gagal menambahkan form', false);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        }

        // Edit Form Modal
        function editForm(index) {
            const form = formsData[index];
            document.getElementById('editFormId').value = form.id;
            document.getElementById('editFormCategory').value = form.category;
            document.getElementById('editFormTitle').value = form.title;
            document.getElementById('editFormUrl').value = form.url;
            document.getElementById('editFormAlert').style.display = 'none';
            document.getElementById('editFormModal').style.display = 'block';
        }
        
        function closeEditFormModal() {
            document.getElementById('editFormModal').style.display = 'none';
            document.getElementById('editFormForm').reset();
        }
        
        function submitEditForm(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const alertDiv = document.getElementById('editFormAlert');
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            fetchJson(BASE_URL + '/pages/forms/update?ajax=1', {
                method: 'POST',
                body: formData
            })
            .then((data) => {
                alertDiv.style.display = 'block';
                alertDiv.style.background = '#d1fae5';
                alertDiv.style.color = '#065f46';
                alertDiv.style.border = '1px solid #a7f3d0';
                alertDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + escapeHtml((data && data.message) ? data.message : 'Form berhasil diupdate');

                setTimeout(async () => {
                    closeEditFormModal();
                    try {
                        await reloadFormsTable();
                    } catch (e) {
                        window.location.reload();
                    }
                }, 600);
            })
            .catch((error) => {
                alertDiv.style.display = 'block';
                alertDiv.style.background = '#fee2e2';
                alertDiv.style.color = '#991b1b';
                alertDiv.style.border = '1px solid #fecaca';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + escapeHtml((error && error.message) ? error.message : 'Gagal menyimpan perubahan');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        }
        
        // Detail Form Modal
        function viewFormDetail(index) {
            const form = formsData[index];
            const content = `
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">Judul</label>
                    <p style="margin: 0; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">${form.title}</p>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">URL</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <p style="flex: 1; margin: 0; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0; word-break: break-all;">${form.url}</p>
                        <button onclick="copyToClipboard('${form.url.replace(/'/g, "\\'")}'); return false;" class="btn btn-sm btn-secondary" title="Copy URL">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">Kategori</label>
                    <p style="margin: 0; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                        ${form.category_name ? '<span class="category-badge" style="background: ' + form.category_color + '"><i class="fas ' + form.category_icon + '"></i> ' + form.category_name + '</span>' : '-'}
                    </p>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: #64748b; font-size: 0.875rem; display: block; margin-bottom: 0.25rem;">Tanggal Dibuat</label>
                    <p style="margin: 0; padding: 0.75rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">${form.created_at ? new Date(form.created_at).toLocaleDateString('id-ID', {day: '2-digit', month: 'long', year: 'numeric'}) : '-'}</p>
                </div>
                <div style="margin-top: 1.5rem; text-align: right;">
                    <a href="${form.url}" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-external-link-alt"></i> Buka Form
                    </a>
                </div>
            `;
            document.getElementById('detailFormContent').innerHTML = content;
            document.getElementById('detailFormModal').style.display = 'block';
        }
        
        function closeDetailFormModal() {
            document.getElementById('detailFormModal').style.display = 'none';
        }
        
        // Delete Function
        async function deleteForm(index) {
            const form = formsData[index];
            const ok = await window.showConfirmDialog({
                title: 'Hapus Form',
                message: `Apakah Anda yakin ingin menghapus form "${form.title}"?`,
                confirmText: 'Hapus',
                cancelText: 'Batal',
                danger: true
            });
            if (!ok) return;

            const fd = new FormData();
            fd.append('id', form.id);
            fd.append('category', form.category);
            fd.append('confirm', '1');

            try {
                await fetchJson(BASE_URL + '/pages/forms/delete?ajax=1', { method: 'POST', body: fd });
                await reloadFormsTable();
                if (typeof window.showToast === 'function') {
                    window.showToast('Form berhasil dihapus', 'success');
                }
            } catch (e) {
                if (typeof window.showToast === 'function') {
                    window.showToast('Gagal menghapus form: ' + e.message, 'error');
                } else {
                    alert('Gagal menghapus form: ' + e.message);
                }
            }
        }
        
        // Copy to Clipboard Function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                const notification = document.createElement('div');
                notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 1rem 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 10000; animation: slideIn 0.3s ease;';
                notification.innerHTML = '<i class="fas fa-check-circle"></i> URL berhasil disalin!';
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }, 2000);
            }).catch(function(err) {
                alert('Gagal menyalin URL: ' + err);
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Initialize Table Pagination and lazy load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize pagination with 10 rows per page
            const formsPagination = initTablePagination('forms-table', {
                rowsPerPage: 10,
                rowsPerPageOptions: [10, 25, 50, 100]
            });

            // Lazy load forms via AJAX (skeleton shown until data arrives)
            reloadFormsTable();
        });
    </script>
</body>
</html>
