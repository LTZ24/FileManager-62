<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/ajax_helpers.php';

requireLogin();

// Define folder categories
$categories = [
    'kesiswaan' => [
        'name' => 'Kesiswaan',
        'folder_id' => '1_tLtTv8pryxUN-_tqvyGD3UxDVU011GW',
        'icon' => 'users',
        'color' => '#3b82f6'
    ],
    'kurikulum' => [
        'name' => 'Kurikulum',
        'folder_id' => '1NUqiOB6bwE90xxBEj_C6DauiRztsmXle',
        'icon' => 'book',
        'color' => '#10b981'
    ],
    'sapras' => [
        'name' => 'Sapras & Humas',
        'folder_id' => '1IB2BeH-YAMA3Nt98uHZuXwx78-JjAxT2',
        'icon' => 'building',
        'color' => '#f59e0b'
    ],
    'tata_usaha' => [
        'name' => 'Tata Usaha',
        'folder_id' => '1irqRKtlBxMSnLzmiR6sMTeuvCcdJDy3I',
        'icon' => 'briefcase',
        'color' => '#8b5cf6'
    ]
];

$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
$isModal = isset($_GET['modal']) && $_GET['modal'] == '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/smk62.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/ajax.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        .form-container {
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--light-color);
        }
        
        .form-header i {
            font-size: 3.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: block;
        }
        
        .form-header h2 {
            font-size: 1.5rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }
        
        .form-group label i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            width: 18px;
        }
        
        .category-selector {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        .category-card {
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--white);
        }
        
        .category-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        
        .category-card.selected {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.08);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        
        .category-card i {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .category-card .category-name {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.8rem;
        }
        
        .file-upload-area {
            border: 3px dashed var(--border-color);
            border-radius: 0.75rem;
            padding: 2.5rem 2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            background: var(--light-color);
        }
        
        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.1);
            transform: scale(1.01);
        }
        
        .file-upload-area i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
        }
        
        .file-upload-area p {
            color: var(--secondary-color);
            margin: 0.25rem 0;
        }
        
        .file-upload-area .upload-title {
            font-weight: 600;
            font-size: 1rem;
            color: var(--dark-color);
        }
        
        .file-upload-area .upload-subtitle {
            font-size: 0.85rem;
        }
        
        .file-upload-area .upload-limit {
            font-size: 0.8rem;
            color: var(--secondary-color);
            margin-top: 0.5rem;
        }
        
        .file-upload-area input[type="file"] {
            display: none;
        }
        
        .selected-files {
            margin-top: 1rem;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .selected-file-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .selected-file-item .file-icon {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            border-radius: 6px;
            color: var(--primary-color);
        }
        
        .selected-file-item .file-info {
            flex: 1;
            min-width: 0;
        }
        
        .selected-file-item .file-name {
            font-weight: 500;
            font-size: 0.875rem;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .selected-file-item .file-size {
            font-size: 0.75rem;
            color: #64748b;
        }
        
        .selected-file-item .remove-file {
            background: #fee2e2;
            border: none;
            color: #ef4444;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .selected-file-item .remove-file:hover {
            background: #ef4444;
            color: white;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--light-color);
        }
        
        .form-actions button,
        .form-actions a {
            flex: 1;
            padding: 0.875rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 0.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s;
            text-decoration: none;
        }
        
        .upload-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }
        
        .upload-stat {
            text-align: center;
        }
        
        .upload-stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .upload-stat-label {
            font-size: 0.75rem;
            color: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .category-selector {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .form-container { padding: 1.25rem; }
            .category-card { padding: 0.75rem; }
            .category-card i { font-size: 1.5rem; }
            .category-card .category-name { font-size: 0.75rem; }
        }
    </style>
</head>
<body<?php echo $isModal ? ' style="background: white;"' : ''; ?>>
    <?php if (!$isModal): ?>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <?php include __DIR__ . '/../../includes/page-navigation.php'; ?>
    <?php else: ?>
    <div style="padding: 1.5rem;">
    <?php endif; ?>
            
            <div class="form-container">
                <div class="form-header">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h2>Upload File ke Google Drive</h2>
                    <p>Pilih kategori dan upload file (maksimal 15 file, 100MB per file)</p>
                </div>
                
                <?php if (!$selectedCategory): ?>
                <div class="form-group">
                    <label>
                        <i class="fas fa-folder"></i> Pilih Kategori
                    </label>
                    <div class="category-selector">
                        <?php foreach ($categories as $key => $cat): ?>
                            <div class="category-card" 
                                 onclick="selectCategory('<?php echo $key; ?>', this)"
                                 data-category="<?php echo $key; ?>">
                                <i class="fas fa-<?php echo $cat['icon']; ?>" 
                                   style="color: <?php echo $cat['color']; ?>"></i>
                                <div class="category-name"><?php echo $cat['name']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="categoryInput" value="">
                </div>
                <?php else: ?>
                <input type="hidden" id="categoryInput" value="<?php echo htmlspecialchars($selectedCategory); ?>">
                <div class="alert alert-info" style="margin-bottom: 1.5rem;">
                    <i class="fas fa-info-circle"></i>
                    Upload ke kategori: <strong><?php echo htmlspecialchars($categories[$selectedCategory]['name'] ?? $selectedCategory); ?></strong>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-file"></i> Pilih File (Multiple)
                    </label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p class="upload-title">Klik atau drag & drop file di sini</p>
                        <p class="upload-subtitle">Mendukung semua jenis file termasuk dokumen, gambar, video, installer, dll</p>
                        <p class="upload-limit">Maksimal 15 file • 100MB per file</p>
                        <input type="file" id="fileInput" multiple>
                    </div>
                    <div class="selected-files" id="selectedFiles"></div>
                    <div class="upload-stats" id="uploadStats" style="display: none;">
                        <div class="upload-stat">
                            <div class="upload-stat-value" id="fileCount">0</div>
                            <div class="upload-stat-label">File Dipilih</div>
                        </div>
                        <div class="upload-stat">
                            <div class="upload-stat-value" id="totalSize">0 MB</div>
                            <div class="upload-stat-label">Total Ukuran</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" id="uploadBtn" onclick="startUpload()" disabled>
                        <i class="fas fa-cloud-upload-alt"></i> Upload File
                    </button>
                    <?php if (!$isModal): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/files" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <?php else: ?>
                    <button type="button" class="btn btn-secondary" onclick="parent.closeUploadModal && parent.closeUploadModal()">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!$isModal): ?>
            <?php include __DIR__ . '/../../includes/footer.php'; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/upload-manager.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script>
        const baseUrl = '<?php echo BASE_URL; ?>';
        const fileInput = document.getElementById('fileInput');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const selectedFilesContainer = document.getElementById('selectedFiles');
        const uploadStats = document.getElementById('uploadStats');
        const uploadBtn = document.getElementById('uploadBtn');
        const categoryInput = document.getElementById('categoryInput');
        
        let selectedFiles = [];
        const MAX_FILES = 15;
        const MAX_SIZE = 100 * 1024 * 1024; // 100MB
        
        // Initialize upload manager
        window.uploadManager = new UploadManager({
            baseUrl: baseUrl,
            maxFiles: MAX_FILES,
            maxFileSize: MAX_SIZE,
            onComplete: (queue) => {
                const successCount = queue.filter(i => i.status === 'success').length;
                if (successCount > 0) {
                    // Clear selected files after upload
                    selectedFiles = [];
                    renderSelectedFiles();
                    
                    // Redirect after delay if not in modal
                    <?php if (!$isModal): ?>
                    setTimeout(() => {
                        window.location.href = baseUrl + '/pages/files';
                    }, 2000);
                    <?php else: ?>
                    setTimeout(() => {
                        if (parent.closeUploadModal) parent.closeUploadModal();
                        if (parent.reloadFilesTable) parent.reloadFilesTable();
                    }, 1500);
                    <?php endif; ?>
                }
            }
        });
        
        // Click to upload
        fileUploadArea.addEventListener('click', () => fileInput.click());
        
        // File selected
        fileInput.addEventListener('change', (e) => {
            addFiles(Array.from(e.target.files));
            fileInput.value = ''; // Reset for next selection
        });
        
        // Drag and drop
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            addFiles(Array.from(e.dataTransfer.files));
        });
        
        function addFiles(files) {
            for (const file of files) {
                if (selectedFiles.length >= MAX_FILES) {
                    alert(`Maksimal ${MAX_FILES} file dalam satu batch`);
                    break;
                }
                
                if (file.size > MAX_SIZE) {
                    alert(`File "${file.name}" melebihi batas 100MB`);
                    continue;
                }
                
                // Check for duplicates
                if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    continue;
                }
                
                selectedFiles.push(file);
            }
            
            renderSelectedFiles();
            checkFormValid();
        }
        
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            renderSelectedFiles();
            checkFormValid();
        }
        
        function renderSelectedFiles() {
            if (selectedFiles.length === 0) {
                selectedFilesContainer.innerHTML = '';
                uploadStats.style.display = 'none';
                return;
            }
            
            let html = '';
            let totalSize = 0;
            
            selectedFiles.forEach((file, index) => {
                totalSize += file.size;
                const icon = getFileIcon(file.name);
                html += `
                    <div class="selected-file-item">
                        <div class="file-icon"><i class="fas ${icon}"></i></div>
                        <div class="file-info">
                            <div class="file-name">${escapeHtml(file.name)}</div>
                            <div class="file-size">${formatFileSize(file.size)}</div>
                        </div>
                        <button type="button" class="remove-file" onclick="removeFile(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });
            
            selectedFilesContainer.innerHTML = html;
            
            document.getElementById('fileCount').textContent = selectedFiles.length;
            document.getElementById('totalSize').textContent = formatFileSize(totalSize);
            uploadStats.style.display = 'flex';
        }
        
        function selectCategory(category, element) {
            document.querySelectorAll('.category-card').forEach(card => {
                card.classList.remove('selected');
            });
            element.classList.add('selected');
            categoryInput.value = category;
            checkFormValid();
        }
        
        function checkFormValid() {
            const hasFiles = selectedFiles.length > 0;
            const hasCategory = categoryInput.value !== '';
            uploadBtn.disabled = !(hasFiles && hasCategory);
        }
        
        function startUpload() {
            const category = categoryInput.value;
            if (!category) {
                alert('Pilih kategori terlebih dahulu');
                return;
            }
            
            if (selectedFiles.length === 0) {
                alert('Pilih file terlebih dahulu');
                return;
            }
            
            // Add files to upload manager (auto-hides panel)
            window.uploadManager.addFiles(selectedFiles, category);
            
            // Clear selected files from form (upload manager has them now)
            selectedFiles = [];
            renderSelectedFiles();
            
            <?php if ($isModal): ?>
            // Close modal after starting upload
            setTimeout(() => {
                if (parent.closeUploadModal) parent.closeUploadModal();
            }, 300);
            <?php endif; ?>
        }
        
        function getFileIcon(filename) {
            const ext = filename.split('.').pop().toLowerCase();
            const iconMap = {
                'pdf': 'fa-file-pdf', 'doc': 'fa-file-word', 'docx': 'fa-file-word',
                'xls': 'fa-file-excel', 'xlsx': 'fa-file-excel',
                'ppt': 'fa-file-powerpoint', 'pptx': 'fa-file-powerpoint',
                'jpg': 'fa-file-image', 'jpeg': 'fa-file-image', 'png': 'fa-file-image', 'gif': 'fa-file-image',
                'mp4': 'fa-file-video', 'avi': 'fa-file-video', 'mkv': 'fa-file-video', 'mov': 'fa-file-video',
                'mp3': 'fa-file-audio', 'wav': 'fa-file-audio',
                'zip': 'fa-file-archive', 'rar': 'fa-file-archive', '7z': 'fa-file-archive',
                'exe': 'fa-cog', 'msi': 'fa-cog', 'apk': 'fa-android', 'dmg': 'fa-apple-alt'
            };
            return iconMap[ext] || 'fa-file';
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Auto-select category if provided
        <?php if ($selectedCategory): ?>
        document.addEventListener('DOMContentLoaded', checkFormValid);
        <?php endif; ?>
    </script>
    <?php if (!$isModal): ?>
</body>
</html>
    <?php else: ?>
</div>
</body>
</html>
    <?php endif; ?>
