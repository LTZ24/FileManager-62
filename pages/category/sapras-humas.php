<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';

requireLogin();

// Category Configuration
$categoryKey = 'sapras';
$categorySlug = basename(__FILE__, '.php');
$categoryName = 'Sapras & Humas';
$categoryIcon = 'fa-building';
$categoryColor = '#f59e0b';
$folderId = '1IB2BeH-YAMA3Nt98uHZuXwx78-JjAxT2';
$sheetId = SHEETS_SAPRAS_HUMAS;

// Get Files from Drive
function getCategoryFiles($folderId) {
    try {
        $driveService = getDriveService();
        
        $parameters = [
            'q' => "'{$folderId}' in parents and trashed=false",
            'fields' => 'files(id, name, mimeType, size, createdTime, modifiedTime, webViewLink, iconLink, thumbnailLink)',
            'orderBy' => 'modifiedTime desc',
            'pageSize' => 1000,
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true
        ];
        
        $results = $driveService->files->listFiles($parameters);
        return $results->getFiles();
    } catch (Exception $e) {
        return [];
    }
}

// Get Links from Sheets
function getCategoryLinks($sheetId, $category) {
    try {
        $sheetsService = getSheetsService();
        
        $sheetTitle = 'Links-Sapras_humas';
        $range = "'" . $sheetTitle . "'!A2:E";
        $response = $sheetsService->spreadsheets_values->get($sheetId, $range);
        $values = $response->getValues();
        
        $links = [];
        if (!empty($values)) {
            foreach ($values as $index => $row) {
                if (count($row) >= 2) {
                    $links[] = [
                        'id' => $index,
                        'title' => $row[0] ?? '',
                        'url' => $row[1] ?? '',
                        'date' => $row[2] ?? date('Y-m-d')
                    ];
                }
            }
        }
        return $links;
    } catch (Exception $e) {
        return [];
    }
}

// Get Forms from Sheets
function getCategoryForms($sheetId, $category) {
    try {
        $sheetsService = getSheetsService();
        
        $sheetTitle = 'Forms-Sapras_humas';
        $range = "'" . $sheetTitle . "'!A2:E";
        $response = $sheetsService->spreadsheets_values->get($sheetId, $range);
        $values = $response->getValues();
        
        $forms = [];
        if (!empty($values)) {
            foreach ($values as $index => $row) {
                if (count($row) >= 2) {
                    $forms[] = [
                        'id' => $index,
                        'title' => $row[0] ?? '',
                        'url' => $row[1] ?? '',
                        'date' => $row[2] ?? date('Y-m-d')
                    ];
                }
            }
        }
        return $forms;
    } catch (Exception $e) {
        return [];
    }
}
// Data loaded via AJAX with skeleton loading
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $categoryName; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Resource Hints for Faster Loading -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://www.googleapis.com" crossorigin>
    <link rel="dns-prefetch" href="https://drive.google.com">
    
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/smk62.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/ajax.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    <style>
        .category-header {
            background: linear-gradient(135deg, <?php echo $categoryColor; ?> 0%, <?php echo $categoryColor; ?>dd 100%);
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .category-header i { font-size: 3rem; }
        .category-header h1 { margin: 0; font-size: 2rem; }
        
        .section-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        .section-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-bar {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .filter-bar input, .filter-bar select {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .filter-bar input:focus { outline: none; border-color: <?php echo $categoryColor; ?>; }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        thead { background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        thead th { padding: 0.625rem 0.75rem; text-align: left; font-weight: 600; color: #475569; font-size: 0.8125rem; text-transform: uppercase; }
        tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
        tbody tr:hover { background: #f8fafc; }
        tbody td { padding: 0.625rem 0.75rem; vertical-align: middle; }
        
        .file-info { display: flex; align-items: center; gap: 0.5rem; }
        .file-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 0.375rem; }
        .file-icon i { font-size: 1rem; color: <?php echo $categoryColor; ?>; }
        .file-details { min-width: 0; flex: 1; }
        .file-name { font-weight: 500; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        
        .empty-state { text-align: center; padding: 3rem; color: #6b7280; }
        .empty-state i { font-size: 3rem; opacity: 0.5; margin-bottom: 1rem; }
        
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); animation: fadeIn 0.3s; }
        .modal-content { background: white; margin: 5% auto; border-radius: 1rem; width: 90%; max-width: 600px; max-height: 85vh; overflow: hidden; animation: slideDown 0.3s; }
        .modal-header { background: <?php echo $categoryColor; ?>; color: white; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; font-size: 1.25rem; }
        .modal-body { padding: 1.5rem; max-height: calc(85vh - 140px); overflow-y: auto; }
        .close { color: white; font-size: 2rem; cursor: pointer; background: none; border: none; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
        .close:hover { background: rgba(255,255,255,0.2); }
        
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #1e293b; }
        .form-group input { width: 100%; padding: 0.75rem; border: 2px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.875rem; }
        .form-group input:focus { border-color: <?php echo $categoryColor; ?>; box-shadow: 0 0 0 3px <?php echo $categoryColor; ?>20; outline: none; }
        .form-actions { display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; }
        .alert-message { display: none; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @media (max-width: 768px) { .filter-bar { grid-template-columns: 1fr; } .category-header { padding: 1.5rem; } .category-header i { font-size: 2rem; } .category-header h1 { font-size: 1.5rem; } }
        
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
            <div class="category-header">
                <i class="fas <?php echo $categoryIcon; ?>"></i>
                <div>
                    <h1><?php echo $categoryName; ?></h1>
                    <p style="margin: 0; opacity: 0.9;">Kelola file, link, dan form <?php echo strtolower($categoryName); ?></p>
                </div>
            </div>
            
            <!-- File Manager Section -->
            <div class="section-container">
                <div class="section-header">
                    <h2><i class="fas fa-folder-open"></i> File Manager</h2>
                    <div class="btn-group" style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-primary" onclick="openBatchFileSelector('<?php echo $categoryKey; ?>')"><i class="fas fa-cloud-upload-alt"></i> Upload File</button>
                    </div>
                </div>
                <div class="filter-bar">
                    <input type="text" id="searchFiles" placeholder="Cari file..." onkeyup="filterFiles()">
                    <select id="sortFiles" onchange="sortFiles()">
                        <option value="modified_desc">Terbaru</option>
                        <option value="modified_asc">Terlama</option>
                        <option value="name_asc">A-Z</option>
                        <option value="name_desc">Z-A</option>
                    </select>
                    <button class="btn btn-secondary" onclick="resetFilesFilter()" style="padding: 0.5rem 0.75rem;"><i class="fas fa-redo"></i></button>
                </div>
                <!-- Skeleton Loader -->
                <div id="skeletonFiles" class="skeleton-loader">
                    <table>
                        <thead><tr><th style="width: 75%;">Nama File</th><th style="width: 25%; text-align: center;">Aksi</th></tr></thead>
                        <tbody>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- Data Table -->
                <div id="filesTableWrapper" style="display: none;">
                    <div id="filesEmptyState" class="empty-state" style="display: none;"><i class="fas fa-inbox"></i><h3>Belum Ada File</h3><p>Upload file pertama dengan klik tombol "Upload File"</p></div>
                    <div style="overflow-x: auto;">
                        <table id="filesTable">
                            <thead><tr><th style="width: 75%;">Nama File</th><th style="width: 25%; text-align: center;">Aksi</th></tr></thead>
                            <tbody id="filesTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Links Section -->
            <div class="section-container">
                <div class="section-header">
                    <h2><i class="fas fa-link"></i> Links</h2>
                    <button class="btn btn-primary" onclick="openLinkModal()"><i class="fas fa-plus"></i> Tambah Link</button>
                </div>
                <div class="filter-bar">
                    <input type="text" id="searchLinks" placeholder="Cari link..." onkeyup="filterLinks()">
                    <div></div>
                    <button class="btn btn-secondary" onclick="resetLinksFilter()" style="padding: 0.5rem 0.75rem;"><i class="fas fa-redo"></i></button>
                </div>
                <!-- Skeleton Loader -->
                <div id="skeletonLinks" class="skeleton-loader">
                    <table>
                        <thead><tr><th style="width: 50%;">Judul</th><th style="width: 15%;">Tanggal</th><th style="width: 35%; text-align: center;">Aksi</th></tr></thead>
                        <tbody>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- Data Table -->
                <div id="linksTableWrapper" style="display: none;">
                    <div id="linksEmptyState" class="empty-state" style="display: none;"><i class="fas fa-link"></i><h3>Belum Ada Link</h3><p>Tambahkan link pertama dengan klik tombol "Tambah Link"</p></div>
                    <div style="overflow-x: auto;">
                        <table id="linksTable">
                            <thead><tr><th style="width: 50%;">Judul</th><th style="width: 15%;">Tanggal</th><th style="width: 35%; text-align: center;">Aksi</th></tr></thead>
                            <tbody id="linksTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Forms Section -->
            <div class="section-container">
                <div class="section-header">
                    <h2><i class="fas fa-file-alt"></i> Forms</h2>
                    <button class="btn btn-primary" onclick="openFormModal()"><i class="fas fa-plus"></i> Tambah Form</button>
                </div>
                <div class="filter-bar">
                    <input type="text" id="searchForms" placeholder="Cari form..." onkeyup="filterForms()">
                    <div></div>
                    <button class="btn btn-secondary" onclick="resetFormsFilter()" style="padding: 0.5rem 0.75rem;"><i class="fas fa-redo"></i></button>
                </div>
                <!-- Skeleton Loader -->
                <div id="skeletonForms" class="skeleton-loader">
                    <table>
                        <thead><tr><th style="width: 50%;">Judul</th><th style="width: 15%;">Tanggal</th><th style="width: 35%; text-align: center;">Aksi</th></tr></thead>
                        <tbody>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                            <tr class="skeleton-row"><td><div class="skeleton-cell"><div class="skeleton-icon"></div><div class="skeleton-text"><div class="skeleton-line"></div></div></div></td><td><div class="skeleton-line medium"></div></td><td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td></tr>
                        </tbody>
                    </table>
                </div>
                <!-- Data Table -->
                <div id="formsTableWrapper" style="display: none;">
                    <div id="formsEmptyState" class="empty-state" style="display: none;"><i class="fas fa-file-alt"></i><h3>Belum Ada Form</h3><p>Tambahkan form pertama dengan klik tombol "Tambah Form"</p></div>
                    <div style="overflow-x: auto;">
                        <table id="formsTable">
                            <thead><tr><th style="width: 50%;">Judul</th><th style="width: 15%;">Tanggal</th><th style="width: 35%; text-align: center;">Aksi</th></tr></thead>
                            <tbody id="formsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php include __DIR__ . '/../../includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Upload Modal (Batch) -->
    <div id="uploadModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header"><h3><i class="fas fa-cloud-upload-alt"></i> Upload File</h3><button class="close" onclick="closeUploadModal()">&times;</button></div>
            <div class="modal-body">
                <div id="uploadAlert" class="alert-message"></div>
                <p style="color:#64748b;font-size:0.875rem;margin-bottom:1rem;">Pilih file (max 15 file, 100MB per file)</p>
                <div id="modalDropArea" style="border:2px dashed #cbd5e1;border-radius:0.75rem;padding:2rem;text-align:center;cursor:pointer;transition:all 0.2s;background:#f8fafc;">
                    <i class="fas fa-cloud-upload-alt" style="font-size:2.5rem;color:#3b82f6;margin-bottom:0.5rem;"></i>
                    <p style="margin:0;font-weight:600;color:#1e293b;">Klik atau drag & drop file di sini</p>
                    <p style="margin:0.25rem 0 0;color:#64748b;font-size:0.8rem;">Mendukung semua jenis file</p>
                    <input type="file" id="modalFileInput" multiple style="display:none;">
                </div>
                <div id="modalSelectedFiles" style="margin-top:1rem;max-height:150px;overflow-y:auto;"></div>
                <div class="form-actions" style="margin-top:1.5rem;">
                    <button type="button" onclick="closeUploadModal()" class="btn btn-secondary">Batal</button>
                    <button type="button" onclick="startCategoryUpload()" id="uploadBtn" class="btn btn-primary" disabled>Upload</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Link Modal -->
    <div id="linkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-link"></i> Tambah Link</h3><button class="close" onclick="closeLinkModal()">&times;</button></div>
            <div class="modal-body">
                <div id="addLinkAlert" class="alert-message"></div>
                <form id="addLinkForm" onsubmit="return false;">
                    <input type="hidden" name="category" value="<?php echo $categoryKey; ?>">
                    <div class="form-group"><label>Judul</label><input type="text" id="addLinkTitle" name="title" required></div>
                    <div class="form-group"><label>URL</label><input type="url" id="addLinkUrl" name="url" required></div>
                    <div class="form-actions">
                        <button type="button" onclick="closeLinkModal()" class="btn btn-secondary">Batal</button>
                        <button type="button" onclick="submitAddLink()" id="addLinkBtn" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Form Modal -->
    <div id="formModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-file-alt"></i> Tambah Form</h3><button class="close" onclick="closeFormModal()">&times;</button></div>
            <div class="modal-body">
                <div id="addFormAlert" class="alert-message"></div>
                <form id="addFormForm" onsubmit="return false;">
                    <input type="hidden" name="category" value="<?php echo $categoryKey; ?>">
                    <div class="form-group"><label>Judul</label><input type="text" id="addFormTitle" name="title" required></div>
                    <div class="form-group"><label>URL</label><input type="url" id="addFormUrl" name="url" required></div>
                    <div class="form-actions">
                        <button type="button" onclick="closeFormModal()" class="btn btn-secondary">Batal</button>
                        <button type="button" onclick="submitAddForm()" id="addFormBtn" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Link Modal -->
    <div id="editLinkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-edit"></i> Edit Link</h3><button class="close" onclick="closeEditLinkModal()">&times;</button></div>
            <div class="modal-body">
                <div id="editLinkAlert" class="alert-message"></div>
                <form id="editLinkForm" onsubmit="return false;">
                    <input type="hidden" id="editLinkId" name="id">
                    <input type="hidden" name="category" value="<?php echo $categoryKey; ?>">
                    <div class="form-group"><label>Judul</label><input type="text" id="editLinkTitle" name="title" required></div>
                    <div class="form-group"><label>URL</label><input type="url" id="editLinkUrl" name="url" required></div>
                    <div class="form-actions">
                        <button type="button" onclick="closeEditLinkModal()" class="btn btn-secondary">Batal</button>
                        <button type="button" onclick="submitEditLink()" id="editLinkBtn" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Form Modal -->
    <div id="editFormModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-edit"></i> Edit Form</h3><button class="close" onclick="closeEditFormModal()">&times;</button></div>
            <div class="modal-body">
                <div id="editFormAlert" class="alert-message"></div>
                <form id="editFormForm" onsubmit="return false;">
                    <input type="hidden" id="editFormId" name="id">
                    <input type="hidden" name="category" value="<?php echo $categoryKey; ?>">
                    <div class="form-group"><label>Judul</label><input type="text" id="editFormTitle" name="title" required></div>
                    <div class="form-group"><label>URL</label><input type="url" id="editFormUrl" name="url" required></div>
                    <div class="form-actions">
                        <button type="button" onclick="closeEditFormModal()" class="btn btn-secondary">Batal</button>
                        <button type="button" onclick="submitEditForm()" id="editFormBtn" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Detail Link Modal -->
    <div id="detailLinkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-info-circle"></i> Detail Link</h3><button class="close" onclick="closeDetailLinkModal()">&times;</button></div>
            <div class="modal-body" id="detailLinkContent"></div>
        </div>
    </div>
    
    <!-- Detail Form Modal -->
    <div id="detailFormModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-info-circle"></i> Detail Form</h3><button class="close" onclick="closeDetailFormModal()">&times;</button></div>
            <div class="modal-body" id="detailFormContent"></div>
        </div>
    </div>
    
    <script>
        let linksData = [];
        let formsData = [];
        const categoryKey = '<?php echo $categoryKey; ?>';
        const categorySlug = '<?php echo $categorySlug; ?>';
        const folderId = '<?php echo $folderId; ?>';
        const baseUrl = '<?php echo BASE_URL; ?>';
        const fileDeleteUrl = baseUrl + '/pages/files/delete';
        const filesDataUrl = baseUrl + '/api/files-data';
        const linksDataUrl = baseUrl + '/api/links-data';
        const formsDataUrl = baseUrl + '/api/forms-data';
        
        // Initialize upload manager
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof initUploadManager === 'function') {
                window.uploadManager = initUploadManager(categoryKey, baseUrl);
            }
        });
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/table-pagination.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/upload-manager.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script>
        // Modal Upload Variables
        let modalSelectedFiles = [];
        const MODAL_MAX_FILES = 15;
        const MODAL_MAX_SIZE = 100 * 1024 * 1024;
        
        // Modal Functions
        function openUploadModal() { 
            document.getElementById('uploadModal').style.display = 'block';
            setupModalUploadHandlers();
        }
        function closeUploadModal() { 
            document.getElementById('uploadModal').style.display = 'none'; 
            modalSelectedFiles = [];
            renderModalFiles();
            hideAlert('uploadAlert'); 
        }
        function openLinkModal() { document.getElementById('linkModal').style.display = 'block'; }
        function closeLinkModal() { document.getElementById('linkModal').style.display = 'none'; document.getElementById('addLinkForm').reset(); hideAlert('addLinkAlert'); }
        function openFormModal() { document.getElementById('formModal').style.display = 'block'; }
        function closeFormModal() { document.getElementById('formModal').style.display = 'none'; document.getElementById('addFormForm').reset(); hideAlert('addFormAlert'); }
        function closeEditLinkModal() { document.getElementById('editLinkModal').style.display = 'none'; hideAlert('editLinkAlert'); }
        function closeEditFormModal() { document.getElementById('editFormModal').style.display = 'none'; hideAlert('editFormAlert'); }
        function closeDetailLinkModal() { document.getElementById('detailLinkModal').style.display = 'none'; }
        function closeDetailFormModal() { document.getElementById('detailFormModal').style.display = 'none'; }
        
        // Alert Functions
        function showAlert(id, msg, success) {
            const el = document.getElementById(id);
            el.style.display = 'block';
            el.style.background = success ? '#d1fae5' : '#fee2e2';
            el.style.color = success ? '#065f46' : '#991b1b';
            el.textContent = msg;
        }
        function hideAlert(id) { const el = document.getElementById(id); if(el) el.style.display = 'none'; }
        
        // Edit Functions
        function editLink(i) {
            const d = linksData[i];
            document.getElementById('editLinkId').value = d.id;
            document.getElementById('editLinkTitle').value = d.title;
            document.getElementById('editLinkUrl').value = d.url;
            document.getElementById('editLinkModal').style.display = 'block';
        }
        function editForm(i) {
            const d = formsData[i];
            document.getElementById('editFormId').value = d.id;
            document.getElementById('editFormTitle').value = d.title;
            document.getElementById('editFormUrl').value = d.url;
            document.getElementById('editFormModal').style.display = 'block';
        }
        
        // Detail Functions
        function viewLinkDetail(i) {
            const d = linksData[i];
            document.getElementById('detailLinkContent').innerHTML = `
                <div style="margin-bottom:1rem;"><label style="font-weight:600;color:#64748b;font-size:0.875rem;">Judul</label><p style="margin:0.25rem 0 0;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;">${d.title}</p></div>
                <div style="margin-bottom:1rem;"><label style="font-weight:600;color:#64748b;font-size:0.875rem;">URL</label><p style="margin:0.25rem 0 0;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;word-break:break-all;">${d.url}</p></div>
                <div style="margin-bottom:1rem;"><label style="font-weight:600;color:#64748b;font-size:0.875rem;">Tanggal</label><p style="margin:0.25rem 0 0;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;">${new Date(d.date).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'})}</p></div>
                <div style="text-align:right;margin-top:1.5rem;"><a href="${d.url}" target="_blank" class="btn btn-primary"><i class="fas fa-external-link-alt"></i> Buka Link</a></div>`;
            document.getElementById('detailLinkModal').style.display = 'block';
        }
        function viewFormDetail(i) {
            const d = formsData[i];
            document.getElementById('detailFormContent').innerHTML = `
                <div style="margin-bottom:1rem;"><label style="font-weight:600;color:#64748b;font-size:0.875rem;">Judul</label><p style="margin:0.25rem 0 0;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;">${d.title}</p></div>
                <div style="margin-bottom:1rem;"><label style="font-weight:600;color:#64748b;font-size:0.875rem;">URL</label><p style="margin:0.25rem 0 0;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;word-break:break-all;">${d.url}</p></div>
                <div style="margin-bottom:1rem;"><label style="font-weight:600;color:#64748b;font-size:0.875rem;">Tanggal</label><p style="margin:0.25rem 0 0;padding:0.75rem;background:#f8fafc;border-radius:0.5rem;">${new Date(d.date).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'})}</p></div>
                <div style="text-align:right;margin-top:1.5rem;"><a href="${d.url}" target="_blank" class="btn btn-primary"><i class="fas fa-external-link-alt"></i> Buka Form</a></div>`;
            document.getElementById('detailFormModal').style.display = 'block';
        }

        async function deleteDriveFile(fileId, fileName) {
            const ok = await window.showConfirmDialog({
                title: 'Hapus File',
                message: `Apakah Anda yakin ingin menghapus file "${fileName}"?`,
                confirmText: 'Hapus',
                cancelText: 'Batal',
                danger: true
            });
            if (!ok) return;

            const fd = new FormData();
            fd.append('id', fileId);
            fd.append('confirm', '1');
            fd.append('redirect', 'category');
            fd.append('category', categorySlug);

            try {
                await fetchJson(fileDeleteUrl, { method: 'POST', body: fd });
                await reloadFilesTable();
                if (typeof window.showToast === 'function') {
                    window.showToast('File berhasil dihapus', 'success');
                }
            } catch (e) {
                if (typeof window.showToast === 'function') {
                    window.showToast('Gagal menghapus file: ' + e.message, 'error');
                }
            }
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        async function reloadFilesTable() {
            const skeleton = document.getElementById('skeletonFiles');
            const wrapper = document.getElementById('filesTableWrapper');
            const emptyState = document.getElementById('filesEmptyState');
            const tableEl = document.getElementById('filesTable');

            try {
                const url = new URL(filesDataUrl, window.location.origin);
                url.searchParams.set('ajax', '1');
                url.searchParams.set('category', categoryKey);
                url.searchParams.set('_t', Date.now());
                const data = await fetchJson(url.toString());
                const files = (data && data.data && data.data.files) ? data.data.files : [];

                const tbody = document.getElementById('filesTableBody');
                if (!tbody) return;

                const rowsHtml = files.map(f => {
                    const name = escapeHtml(f.name);
                    const modified = f.modifiedTime ? new Date(f.modifiedTime) : null;
                    const dd = modified ? String(modified.getDate()).padStart(2, '0') : '--';
                    const mm = modified ? String(modified.getMonth() + 1).padStart(2, '0') : '--';
                    const yy = modified ? String(modified.getFullYear()) : '----';
                    const dateText = `${dd}/${mm}/${yy}`;
                    const sizeFormatted = escapeHtml(f.sizeFormatted || '0 Bytes');

                    return `
                        <tr class="file-row" data-name="${escapeHtml(String(f.name || '').toLowerCase())}" data-modified="${Number(f.modifiedTimestamp || 0)}">
                            <td>
                                <div class="file-info">
                                    <div class="file-icon"><i class="fas fa-file-alt"></i></div>
                                    <div class="file-details"><span class="file-name">${name}</span><span class="file-meta">${sizeFormatted} • ${escapeHtml(dateText)}</span></div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <div class="btn-group" style="display: inline-flex; gap: 0.25rem;">
                                    <a href="${escapeHtml(f.webViewLink || '#')}" target="_blank" class="btn btn-sm btn-info" style="padding: 0.375rem 0.625rem;" title="Lihat"><i class="fas fa-eye"></i></a>
                                    <button type="button" class="btn btn-sm btn-danger" style="padding: 0.375rem 0.625rem;" title="Hapus" onclick='deleteDriveFile(${JSON.stringify(f.id)}, ${JSON.stringify(f.name)})'><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                tbody.innerHTML = rowsHtml;

                // Handle empty state
                if (files.length === 0) {
                    if (tableEl) tableEl.style.display = 'none';
                    if (emptyState) emptyState.style.display = '';
                } else {
                    if (tableEl) tableEl.style.display = '';
                    if (emptyState) emptyState.style.display = 'none';
                }

                if (window.filesPagination && typeof window.filesPagination.refresh === 'function') {
                    window.filesPagination.refresh();
                } else if (window.tablePaginationInstances && window.tablePaginationInstances['filesTable']) {
                    window.tablePaginationInstances['filesTable'].refresh();
                }
            } catch (e) {
                console.error('Failed to load files:', e);
                if (emptyState) {
                    emptyState.innerHTML = '<i class="fas fa-exclamation-triangle"></i><h3>Gagal Memuat Data</h3><p>Terjadi kesalahan saat memuat file. <a href="javascript:void(0)" onclick="reloadFilesTable()">Coba lagi</a></p>';
                    emptyState.style.display = '';
                }
                if (tableEl) tableEl.style.display = 'none';
            } finally {
                if (skeleton) skeleton.style.display = 'none';
                if (wrapper) wrapper.style.display = '';
            }
        }

        async function reloadLinksTable() {
            const skeleton = document.getElementById('skeletonLinks');
            const wrapper = document.getElementById('linksTableWrapper');
            const emptyState = document.getElementById('linksEmptyState');
            const tableEl = document.getElementById('linksTable');

            try {
                const url = new URL(linksDataUrl, window.location.origin);
                url.searchParams.set('ajax', '1');
                url.searchParams.set('category', categoryKey);
                url.searchParams.set('_t', Date.now());
                const data = await fetchJson(url.toString());
                linksData = (data && data.data && data.data.links) ? data.data.links : [];

                const tbody = document.getElementById('linksTableBody');
                if (!tbody) return;

                const rowsHtml = linksData.map((l, idx) => {
                    const title = escapeHtml(l.title);
                    const date = l.date ? new Date(l.date) : null;
                    const dd = date ? String(date.getDate()).padStart(2, '0') : '--';
                    const mm = date ? String(date.getMonth() + 1).padStart(2, '0') : '--';
                    const yy = date ? String(date.getFullYear()) : '----';
                    const dateText = `${dd}/${mm}/${yy}`;
                    return `
                        <tr class="link-row" data-title="${escapeHtml(String(l.title || '').toLowerCase())}">
                            <td>
                                <div class="file-info">
                                    <div class="file-icon"><i class="fas fa-link"></i></div>
                                    <div class="file-details"><span class="file-name">${title}</span></div>
                                </div>
                            </td>
                            <td style="color: #64748b; font-size: 0.8125rem;">${escapeHtml(dateText)}</td>
                            <td style="text-align: center;">
                                <div class="btn-group" style="display: inline-flex; gap: 0.25rem;">
                                    <button onclick="viewLinkDetail(${idx})" class="btn btn-sm btn-info" title="Detail"><i class="fas fa-info-circle"></i></button>
                                    <button onclick="editLink(${idx})" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button onclick="deleteLink(${idx})" class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                tbody.innerHTML = rowsHtml;

                // Handle empty state
                if (linksData.length === 0) {
                    if (tableEl) tableEl.style.display = 'none';
                    if (emptyState) emptyState.style.display = '';
                } else {
                    if (tableEl) tableEl.style.display = '';
                    if (emptyState) emptyState.style.display = 'none';
                }

                if (window.linksPagination && typeof window.linksPagination.refresh === 'function') {
                    window.linksPagination.refresh();
                } else if (window.tablePaginationInstances && window.tablePaginationInstances['linksTable']) {
                    window.tablePaginationInstances['linksTable'].refresh();
                }
            } catch (e) {
                console.error('Failed to load links:', e);
                if (emptyState) {
                    emptyState.innerHTML = '<i class="fas fa-exclamation-triangle"></i><h3>Gagal Memuat Data</h3><p>Terjadi kesalahan saat memuat link. <a href="javascript:void(0)" onclick="reloadLinksTable()">Coba lagi</a></p>';
                    emptyState.style.display = '';
                }
                if (tableEl) tableEl.style.display = 'none';
            } finally {
                if (skeleton) skeleton.style.display = 'none';
                if (wrapper) wrapper.style.display = '';
            }
        }

        async function reloadFormsTable() {
            const skeleton = document.getElementById('skeletonForms');
            const wrapper = document.getElementById('formsTableWrapper');
            const emptyState = document.getElementById('formsEmptyState');
            const tableEl = document.getElementById('formsTable');

            try {
                const url = new URL(formsDataUrl, window.location.origin);
                url.searchParams.set('ajax', '1');
                url.searchParams.set('category', categoryKey);
                url.searchParams.set('_t', Date.now());
                const data = await fetchJson(url.toString());
                formsData = (data && data.data && data.data.forms) ? data.data.forms : [];

                const tbody = document.getElementById('formsTableBody');
                if (!tbody) return;

                const rowsHtml = formsData.map((f, idx) => {
                    const title = escapeHtml(f.title);
                    const date = f.date ? new Date(f.date) : null;
                    const dd = date ? String(date.getDate()).padStart(2, '0') : '--';
                    const mm = date ? String(date.getMonth() + 1).padStart(2, '0') : '--';
                    const yy = date ? String(date.getFullYear()) : '----';
                    const dateText = `${dd}/${mm}/${yy}`;
                    return `
                        <tr class="form-row" data-title="${escapeHtml(String(f.title || '').toLowerCase())}">
                            <td>
                                <div class="file-info">
                                    <div class="file-icon"><i class="fas fa-file-alt"></i></div>
                                    <div class="file-details"><span class="file-name">${title}</span></div>
                                </div>
                            </td>
                            <td style="color: #64748b; font-size: 0.8125rem;">${escapeHtml(dateText)}</td>
                            <td style="text-align: center;">
                                <div class="btn-group" style="display: inline-flex; gap: 0.25rem;">
                                    <button onclick="viewFormDetail(${idx})" class="btn btn-sm btn-info" title="Detail"><i class="fas fa-info-circle"></i></button>
                                    <button onclick="editForm(${idx})" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button onclick="deleteForm(${idx})" class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                tbody.innerHTML = rowsHtml;

                // Handle empty state
                if (formsData.length === 0) {
                    if (tableEl) tableEl.style.display = 'none';
                    if (emptyState) emptyState.style.display = '';
                } else {
                    if (tableEl) tableEl.style.display = '';
                    if (emptyState) emptyState.style.display = 'none';
                }

                if (window.formsPagination && typeof window.formsPagination.refresh === 'function') {
                    window.formsPagination.refresh();
                } else if (window.tablePaginationInstances && window.tablePaginationInstances['formsTable']) {
                    window.tablePaginationInstances['formsTable'].refresh();
                }
            } catch (e) {
                console.error('Failed to load forms:', e);
                if (emptyState) {
                    emptyState.innerHTML = '<i class="fas fa-exclamation-triangle"></i><h3>Gagal Memuat Data</h3><p>Terjadi kesalahan saat memuat form. <a href="javascript:void(0)" onclick="reloadFormsTable()">Coba lagi</a></p>';
                    emptyState.style.display = '';
                }
                if (tableEl) tableEl.style.display = 'none';
            } finally {
                if (skeleton) skeleton.style.display = 'none';
                if (wrapper) wrapper.style.display = '';
            }
        }
        
        // Delete Functions
        async function deleteLink(i) {
            const ok = await window.showConfirmDialog({
                title: 'Hapus Link',
                message: `Hapus link "${linksData[i].title}"?`,
                confirmText: 'Hapus',
                cancelText: 'Batal',
                danger: true
            });
            if (!ok) return;

            const fd = new FormData();
            fd.append('id', linksData[i].id);
            fd.append('category', categoryKey);
            fd.append('confirm', '1');
            fd.append('redirect', 'category');

            try {
                await fetchJson(`${baseUrl}/pages/links/delete`, { method: 'POST', body: fd });
                await reloadLinksTable();
                if (typeof window.showToast === 'function') {
                    window.showToast('Link berhasil dihapus', 'success');
                }
            } catch (e) {
                if (typeof window.showToast === 'function') {
                    window.showToast('Gagal menghapus link: ' + e.message, 'error');
                }
            }
        }
        async function deleteForm(i) {
            const ok = await window.showConfirmDialog({
                title: 'Hapus Form',
                message: `Hapus form "${formsData[i].title}"?`,
                confirmText: 'Hapus',
                cancelText: 'Batal',
                danger: true
            });
            if (!ok) return;

            const fd = new FormData();
            fd.append('id', formsData[i].id);
            fd.append('category', categoryKey);
            fd.append('confirm', '1');
            fd.append('redirect', 'category');

            try {
                await fetchJson(`${baseUrl}/pages/forms/delete`, { method: 'POST', body: fd });
                await reloadFormsTable();
                if (typeof window.showToast === 'function') {
                    window.showToast('Form berhasil dihapus', 'success');
                }
            } catch (e) {
                if (typeof window.showToast === 'function') {
                    window.showToast('Gagal menghapus form: ' + e.message, 'error');
                }
            }
        }

        async function fetchJson(url, options = {}) {
            const requestUrl = url.includes('?') ? `${url}&ajax=1` : `${url}?ajax=1`;

            if (options && options.body && (options.body instanceof FormData)) {
                if (window.APP_CSRF_TOKEN && !options.body.has('csrf_token')) {
                    options.body.append('csrf_token', window.APP_CSRF_TOKEN);
                }
            }

            const headers = Object.assign(
                { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': (window.APP_CSRF_TOKEN || '') },
                options.headers || {}
            );

            const res = await fetch(requestUrl, Object.assign({}, options, { headers }));
            const text = await res.text();

            let data;
            try {
                data = text ? JSON.parse(text) : {};
            } catch (e) {
                const snippet = (text || '').slice(0, 180).replace(/\s+/g, ' ').trim();
                throw new Error(`Response server bukan JSON (${res.status}). URL: ${requestUrl}. Awal respons: ${snippet}`);
            }

            if (data && data.redirect) {
                window.location.href = data.redirect;
                return data;
            }

            if (!res.ok) {
                throw new Error((data && data.message) ? data.message : 'Network error');
            }

            return data;
        }
        
        // Setup modal upload handlers
        function setupModalUploadHandlers() {
            const dropArea = document.getElementById('modalDropArea');
            const fileInput = document.getElementById('modalFileInput');
            
            if (!dropArea || !fileInput) return;
            
            const newDropArea = dropArea.cloneNode(true);
            dropArea.parentNode.replaceChild(newDropArea, dropArea);
            
            const newFileInput = newDropArea.querySelector('input[type="file"]');
            
            newDropArea.addEventListener('click', () => newFileInput.click());
            
            newDropArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                newDropArea.style.borderColor = '#3b82f6';
                newDropArea.style.background = '#eff6ff';
            });
            
            newDropArea.addEventListener('dragleave', () => {
                newDropArea.style.borderColor = '#cbd5e1';
                newDropArea.style.background = '#f8fafc';
            });
            
            newDropArea.addEventListener('drop', (e) => {
                e.preventDefault();
                newDropArea.style.borderColor = '#cbd5e1';
                newDropArea.style.background = '#f8fafc';
                addModalFiles(Array.from(e.dataTransfer.files));
            });
            
            newFileInput.addEventListener('change', (e) => {
                addModalFiles(Array.from(e.target.files));
                newFileInput.value = '';
            });
        }
        
        function addModalFiles(files) {
            for (const file of files) {
                if (modalSelectedFiles.length >= MODAL_MAX_FILES) {
                    alert('Maksimal ' + MODAL_MAX_FILES + ' file');
                    break;
                }
                if (file.size > MODAL_MAX_SIZE) {
                    alert('File "' + file.name + '" melebihi 100MB');
                    continue;
                }
                if (!modalSelectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    modalSelectedFiles.push(file);
                }
            }
            renderModalFiles();
        }
        
        function renderModalFiles() {
            const container = document.getElementById('modalSelectedFiles');
            const btn = document.getElementById('uploadBtn');
            
            if (modalSelectedFiles.length === 0) {
                container.innerHTML = '';
                btn.disabled = true;
                return;
            }
            
            btn.disabled = false;
            container.innerHTML = modalSelectedFiles.map((file, i) => `
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem;background:#f1f5f9;border-radius:0.5rem;margin-bottom:0.5rem;">
                    <i class="fas fa-file" style="color:#3b82f6;"></i>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.8rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${file.name}</div>
                        <div style="font-size:0.7rem;color:#64748b;">${formatFileSizeSimple(file.size)}</div>
                    </div>
                    <button type="button" onclick="removeModalFile(${i})" style="background:#fee2e2;border:none;color:#ef4444;width:24px;height:24px;border-radius:4px;cursor:pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
        }
        
        function removeModalFile(index) {
            modalSelectedFiles.splice(index, 1);
            renderModalFiles();
        }
        
        function formatFileSizeSimple(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        function startCategoryUpload() {
            if (modalSelectedFiles.length === 0) {
                alert('Pilih file terlebih dahulu');
                return;
            }
            
            if (!window.uploadManager) {
                window.uploadManager = initUploadManager(categoryKey, baseUrl);
            }
            
            window.uploadManager.addFiles(modalSelectedFiles, categoryKey);
            
            modalSelectedFiles = [];
            renderModalFiles();
            
            setTimeout(() => {
                closeUploadModal();
            }, 300);
        }
        
        async function submitUpload() {
            startCategoryUpload();
        }
        
        async function submitAddLink() {
            const btn = document.getElementById('addLinkBtn');
            const form = document.getElementById('addLinkForm');
            const formData = new FormData(form);
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            hideAlert('addLinkAlert');
            
            try {
                const data = await fetchJson(`${baseUrl}/pages/links/add`, {
                    method: 'POST',
                    body: formData
                });
                if (data.success) {
                    showAlert('addLinkAlert', data.message, true);
                    setTimeout(async () => {
                        btn.disabled = false;
                        btn.innerHTML = 'Simpan';
                        closeLinkModal();
                        try {
                            await reloadLinksTable();
                        } catch (e) {
                            location.reload();
                        }
                    }, 600);
                }
                else { showAlert('addLinkAlert', data.message, false); btn.disabled = false; btn.innerHTML = 'Simpan'; }
            } catch (e) { showAlert('addLinkAlert', 'Error: ' + e.message, false); btn.disabled = false; btn.innerHTML = 'Simpan'; }
        }
        
        async function submitAddForm() {
            const btn = document.getElementById('addFormBtn');
            const form = document.getElementById('addFormForm');
            const formData = new FormData(form);
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            hideAlert('addFormAlert');
            
            try {
                const data = await fetchJson(`${baseUrl}/pages/forms/add`, {
                    method: 'POST',
                    body: formData
                });
                if (data.success) {
                    showAlert('addFormAlert', data.message, true);
                    setTimeout(async () => {
                        btn.disabled = false;
                        btn.innerHTML = 'Simpan';
                        closeFormModal();
                        try {
                            await reloadFormsTable();
                        } catch (e) {
                            location.reload();
                        }
                    }, 600);
                }
                else { showAlert('addFormAlert', data.message, false); btn.disabled = false; btn.innerHTML = 'Simpan'; }
            } catch (e) { showAlert('addFormAlert', 'Error: ' + e.message, false); btn.disabled = false; btn.innerHTML = 'Simpan'; }
        }
        
        async function submitEditLink() {
            const btn = document.getElementById('editLinkBtn');
            const form = document.getElementById('editLinkForm');
            const formData = new FormData(form);
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            hideAlert('editLinkAlert');
            
            try {
                const data = await fetchJson(`${baseUrl}/pages/links/update`, {
                    method: 'POST',
                    body: formData
                });
                if (data.success) {
                    showAlert('editLinkAlert', data.message, true);
                    setTimeout(async () => {
                        closeEditLinkModal();
                        try {
                            await reloadLinksTable();
                        } catch (e) {
                            location.reload();
                        }
                    }, 600);
                }
                else { showAlert('editLinkAlert', data.message, false); btn.disabled = false; btn.innerHTML = 'Simpan'; }
            } catch (e) { showAlert('editLinkAlert', 'Error: ' + e.message, false); btn.disabled = false; btn.innerHTML = 'Simpan'; }
        }
        
        async function submitEditForm() {
            const btn = document.getElementById('editFormBtn');
            const form = document.getElementById('editFormForm');
            const formData = new FormData(form);
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            hideAlert('editFormAlert');
            
            try {
                const data = await fetchJson(`${baseUrl}/pages/forms/update`, {
                    method: 'POST',
                    body: formData
                });
                if (data.success) {
                    showAlert('editFormAlert', data.message, true);
                    setTimeout(async () => {
                        closeEditFormModal();
                        try {
                            await reloadFormsTable();
                        } catch (e) {
                            location.reload();
                        }
                    }, 600);
                }
                else { showAlert('editFormAlert', data.message, false); btn.disabled = false; btn.innerHTML = 'Simpan'; }
            } catch (e) { showAlert('editFormAlert', 'Error: ' + e.message, false); btn.disabled = false; btn.innerHTML = 'Simpan'; }
        }
        
        // Filter & Sort Functions
        function filterFiles() {
            const s = document.getElementById('searchFiles').value.toLowerCase();
            document.querySelectorAll('.file-row').forEach(r => r.style.display = r.dataset.name.includes(s) ? '' : 'none');
        }
        function sortFiles() {
            const [f, d] = document.getElementById('sortFiles').value.split('_');
            const tbody = document.getElementById('filesTableBody');
            const rows = Array.from(tbody.querySelectorAll('.file-row'));
            rows.sort((a, b) => {
                if (f === 'name') return d === 'asc' ? a.dataset.name.localeCompare(b.dataset.name) : b.dataset.name.localeCompare(a.dataset.name);
                return d === 'asc' ? parseInt(a.dataset.modified) - parseInt(b.dataset.modified) : parseInt(b.dataset.modified) - parseInt(a.dataset.modified);
            });
            rows.forEach(r => tbody.appendChild(r));
        }
        function resetFilesFilter() { document.getElementById('searchFiles').value = ''; document.getElementById('sortFiles').value = 'modified_desc'; filterFiles(); sortFiles(); }
        function filterLinks() { const s = document.getElementById('searchLinks').value.toLowerCase(); document.querySelectorAll('.link-row').forEach(r => r.style.display = r.dataset.title.includes(s) ? '' : 'none'); }
        function resetLinksFilter() { document.getElementById('searchLinks').value = ''; filterLinks(); }
        function filterForms() { const s = document.getElementById('searchForms').value.toLowerCase(); document.querySelectorAll('.form-row').forEach(r => r.style.display = r.dataset.title.includes(s) ? '' : 'none'); }
        function resetFormsFilter() { document.getElementById('searchForms').value = ''; filterForms(); }
        
        // Close modal on outside click
        window.onclick = function(e) { if (e.target.classList.contains('modal')) e.target.style.display = 'none'; }
        
        // Init pagination and lazy load data
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof initTablePagination === 'function') {
                window.filesPagination = initTablePagination('filesTable', { rowsPerPage: 10, rowsPerPageOptions: [10, 25, 50, 100] });
                window.linksPagination = initTablePagination('linksTable', { rowsPerPage: 10, rowsPerPageOptions: [10, 25, 50, 100] });
                window.formsPagination = initTablePagination('formsTable', { rowsPerPage: 10, rowsPerPageOptions: [10, 25, 50, 100] });
            }
            
            // Lazy load all tables via AJAX (skeleton shown until data arrives)
            reloadFilesTable();
            reloadLinksTable();
            reloadFormsTable();
        });
    </script>
</body>
</html>
