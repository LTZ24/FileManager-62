<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';

requireLogin();

$categoryKey = 'kurikulum';
$categoryName = 'Kurikulum';
$categoryIcon = 'fa-book';
$categoryColor = '#10b981';
$folderId = '1JlqjO6AxW2ML-FuP14f22wMmLSbASSzA';
$sheetId = SHEETS_KURIKULUM;

// Cache configuration
$cacheTime = 300;

// Get Files from Drive
function getCategoryFiles($folderId) {
    try {
        $client = getGoogleClient();
        $driveService = new Google_Service_Drive($client);
        
        $parameters = [
            'q' => "'{$folderId}' in parents and trashed=false",
            'fields' => 'files(id, name, mimeType, size, createdTime, modifiedTime, webViewLink, iconLink, thumbnailLink)',
            'orderBy' => 'modifiedTime desc',
            'pageSize' => 1000
        ];
        
        $results = $driveService->files->listFiles($parameters);
        return $results->getFiles();
    } catch (Exception $e) {
        return [];
    }
}

// Get Links from Sheets
function getCategoryLinks($sheetId) {
    try {
        $client = getGoogleClient();
        $sheetsService = new Google_Service_Sheets($client);
        
        $range = 'Links!A2:D';
        $response = $sheetsService->spreadsheets_values->get($sheetId, $range);
        $values = $response->getValues();
        
        $links = [];
        if (!empty($values)) {
            foreach ($values as $row) {
                if (count($row) >= 3) {
                    $links[] = [
                        'title' => $row[0] ?? '',
                        'url' => $row[1] ?? '',
                        'description' => $row[2] ?? '',
                        'date' => $row[3] ?? date('Y-m-d')
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
function getCategoryForms($sheetId) {
    try {
        $client = getGoogleClient();
        $sheetsService = new Google_Service_Sheets($client);
        
        $range = 'Forms!A2:D';
        $response = $sheetsService->spreadsheets_values->get($sheetId, $range);
        $values = $response->getValues();
        
        $forms = [];
        if (!empty($values)) {
            foreach ($values as $row) {
                if (count($row) >= 3) {
                    $forms[] = [
                        'title' => $row[0] ?? '',
                        'url' => $row[1] ?? '',
                        'description' => $row[2] ?? '',
                        'date' => $row[3] ?? date('Y-m-d')
                    ];
                }
            }
        }
        return $forms;
    } catch (Exception $e) {
        return [];
    }
}

// Cache files
$filesCacheKey = "category_{$categoryKey}_files";
if (isset($_SESSION[$filesCacheKey]) && 
    isset($_SESSION[$filesCacheKey . '_time']) && 
    (time() - $_SESSION[$filesCacheKey . '_time']) < $cacheTime) {
    $files = $_SESSION[$filesCacheKey];
} else {
    $files = getCategoryFiles($folderId);
    $_SESSION[$filesCacheKey] = $files;
    $_SESSION[$filesCacheKey . '_time'] = time();
}

// Cache links
$linksCacheKey = "category_{$categoryKey}_links";
if (isset($_SESSION[$linksCacheKey]) && 
    isset($_SESSION[$linksCacheKey . '_time']) && 
    (time() - $_SESSION[$linksCacheKey . '_time']) < $cacheTime) {
    $links = $_SESSION[$linksCacheKey];
} else {
    $links = getCategoryLinks($sheetId);
    $_SESSION[$linksCacheKey] = $links;
    $_SESSION[$linksCacheKey . '_time'] = time();
}

// Cache forms
$formsCacheKey = "category_{$categoryKey}_forms";
if (isset($_SESSION[$formsCacheKey]) && 
    isset($_SESSION[$formsCacheKey . '_time']) && 
    (time() - $_SESSION[$formsCacheKey . '_time']) < $cacheTime) {
    $forms = $_SESSION[$formsCacheKey];
} else {
    $forms = getCategoryForms($sheetId);
    $_SESSION[$formsCacheKey] = $forms;
    $_SESSION[$formsCacheKey . '_time'] = time();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $categoryName; ?> - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/smk62.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/ajax.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .category-header i {
            font-size: 3rem;
        }
        
        .category-header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .section-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .section-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .filter-bar {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-bar input {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.95rem;
        }
        
        .filter-bar input:focus {
            outline: none;
            border-color: <?php echo $categoryColor; ?>;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        thead {
            background: var(--light-color);
        }
        
        thead th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s;
        }
        
        tbody tr:hover {
            background: var(--light-color);
        }
        
        tbody td {
            padding: 1rem;
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
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 1rem;
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow: hidden;
            animation: slideDown 0.3s;
        }
        
        .modal-header {
            background: <?php echo $categoryColor; ?>;
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
        }
        
        .modal-body {
            padding: 1.5rem;
            max-height: calc(85vh - 140px);
            overflow-y: auto;
        }
        
        .close {
            color: white;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .close:hover {
            background: rgba(255, 255, 255, 0.2);
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
        
        @media (max-width: 768px) {
            .filter-bar {
                grid-template-columns: 1fr;
            }
            
            .category-header {
                padding: 1.5rem;
            }
            
            .category-header i {
                font-size: 2rem;
            }
            
            .category-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <!-- Category Header -->
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
                    <button class="btn btn-primary" onclick="openUploadModal()">
                        <i class="fas fa-cloud-upload-alt"></i> Upload File
                    </button>
                </div>
                
                <div class="filter-bar">
                    <input type="text" id="searchFiles" placeholder="Cari file..." onkeyup="filterFiles()">
                    <select id="sortFiles" onchange="sortFiles()" style="padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 0.5rem;">
                        <option value="modified_desc">Terakhir Diubah (Terbaru)</option>
                        <option value="modified_asc">Terakhir Diubah (Terlama)</option>
                        <option value="name_asc">Nama (A-Z)</option>
                        <option value="name_desc">Nama (Z-A)</option>
                    </select>
                    <button class="btn btn-secondary" onclick="resetFilesFilter()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
                
                <?php if (empty($files)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Belum Ada File</h3>
                        <p>Upload file pertama dengan klik tombol "Upload File"</p>
                    </div>
                <?php else: ?>
                    <table id="filesTable">
                        <thead>
                            <tr>
                                <th>Nama File</th>
                                <th>Ukuran</th>
                                <th>Terakhir Diubah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="filesTableBody">
                            <?php foreach ($files as $file): ?>
                                <tr class="file-row" 
                                    data-name="<?php echo strtolower($file->getName()); ?>"
                                    data-modified="<?php echo strtotime($file->getModifiedTime()); ?>">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <i class="fas fa-file" style="color: <?php echo $categoryColor; ?>;"></i>
                                            <span style="font-weight: 500;"><?php echo htmlspecialchars($file->getName()); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo formatFileSize($file->getSize() ?? 0); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($file->getModifiedTime())); ?></td>
                                    <td>
                                        <a href="<?php echo $file->getWebViewLink(); ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Lihat
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Links Section -->
            <div class="section-container">
                <div class="section-header">
                    <h2><i class="fas fa-link"></i> Links</h2>
                    <button class="btn btn-primary" onclick="openLinkModal()">
                        <i class="fas fa-plus"></i> Tambah Link
                    </button>
                </div>
                
                <div class="filter-bar">
                    <input type="text" id="searchLinks" placeholder="Cari link..." onkeyup="filterLinks()">
                    <div></div>
                    <button class="btn btn-secondary" onclick="resetLinksFilter()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
                
                <?php if (empty($links)): ?>
                    <div class="empty-state">
                        <i class="fas fa-link"></i>
                        <h3>Belum Ada Link</h3>
                        <p>Tambahkan link pertama dengan klik tombol "Tambah Link"</p>
                    </div>
                <?php else: ?>
                    <table id="linksTable">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Deskripsi</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="linksTableBody">
                            <?php foreach ($links as $link): ?>
                                <tr class="link-row" data-title="<?php echo strtolower($link['title']); ?>">
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($link['title']); ?></td>
                                    <td><?php echo htmlspecialchars($link['description']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($link['date'])); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-external-link-alt"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Forms Section -->
            <div class="section-container">
                <div class="section-header">
                    <h2><i class="fas fa-file-alt"></i> Forms</h2>
                    <button class="btn btn-primary" onclick="openFormModal()">
                        <i class="fas fa-plus"></i> Tambah Form
                    </button>
                </div>
                
                <div class="filter-bar">
                    <input type="text" id="searchForms" placeholder="Cari form..." onkeyup="filterForms()">
                    <div></div>
                    <button class="btn btn-secondary" onclick="resetFormsFilter()">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
                
                <?php if (empty($forms)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>Belum Ada Form</h3>
                        <p>Tambahkan form pertama dengan klik tombol "Tambah Form"</p>
                    </div>
                <?php else: ?>
                    <table id="formsTable">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th>Deskripsi</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="formsTableBody">
                            <?php foreach ($forms as $form): ?>
                                <tr class="form-row" data-title="<?php echo strtolower($form['title']); ?>">
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($form['title']); ?></td>
                                    <td><?php echo htmlspecialchars($form['description']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($form['date'])); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($form['url']); ?>" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-external-link-alt"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <?php include __DIR__ . '/../../includes/footer.php'; ?>
        </div>
    </div>
    
    <!-- Upload File Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-cloud-upload-alt"></i> Upload File</h3>
                <button class="close" onclick="closeUploadModal()">&times;</button>
            </div>
            <div class="modal-body">
                <iframe id="uploadFrame" style="width: 100%; height: 500px; border: none;"></iframe>
            </div>
        </div>
    </div>
    
    <!-- Add Link Modal -->
    <div id="linkModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-link"></i> Tambah Link</h3>
                <button class="close" onclick="closeLinkModal()">&times;</button>
            </div>
            <div class="modal-body">
                <iframe id="linkFrame" style="width: 100%; height: 500px; border: none;"></iframe>
            </div>
        </div>
    </div>
    
    <!-- Add Form Modal -->
    <div id="formModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-alt"></i> Tambah Form</h3>
                <button class="close" onclick="closeFormModal()">&times;</button>
            </div>
            <div class="modal-body">
                <iframe id="formFrame" style="width: 100%; height: 500px; border: none;"></iframe>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <script>
        // Modal functions
        function openUploadModal() {
            document.getElementById('uploadFrame').src = '<?php echo BASE_URL; ?>/pages/files/upload.php?category=<?php echo $categoryKey; ?>&modal=1';
            document.getElementById('uploadModal').style.display = 'block';
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
            document.getElementById('uploadFrame').src = '';
        }
        
        function openLinkModal() {
            document.getElementById('linkFrame').src = '<?php echo BASE_URL; ?>/pages/links/add.php?category=<?php echo $categoryKey; ?>&modal=1';
            document.getElementById('linkModal').style.display = 'block';
        }
        
        function closeLinkModal() {
            document.getElementById('linkModal').style.display = 'none';
            document.getElementById('linkFrame').src = '';
        }
        
        function openFormModal() {
            document.getElementById('formFrame').src = '<?php echo BASE_URL; ?>/pages/forms/add.php?category=<?php echo $categoryKey; ?>&modal=1';
            document.getElementById('formModal').style.display = 'block';
        }
        
        function closeFormModal() {
            document.getElementById('formModal').style.display = 'none';
            document.getElementById('formFrame').src = '';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Filter functions
        function filterFiles() {
            const search = document.getElementById('searchFiles').value.toLowerCase();
            const rows = document.querySelectorAll('.file-row');
            
            rows.forEach(row => {
                const name = row.dataset.name;
                row.style.display = name.includes(search) ? '' : 'none';
            });
        }
        
        function sortFiles() {
            const select = document.getElementById('sortFiles');
            const [field, direction] = select.value.split('_');
            const tbody = document.getElementById('filesTableBody');
            const rows = Array.from(tbody.querySelectorAll('.file-row'));
            
            rows.sort((a, b) => {
                let aVal, bVal;
                
                if (field === 'name') {
                    aVal = a.dataset.name;
                    bVal = b.dataset.name;
                    return direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                } else if (field === 'modified') {
                    aVal = parseInt(a.dataset.modified);
                    bVal = parseInt(b.dataset.modified);
                    return direction === 'asc' ? aVal - bVal : bVal - aVal;
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }
        
        function resetFilesFilter() {
            document.getElementById('searchFiles').value = '';
            document.getElementById('sortFiles').value = 'modified_desc';
            filterFiles();
            sortFiles();
        }
        
        function filterLinks() {
            const search = document.getElementById('searchLinks').value.toLowerCase();
            const rows = document.querySelectorAll('.link-row');
            
            rows.forEach(row => {
                const title = row.dataset.title;
                row.style.display = title.includes(search) ? '' : 'none';
            });
        }
        
        function resetLinksFilter() {
            document.getElementById('searchLinks').value = '';
            filterLinks();
        }
        
        function filterForms() {
            const search = document.getElementById('searchForms').value.toLowerCase();
            const rows = document.querySelectorAll('.form-row');
            
            rows.forEach(row => {
                const title = row.dataset.title;
                row.style.display = title.includes(search) ? '' : 'none';
            });
        }
        
        function resetFormsFilter() {
            document.getElementById('searchForms').value = '';
            filterForms();
        }
        
        // Listen for iframe messages to refresh data
        window.addEventListener('message', function(e) {
            if (e.data === 'upload_success' || e.data === 'link_added' || e.data === 'form_added') {
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }
        });
    </script>
</body>
</html>
