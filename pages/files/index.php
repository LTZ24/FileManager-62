<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';

requireLogin();

// Lazy loading mode - no API calls on initial page load
$lazyLoad = isset($_GET['lazy']) ? $_GET['lazy'] === '1' : true;
$cacheTime = 300;
$cacheKey = 'files_cache';

// Only fetch files if not lazy loading
$files = [];
$cacheStatus = "lazy_mode";

if (!$lazyLoad) {
    function getFilesFromDrive() {
        try {
            $driveService = getDriveService();
            
            // Use centralized category definitions
        $categories = getDriveCategories();
        
        // Map category key to URL parameter
        $keyToParam = [
            'kesiswaan' => 'kesiswaan',
            'kurikulum' => 'kurikulum',
            'sapras' => 'sapras-humas',
            'tata_usaha' => 'tata-usaha'
            , 'dokumentasi' => 'dokumentasi'
        ];
        
        $allFiles = [];
        
        foreach ($categories as $key => $category) {
            $folderId = $category['folder_id'];
            
            $parameters = [
                'q' => "'{$folderId}' in parents and trashed=false",
                'fields' => 'files(id, name, mimeType, size, createdTime, modifiedTime, webViewLink, iconLink, thumbnailLink)',
                'orderBy' => 'modifiedTime desc',
                'pageSize' => 1000,
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true
            ];
            
            $results = $driveService->files->listFiles($parameters);
            $files = $results->getFiles();
            
            // Extract icon name without 'fa-' prefix for template
            $iconName = str_replace('fa-', '', $category['icon']);
            
            foreach ($files as $file) {
                $allFiles[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'mimeType' => $file->getMimeType(),
                    'size' => $file->getSize() ?? 0,
                    'createdTime' => $file->getCreatedTime(),
                    'modifiedTime' => $file->getModifiedTime(),
                    'webViewLink' => $file->getWebViewLink(),
                    'iconLink' => $file->getIconLink(),
                    'thumbnailLink' => $file->getThumbnailLink(),
                    'category' => $key,
                    'categoryParam' => $keyToParam[$key] ?? $key,
                    'categoryName' => $category['name'],
                    'categoryIcon' => $iconName,
                    'categoryColor' => $category['color']
                ];
            }
        }
        
        return $allFiles;
    } catch (Exception $e) {
        error_log('getFilesFromDrive error: ' . $e->getMessage());
        return [];
    }
    }

    // Clear cache if needed
    if (isset($_GET['clear_cache'])) {
        unset($_SESSION[$cacheKey]);
        unset($_SESSION[$cacheKey . '_time']);
        header('Location: ' . BASE_URL . '/pages/files/');
        exit;
    }

    // Force refresh if cache might have old format (missing categoryParam)
    if (isset($_SESSION[$cacheKey]) && is_array($_SESSION[$cacheKey]) && !empty($_SESSION[$cacheKey])) {
        $firstFile = reset($_SESSION[$cacheKey]);
        if (!isset($firstFile['categoryParam'])) {
            unset($_SESSION[$cacheKey]);
            unset($_SESSION[$cacheKey . '_time']);
        }
    }

    // Get files with cache
    if (isset($_SESSION[$cacheKey]) && 
        isset($_SESSION[$cacheKey . '_time']) && 
        (time() - $_SESSION[$cacheKey . '_time']) < $cacheTime) {
        $files = $_SESSION[$cacheKey];
        $cacheStatus = "from_cache";
    } else {
        $files = getFilesFromDrive();
        $_SESSION[$cacheKey] = $files;
        $_SESSION[$cacheKey . '_time'] = time();
        $cacheStatus = "fresh_fetch";
    }
}

// Ensure $files is always an array
if (!isset($files) || !is_array($files)) {
    $files = [];
}
 
// Get category from URL parameter
$categoryParam = isset($_GET['category']) ? $_GET['category'] : null;
$categoryMap = [
    'kesiswaan' => 'kesiswaan',
    'kurikulum' => 'kurikulum',
    'sapras-humas' => 'sapras',
    'tata-usaha' => 'tata_usaha'
    , 'dokumentasi' => 'dokumentasi'
];
$selectedCategory = isset($categoryMap[$categoryParam]) ? $categoryMap[$categoryParam] : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager - <?php echo APP_NAME; ?></title>
    
    <!-- Resource Hints for Faster Loading -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://www.googleapis.com" crossorigin>
    <link rel="dns-prefetch" href="https://drive.google.com">
    
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/smk62.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/ajax.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    <style>
        /* Skeleton Loader for Lazy Loading */
        .skeleton-row {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .skeleton-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            border-radius: 0.5rem;
            margin-right: 0.75rem;
            animation: shimmer 1.5s infinite;
        }
        .skeleton-text {
            flex: 1;
        }
        .skeleton-line {
            height: 12px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            border-radius: 4px;
            animation: shimmer 1.5s infinite;
        }
        .skeleton-line.short { width: 60%; }
        .skeleton-line.medium { width: 80%; margin-top: 6px; }
        .skeleton-actions {
            display: flex;
            gap: 0.5rem;
        }
        .skeleton-btn {
            width: 36px;
            height: 36px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            border-radius: 0.5rem;
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        .loading-overlay {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }
        .loading-overlay i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .files-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            border: 1px solid #f3f4f6;
        }
        
        .files-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .filter-controls {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            border: 1px solid #e5e7eb;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--dark-color);
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .category-filters {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        
        .category-filter-dropdown {
            display: none;
            width: 100%;
            max-width: 100%;
            margin-bottom: 1.5rem;
            box-sizing: border-box;
        }
        
        .category-filter-dropdown select {
            width: 100%;
            max-width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            font-size: 0.95rem;
            font-weight: 500;
            background: white;
            color: var(--dark-color);
            cursor: pointer;
            transition: all 0.3s;
            box-sizing: border-box;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M10.293 3.293L6 7.586 1.707 3.293A1 1 0 00.293 4.707l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
        }
        
        .category-filter-dropdown select:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .category-filter-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark-color);
        }
        
        .category-filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .category-filter-btn.active {
            border-color: #3b82f6;
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .files-table {
            width: 100%;
            overflow-x: auto;
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
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        
        thead th:hover {
            background: var(--border-color);
        }
        
        thead th i {
            margin-left: 0.5rem;
            opacity: 0.5;
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
            font-size: 0.9rem;
        }
        
        .file-name-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .file-name-cell .file-details {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }

        .file-name-cell .file-meta {
            display: none;
            font-size: 0.7rem;
            color: #64748b;
        }
        
        .file-icon {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
        }
        
        .file-name {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .category-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--white);
        }
        
        .file-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--secondary-color);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .category-filters {
                display: none; /* Hide button filters on mobile */
            }
            
            .category-filter-dropdown {
                display: block; /* Show dropdown on mobile */
                padding: 0 0.5rem;
            }
            
            .filter-controls {
                grid-template-columns: 1fr;
                padding: 0 0.5rem;
            }
            
            .files-header {
                flex-direction: column;
                align-items: stretch;
                padding: 0 0.5rem;
            }

            /* Hide columns on mobile */
            table th.hide-mobile,
            table td.hide-mobile {
                display: none;
            }

            /* Show file-meta on mobile */
            .file-name-cell .file-meta {
                display: flex;
            }

            /* Smaller table text */
            tbody td {
                padding: 0.75rem 0.5rem;
                font-size: 0.8rem;
            }

            /* Compact action buttons */
            .btn-icon {
                width: 32px;
                height: 32px;
            }

            .file-actions {
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../../includes/header.php'; ?>
        
        <div class="content-wrapper">
            <?php include __DIR__ . '/../../includes/page-navigation.php'; ?>
            
            <div class="files-header">
                <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/pages/files/upload">
                    <i class="fas fa-cloud-upload-alt"></i> Upload File
                </a>
            </div>
            
            <div class="files-container">
                <?php if (!$selectedCategory): ?>
                <!-- Mobile Category Dropdown -->
                <div class="category-filter-dropdown">
                    <select onchange="filterByCategory(this.value)">
                        <option value="all">Semua Kategori</option>
                        <option value="kesiswaan">Kesiswaan</option>
                        <option value="kurikulum">Kurikulum</option>
                        <option value="sapras">Sapras & Humas</option>
                        <option value="tata_usaha">Tata Usaha</option>
                        <option value="dokumentasi">Dokumentasi</option>
                    </select>
                </div>
                
                <!-- Desktop Category Buttons -->
                <div class="category-filters">
                    <button class="category-filter-btn active" data-category="all" onclick="filterByCategory('all')">
                        <i class="fas fa-th"></i> Semua Kategori
                    </button>
                    <button class="category-filter-btn" data-category="kesiswaan" onclick="filterByCategory('kesiswaan')" data-color="#3b82f6">
                        <i class="fas fa-users"></i> Kesiswaan
                    </button>
                    <button class="category-filter-btn" data-category="kurikulum" onclick="filterByCategory('kurikulum')" data-color="#10b981">
                        <i class="fas fa-book"></i> Kurikulum
                    </button>
                    <button class="category-filter-btn" data-category="sapras" onclick="filterByCategory('sapras')" data-color="#f59e0b">
                        <i class="fas fa-building"></i> Sapras & Humas
                    </button>
                    <button class="category-filter-btn" data-category="tata_usaha" onclick="filterByCategory('tata_usaha')" data-color="#8b5cf6">
                        <i class="fas fa-briefcase"></i> Tata Usaha
                    </button>
                    <button class="category-filter-btn" data-category="dokumentasi" onclick="filterByCategory('dokumentasi')" data-color="#f97316">
                        <i class="fas fa-photo-video"></i> Dokumentasi
                    </button>
                </div>
                <?php else: ?>
                <!-- Breadcrumb for filtered category -->
                <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; padding: 1rem; background: #eff6ff; border-radius: 0.75rem; border: 2px solid #3b82f6;">
                    <a href="<?php echo BASE_URL; ?>/pages/files/" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <span style="font-weight: 600; color: var(--dark-color); font-size: 1.1rem;">
                        <i class="fas fa-filter"></i> Filter: 
                        <?php 
                        $categoryNames = [
                            'kesiswaan' => 'Kesiswaan',
                            'kurikulum' => 'Kurikulum',
                            'sapras' => 'Sapras & Humas',
                            'tata_usaha' => 'Tata Usaha'
                                ,
                                'dokumentasi' => 'Dokumentasi'
                        ];
                        echo $categoryNames[$selectedCategory];
                        ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="filter-controls">
                    <div class="filter-group">
                        <label><i class="fas fa-search"></i> Cari File</label>
                        <input type="text" id="searchInput" placeholder="Cari nama file..." onkeyup="filterFiles()">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-sort"></i> Urutkan</label>
                        <select id="sortSelect" onchange="sortFiles()">
                            <option value="modified_desc">Terakhir Diubah (Terbaru)</option>
                            <option value="modified_asc">Terakhir Diubah (Terlama)</option>
                            <option value="name_asc">Nama (A-Z)</option>
                            <option value="name_desc">Nama (Z-A)</option>
                            <option value="size_desc">Ukuran (Terbesar)</option>
                            <option value="size_asc">Ukuran (Terkecil)</option>
                            <option value="created_desc">Tanggal Upload (Terbaru)</option>
                            <option value="created_asc">Tanggal Upload (Terlama)</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> Filter Tanggal</label>
                        <input type="date" id="dateFilter" onchange="filterFiles()">
                    </div>
                    <div class="filter-group" style="display: flex; align-items: flex-end;">
                        <button class="btn btn-secondary" onclick="resetFilters()" style="width: 100%;">
                            <i class="fas fa-redo"></i> Reset Filter
                        </button>
                    </div>
                </div>
                
                <div class="files-table">
                    <?php if ($lazyLoad): ?>
                        <!-- Skeleton Loader for Lazy Loading -->
                        <div id="skeletonLoader">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nama File <i class="fas fa-sort"></i></th>
                                        <th class="hide-mobile">Kategori <i class="fas fa-sort"></i></th>
                                        <th class="hide-mobile">Ukuran <i class="fas fa-sort"></i></th>
                                        <th class="hide-mobile">Terakhir Diubah <i class="fas fa-sort"></i></th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                    <tr class="skeleton-row">
                                        <td>
                                            <div class="file-name-cell">
                                                <div class="skeleton-icon"></div>
                                                <div class="skeleton-text">
                                                    <div class="skeleton-line short"></div>
                                                    <div class="skeleton-line medium" style="width: 40%; height: 8px;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="hide-mobile"><div class="skeleton-line" style="width: 80px; height: 24px; border-radius: 1rem;"></div></td>
                                        <td class="hide-mobile"><div class="skeleton-line" style="width: 60px;"></div></td>
                                        <td class="hide-mobile"><div class="skeleton-line" style="width: 80px;"></div></td>
                                        <td><div class="skeleton-actions"><div class="skeleton-btn"></div><div class="skeleton-btn"></div><div class="skeleton-btn"></div></div></td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Real Table (hidden initially, shown after data loads) -->
                        <table id="filesTable" style="display: none;">
                            <thead>
                                <tr>
                                    <th onclick="sortTable('name')">Nama File <i class="fas fa-sort"></i></th>
                                    <th class="hide-mobile" onclick="sortTable('category')">Kategori <i class="fas fa-sort"></i></th>
                                    <th class="hide-mobile" onclick="sortTable('size')">Ukuran <i class="fas fa-sort"></i></th>
                                    <th class="hide-mobile" onclick="sortTable('modified')">Terakhir Diubah <i class="fas fa-sort"></i></th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="filesTableBody"></tbody>
                        </table>
                        <div id="noResults" class="empty-state" style="display: none;">
                            <i class="fas fa-search"></i>
                            <h3>Tidak Ada Hasil</h3>
                            <p>Tidak ada file yang sesuai dengan filter Anda</p>
                        </div>
                    <?php elseif (empty($files)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>Belum Ada File</h3>
                            <p>Upload file pertama Anda dengan klik tombol "Upload File"</p>
                        </div>
                    <?php else: ?>
                        <table id="filesTable">
                            <thead>
                                <tr>
                                    <th onclick="sortTable('name')">
                                        Nama File <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="hide-mobile" onclick="sortTable('category')">
                                        Kategori <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="hide-mobile" onclick="sortTable('size')">
                                        Ukuran <i class="fas fa-sort"></i>
                                    </th>
                                    <th class="hide-mobile" onclick="sortTable('modified')">
                                        Terakhir Diubah <i class="fas fa-sort"></i>
                                    </th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="filesTableBody">
                                <?php foreach ($files as $file): ?>
                                    <tr class="file-row" 
                                        data-category="<?php echo $file['category']; ?>"
                                        data-name="<?php echo strtolower($file['name']); ?>"
                                        data-size="<?php echo $file['size']; ?>"
                                        data-modified="<?php echo strtotime($file['modifiedTime']); ?>"
                                        data-created="<?php echo strtotime($file['createdTime']); ?>"
                                        data-date="<?php echo date('Y-m-d', strtotime($file['modifiedTime'])); ?>">
                                        <td>
                                            <div class="file-name-cell">
                                                <img src="<?php echo $file['iconLink']; ?>" alt="" class="file-icon">
                                                <div class="file-details">
                                                    <span class="file-name"><?php echo htmlspecialchars($file['name']); ?></span>
                                                    <span class="file-meta"><?php echo formatFileSize($file['size']); ?> • <?php echo formatDateTime($file['modifiedTime']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="hide-mobile">
                                            <span class="category-badge" style="background: <?php echo $file['categoryColor']; ?>">
                                                <i class="fas fa-<?php echo $file['categoryIcon']; ?>"></i>
                                                <?php echo $file['categoryName']; ?>
                                            </span>
                                        </td>
                                        <td class="hide-mobile"><?php echo formatFileSize($file['size']); ?></td>
                                        <td class="hide-mobile"><?php echo formatDateTime($file['modifiedTime']); ?></td>
                                        <td>
                                            <div class="file-actions">
                                                <a href="<?php echo $file['webViewLink']; ?>" 
                                                   target="_blank" 
                                                   class="btn btn-primary btn-icon" 
                                                   title="Lihat">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <!-- download removed per request -->
                                                                <button type="button"
                                                                    class="btn btn-danger btn-icon"
                                                                    title="Hapus"
                                                                    onclick='deleteDriveFile(<?php echo json_encode($file['id']); ?>, <?php echo json_encode($file['name']); ?>, <?php echo json_encode($file['categoryParam']); ?>)'>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div id="noResults" class="empty-state" style="display: none;">
                            <i class="fas fa-search"></i>
                            <h3>Tidak Ada Hasil</h3>
                            <p>Tidak ada file yang sesuai dengan filter Anda</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php include __DIR__ . '/../../includes/footer.php'; ?>
        </div>
    </div>

            <!-- Upload Modal -->
            <div id="uploadModal" class="modal" style="display:none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
                <div class="modal-content" style="background: #fff; margin: 3% auto; padding: 0; border-radius: 0.75rem; width: 95%; max-width: 700px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.25);">
                    <div class="modal-header" style="display:flex; justify-content: space-between; align-items:center; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
                        <h3 style="margin:0; font-size: 1.1rem;"><i class="fas fa-cloud-upload-alt"></i> Upload File (Batch)</h3>
                        <button class="close" onclick="closeUploadModal()" style="background:rgba(255,255,255,0.2); border:none; font-size:1.25rem; cursor:pointer; color:white; width:32px; height:32px; border-radius:6px;">&times;</button>
                    </div>
                    <div class="modal-body" style="padding: 1.5rem;">
                        <div id="uploadAlert" style="display:none; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.25rem; font-size: 0.875rem;"></div>
                        
                        <div class="form-group" style="margin-bottom: 1.25rem;">
                            <label style="display:block; margin-bottom:0.5rem; font-weight:600; color:#334155; font-size:0.875rem;"><i class="fas fa-layer-group" style="color:#3b82f6; margin-right:0.5rem;"></i> Kategori</label>
                            <select id="uploadCategory" name="category" required style="width:100%; padding:0.75rem; border:2px solid #e2e8f0; border-radius:0.5rem; font-size:0.875rem; background:white;">
                                <option value="" disabled selected>Pilih Kategori</option>
                                <option value="kesiswaan" <?php echo ($selectedCategory === 'kesiswaan') ? 'selected' : ''; ?>>Kesiswaan</option>
                                <option value="kurikulum" <?php echo ($selectedCategory === 'kurikulum') ? 'selected' : ''; ?>>Kurikulum</option>
                                <option value="sapras" <?php echo ($selectedCategory === 'sapras') ? 'selected' : ''; ?>>Sapras &amp; Humas</option>
                                <option value="tata_usaha" <?php echo ($selectedCategory === 'tata_usaha') ? 'selected' : ''; ?>>Tata Usaha</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label style="display:block; margin-bottom:0.5rem; font-weight:600; color:#334155; font-size:0.875rem;"><i class="fas fa-file" style="color:#3b82f6; margin-right:0.5rem;"></i> Pilih File (Multiple)</label>
                            <div id="modalFileUploadArea" style="border: 3px dashed #e2e8f0; border-radius: 0.75rem; padding: 2rem; text-align: center; cursor: pointer; background: #f8fafc; transition: all 0.2s;">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #3b82f6; margin-bottom: 0.5rem;"></i>
                                <p style="font-weight: 600; color: #1e293b; margin: 0.25rem 0;">Klik atau drag & drop file</p>
                                <p style="color: #64748b; font-size: 0.8rem; margin: 0.25rem 0;">Maks 15 file • 100MB per file</p>
                                <input type="file" id="modalFileInput" multiple style="display: none;">
                            </div>
                            <div id="modalSelectedFiles" style="margin-top: 0.75rem; max-height: 150px; overflow-y: auto;"></div>
                            <div id="modalUploadStats" style="display: none; background: #f8fafc; border-radius: 0.5rem; padding: 0.5rem; margin-top: 0.75rem; text-align: center;">
                                <span id="modalFileCount" style="font-weight: 600; color: #3b82f6;">0</span>
                                <span style="color: #64748b; font-size: 0.8rem;"> file dipilih • </span>
                                <span id="modalTotalSize" style="font-weight: 600; color: #3b82f6;">0 MB</span>
                            </div>
                        </div>

                        <div style="display:flex; gap:0.75rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                            <button id="uploadSubmitBtn" type="button" class="btn btn-primary" onclick="startModalUpload()" disabled style="flex:1; padding:0.75rem 1.25rem; font-size:0.875rem; font-weight:600;">
                                <i class="fas fa-upload"></i> Upload File
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="closeUploadModal()" style="flex:1; padding:0.75rem 1.25rem; font-size:0.875rem; font-weight:600;">
                                <i class="fas fa-times"></i> Batal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/table-pagination.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/upload-manager.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/ajax.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script>
        const FILE_DELETE_URL = <?php echo json_encode(BASE_URL . '/pages/files/delete'); ?>;
        const FILES_DATA_URL = <?php echo json_encode(BASE_URL . '/api/files-data'); ?>;
        const UPLOAD_URL = <?php echo json_encode(BASE_URL . '/pages/files/upload'); ?>;
        const SELECTED_CATEGORY = <?php echo json_encode($selectedCategory); ?>;
        const LAZY_LOAD = <?php echo $lazyLoad ? 'true' : 'false'; ?>;

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        async function fetchJson(url, options = {}) {
            if (options && options.body && (options.body instanceof FormData)) {
                if (window.APP_CSRF_TOKEN && !options.body.has('csrf_token')) {
                    options.body.append('csrf_token', window.APP_CSRF_TOKEN);
                }
            }

            const headers = Object.assign(
                { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': (window.APP_CSRF_TOKEN || '') },
                options.headers || {}
            );
            const res = await fetch(url, Object.assign({}, options, { headers }));
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

        function renderFilesTable(files) {
            const tbody = document.getElementById('filesTableBody');
            if (!tbody) return;

            const rowsHtml = (files || []).map(file => {
                const name = escapeHtml(file.name);
                const iconLink = escapeHtml(file.iconLink || '');
                const webViewLink = escapeHtml(file.webViewLink || '#');
                const categoryColor = escapeHtml(file.categoryColor || '#64748b');
                const categoryIcon = escapeHtml(file.categoryIcon || 'fa-folder');
                const categoryName = escapeHtml(file.categoryName || '-');

                const modifiedTs = file.modifiedTimestamp || 0;
                const createdTs = file.createdTimestamp || 0;
                const date = escapeHtml(file.date || '');

                return `
                    <tr class="file-row"
                        data-category="${escapeHtml(file.category || '')}"
                        data-name="${escapeHtml(String(file.name || '').toLowerCase())}"
                        data-size="${Number(file.size || 0)}"
                        data-modified="${Number(modifiedTs)}"
                        data-created="${Number(createdTs)}"
                        data-date="${date}">
                        <td>
                            <div class="file-name-cell">
                                <img src="${iconLink}" alt="" class="file-icon">
                                <div class="file-details">
                                    <span class="file-name">${name}</span>
                                    <span class="file-meta">${escapeHtml(file.sizeFormatted || '0 Bytes')} • ${escapeHtml(file.modifiedFormatted || '-')}
                                        <span class="file-meta-category hide-desktop">• ${categoryName}</span>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="hide-mobile">
                            <span class="category-badge" style="background: ${categoryColor}">
                                <i class="fas fa-${categoryIcon}"></i>
                                ${categoryName}
                            </span>
                        </td>
                        <td class="hide-mobile">${escapeHtml(file.sizeFormatted || '0 Bytes')}</td>
                        <td class="hide-mobile">${escapeHtml(file.modifiedFormatted || '-')}</td>
                        <td>
                            <div class="file-actions">
                                <a href="${webViewLink}" target="_blank" class="btn btn-primary btn-icon" title="Lihat"><i class="fas fa-eye"></i></a>
                                <!-- download button removed -->
                                <button type="button" class="btn btn-danger btn-icon" title="Hapus" onclick='deleteDriveFile(${JSON.stringify(file.id)}, ${JSON.stringify(file.name)}, ${JSON.stringify(file.categoryParam)})'>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            tbody.innerHTML = rowsHtml;

            const inst = window.tablePaginationInstances && window.tablePaginationInstances['filesTable'];
            if (inst && typeof inst.refresh === 'function') {
                inst.refresh();
            }
        }

        async function reloadFilesTable(page = null) {
            if (page !== null) filesPage = page;
            await fetchFilesPage(filesPage);
        }

        function showInlineAlert(el, message, ok) {
            el.style.display = 'block';
            el.style.background = ok ? '#d1fae5' : '#fee2e2';
            el.style.color = ok ? '#065f46' : '#991b1b';
            el.style.border = ok ? '1px solid #a7f3d0' : '1px solid #fecaca';
            el.innerHTML = (ok ? '<i class="fas fa-check-circle"></i> ' : '<i class="fas fa-exclamation-circle"></i> ') + escapeHtml(message);
        }

        // Modal batch upload state
        let modalSelectedFiles = [];
        const MODAL_MAX_FILES = 15;
        const MODAL_MAX_SIZE = 100 * 1024 * 1024; // 100MB

        function openUploadModal() {
            const modal = document.getElementById('uploadModal');
            if (modal) modal.style.display = 'block';

            const alertDiv = document.getElementById('uploadAlert');
            if (alertDiv) alertDiv.style.display = 'none';

            if (SELECTED_CATEGORY) {
                const sel = document.getElementById('uploadCategory');
                if (sel) sel.value = SELECTED_CATEGORY;
            }
            
            // Reset selected files
            modalSelectedFiles = [];
            renderModalSelectedFiles();
            checkModalFormValid();
            
            // Setup file input handlers
            setupModalFileHandlers();
        }

        function setupModalFileHandlers() {
            const fileInput = document.getElementById('modalFileInput');
            const uploadArea = document.getElementById('modalFileUploadArea');
            
            if (!fileInput || !uploadArea) return;
            
            // Click to upload
            uploadArea.onclick = () => fileInput.click();
            
            // File selected
            fileInput.onchange = (e) => {
                addModalFiles(Array.from(e.target.files));
                fileInput.value = '';
            };
            
            // Drag and drop
            uploadArea.ondragover = (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#3b82f6';
                uploadArea.style.background = 'rgba(59, 130, 246, 0.1)';
            };
            
            uploadArea.ondragleave = () => {
                uploadArea.style.borderColor = '#e2e8f0';
                uploadArea.style.background = '#f8fafc';
            };
            
            uploadArea.ondrop = (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#e2e8f0';
                uploadArea.style.background = '#f8fafc';
                addModalFiles(Array.from(e.dataTransfer.files));
            };
        }
        
        function addModalFiles(files) {
            for (const file of files) {
                if (modalSelectedFiles.length >= MODAL_MAX_FILES) {
                    alert('Maksimal ' + MODAL_MAX_FILES + ' file dalam satu batch');
                    break;
                }
                
                if (file.size > MODAL_MAX_SIZE) {
                    alert('File "' + file.name + '" melebihi batas 100MB');
                    continue;
                }
                
                // Check for duplicates
                if (modalSelectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    continue;
                }
                
                modalSelectedFiles.push(file);
            }
            
            renderModalSelectedFiles();
            checkModalFormValid();
        }
        
        function removeModalFile(index) {
            modalSelectedFiles.splice(index, 1);
            renderModalSelectedFiles();
            checkModalFormValid();
        }
        
        function renderModalSelectedFiles() {
            const container = document.getElementById('modalSelectedFiles');
            const statsDiv = document.getElementById('modalUploadStats');
            
            if (!container) return;
            
            if (modalSelectedFiles.length === 0) {
                container.innerHTML = '';
                if (statsDiv) statsDiv.style.display = 'none';
                return;
            }
            
            let html = '';
            let totalSize = 0;
            
            modalSelectedFiles.forEach((file, index) => {
                totalSize += file.size;
                html += '<div style="display:flex; align-items:center; gap:0.5rem; padding:0.5rem; background:#f8fafc; border-radius:0.375rem; margin-bottom:0.375rem; border:1px solid #e2e8f0;">' +
                    '<i class="fas fa-file" style="color:#3b82f6;"></i>' +
                    '<span style="flex:1; font-size:0.8rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + escapeHtml(file.name) + '</span>' +
                    '<span style="font-size:0.75rem; color:#64748b;">' + formatFileSize(file.size) + '</span>' +
                    '<button type="button" onclick="removeModalFile(' + index + ')" style="background:#fee2e2; border:none; color:#ef4444; width:24px; height:24px; border-radius:4px; cursor:pointer; font-size:0.75rem;"><i class="fas fa-times"></i></button>' +
                    '</div>';
            });
            
            container.innerHTML = html;
            
            if (statsDiv) {
                document.getElementById('modalFileCount').textContent = modalSelectedFiles.length;
                document.getElementById('modalTotalSize').textContent = formatFileSize(totalSize);
                statsDiv.style.display = 'block';
            }
        }
        
        function checkModalFormValid() {
            const hasFiles = modalSelectedFiles.length > 0;
            const category = document.getElementById('uploadCategory');
            const hasCategory = category && category.value !== '';
            const btn = document.getElementById('uploadSubmitBtn');
            if (btn) btn.disabled = !(hasFiles && hasCategory);
        }
        
        function startModalUpload() {
            const category = document.getElementById('uploadCategory').value;
            if (!category) {
                alert('Pilih kategori terlebih dahulu');
                return;
            }
            
            if (modalSelectedFiles.length === 0) {
                alert('Pilih file terlebih dahulu');
                return;
            }
            
            // Initialize upload manager if not exists
            if (!window.uploadManager) {
                window.uploadManager = new UploadManager({
                    baseUrl: '<?php echo BASE_URL; ?>',
                    maxFiles: MODAL_MAX_FILES,
                    maxFileSize: MODAL_MAX_SIZE,
                    onComplete: async (queue) => {
                        const successCount = queue.filter(i => i.status === 'success').length;
                        if (successCount > 0) {
                            try {
                                await reloadFilesTable();
                            } catch (e) {
                                window.location.reload();
                            }
                        }
                    }
                });
            }
            
            // Add files to upload manager
            window.uploadManager.addFiles(modalSelectedFiles, category);
            
            // Clear selected files
            modalSelectedFiles = [];
            renderModalSelectedFiles();
            
            // Close modal after starting upload
            setTimeout(() => {
                closeUploadModal();
            }, 300);
        }

        function closeUploadModal() {
            const modal = document.getElementById('uploadModal');
            if (modal) modal.style.display = 'none';
            
            // Reset modal state
            modalSelectedFiles = [];
            renderModalSelectedFiles();
            
            const alertDiv = document.getElementById('uploadAlert');
            if (alertDiv) alertDiv.style.display = 'none';
            
            const category = document.getElementById('uploadCategory');
            if (category) category.value = SELECTED_CATEGORY || '';
            
            checkModalFormValid();
        }
        
        // Add category change listener
        document.addEventListener('DOMContentLoaded', function() {
            const category = document.getElementById('uploadCategory');
            if (category) {
                category.addEventListener('change', checkModalFormValid);
            }
        });

        async function deleteDriveFile(fileId, fileName, categoryParam) {
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
            fd.append('redirect', 'files');
            if (categoryParam) fd.append('category', categoryParam);

            try {
                await fetchJson(FILE_DELETE_URL + '?ajax=1', { method: 'POST', body: fd });
                await reloadFilesTable();
                if (typeof window.showToast === 'function') {
                    window.showToast('File berhasil dihapus', 'success');
                }
            } catch (e) {
                if (typeof window.showToast === 'function') {
                    window.showToast('Gagal menghapus file: ' + e.message, 'error');
                } else {
                    alert('Gagal menghapus file: ' + e.message);
                }
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        let currentCategory = 'all';
        
        function filterByCategory(category) {
            currentCategory = category;

            document.querySelectorAll('.category-filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Select the matching button by data-category attribute instead of relying on global event
            const btn = document.querySelector('.category-filter-btn[data-category="' + category + '"]');
            if (btn && btn.classList) btn.classList.add('active');

            // If we are in lazy/server-side mode, trigger server-side reload for the selected category
            if (LAZY_LOAD) {
                // reset paging state for the current per-page and category
                filesPage = 1;
                filesHasMore = false;
                resetTokensFor(filesPerPage, (SELECTED_CATEGORY && SELECTED_CATEGORY !== null) ? SELECTED_CATEGORY : (currentCategory && currentCategory !== 'all' ? currentCategory : ''));

                // If switching to 'all', ensure default sort is 'modified_desc' and fetch larger page
                if (category === 'all') {
                    const sortEl = document.getElementById('sortSelect');
                    if (sortEl) sortEl.value = 'modified_desc';
                }

                // Load server-side page for the selection
                reloadFilesTable(1);
            } else {
                // Non-lazy: client-side filtering
                filterFiles();
            }
        }
        
        function filterFiles() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const dateFilter = document.getElementById('dateFilter').value;
            const rows = document.querySelectorAll('.file-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const category = row.dataset.category;
                const name = row.dataset.name;
                const date = row.dataset.date;
                
                let show = true;
                
                if (currentCategory !== 'all' && category !== currentCategory) {
                    show = false;
                }
                
                if (searchTerm && !name.includes(searchTerm)) {
                    show = false;
                }
                
                if (dateFilter && date !== dateFilter) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            
            document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
            document.getElementById('filesTable').style.display = visibleCount === 0 ? 'none' : 'table';
        }
        
        function sortFiles() {
            const select = document.getElementById('sortSelect');
            const [field, direction] = select.value.split('_');
            const tbody = document.getElementById('filesTableBody');
            const rows = Array.from(tbody.querySelectorAll('.file-row'));
            
            rows.sort((a, b) => {
                let aVal, bVal;
                
                if (field === 'name') {
                    aVal = a.dataset.name;
                    bVal = b.dataset.name;
                    return direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                } else if (field === 'size') {
                    aVal = parseInt(a.dataset.size);
                    bVal = parseInt(b.dataset.size);
                } else if (field === 'modified') {
                    aVal = parseInt(a.dataset.modified);
                    bVal = parseInt(b.dataset.modified);
                } else if (field === 'created') {
                    aVal = parseInt(a.dataset.created);
                    bVal = parseInt(b.dataset.created);
                }
                
                return direction === 'asc' ? aVal - bVal : bVal - aVal;
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }
        
        function sortTable(column) {
            const select = document.getElementById('sortSelect');
            const currentSort = select.value.split('_');
            
            if (currentSort[0] === column) {
                // Toggle direction
                select.value = column + '_' + (currentSort[1] === 'asc' ? 'desc' : 'asc');
            } else {
                // Default to desc for new column
                select.value = column + '_desc';
            }
            
            sortFiles();
        }
        
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('dateFilter').value = '';
            document.getElementById('sortSelect').value = 'modified_desc';
            
            <?php if (!$selectedCategory): ?>
            currentCategory = 'all';
            
            document.querySelectorAll('.category-filter-btn').forEach((btn, index) => {
                btn.classList.toggle('active', index === 0);
            });
            <?php endif; ?>
            
            filterFiles();
            sortFiles();
        }
        
        // Initialize Table Pagination
        document.addEventListener('DOMContentLoaded', function() {
            // Lazy load files data on page load for faster initial render
            if (LAZY_LOAD) {
                loadFilesData();
            } else {
                // Initialize pagination with configured rows per page
                window.filesPagination = initTablePagination('filesTable', {
                    rowsPerPage: filesPerPage || 10,
                    rowsPerPageOptions: [5, 10, 15, 25, 50]
                });
            }
            
            // Auto-filter on page load if category parameter exists
            <?php if ($selectedCategory): ?>
            currentCategory = '<?php echo $selectedCategory; ?>';
            if (!LAZY_LOAD) filterFiles();
            <?php endif; ?>
        });
        
        // Server-side paged fetch (per_page) default for category views
        const FILES_PER_PAGE = 15;
        // rows per page control (5,10,15,25,50)
        let filesPerPage = 25; // default when viewing all categories
        let filesPage = 1;
        // tokens keyed by `${perPage}|${category}` -> { pageNumber: token }
        let filesPageTokensMap = {};

        function tokensKey(perPage, category) {
            return `${perPage}|${category || 'all'}`;
        }

        function getPageTokenFor(page, perPage, category) {
            const key = tokensKey(perPage, category);
            return (filesPageTokensMap[key] && filesPageTokensMap[key][page]) ? filesPageTokensMap[key][page] : '';
        }

        function setPageTokenFor(page, token, perPage, category) {
            const key = tokensKey(perPage, category);
            filesPageTokensMap[key] = filesPageTokensMap[key] || {};
            if (token && token !== '') filesPageTokensMap[key][page] = token;
        }

        function resetTokensFor(perPage, category) {
            const key = tokensKey(perPage, category);
            filesPageTokensMap[key] = {1: ''};
        }
        let filesHasMore = false;

        function renderFilesPaginationControls() {
            let container = document.getElementById('filesServerPagination');
            if (!container) {
                container = document.createElement('div');
                container.id = 'filesServerPagination';
                container.style.display = 'flex';
                container.style.justifyContent = 'space-between';
                container.style.alignItems = 'center';
                container.style.marginTop = '0.75rem';
                const tableWrapper = document.getElementById('filesTable').closest('.files-table');
                tableWrapper.appendChild(container);
            }

            const isMobile = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
            const prevLabel = isMobile ? '&lt;' : 'Prev';
            const nextLabel = isMobile ? '&gt;' : 'Next';
            const pageText = isMobile ? `Hal ${filesPage}` : `Halaman ${filesPage}`;

            container.innerHTML = `
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <button id="filesPrevBtn" class="btn btn-secondary" ${filesPage === 1 ? 'disabled' : ''}>${prevLabel}</button>
                    <button id="filesNextBtn" class="btn btn-secondary" ${filesHasMore ? '' : 'disabled'}>${nextLabel}</button>
                    <label style="margin:0 0 0 1rem; font-size:0.9rem; color:#475569;">Tampilkan:</label>
                    <select id="filesPerPageSelect" style="padding:0.35rem 0.5rem; border-radius:0.375rem; border:1px solid #e5e7eb;">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div style="font-weight:600">${pageText}</div>
            `;

            // Prev/Next handlers
            const prevBtn = document.getElementById('filesPrevBtn');
            const nextBtn = document.getElementById('filesNextBtn');
            if (prevBtn) prevBtn.onclick = () => { if (filesPage > 1) { filesPage--; fetchFilesPage(filesPage); } };
            if (nextBtn) nextBtn.onclick = () => { if (filesHasMore) { filesPage++; fetchFilesPage(filesPage); } };

            // rows-per-page select handling
            const sel = document.getElementById('filesPerPageSelect');
            if (sel) {
                sel.value = String(filesPerPage);
                sel.onchange = () => {
                    const v = parseInt(sel.value) || 25;
                    filesPerPage = v;
                    // reset paging state and reload
                    filesPage = 1;
                    filesHasMore = false;
                    resetTokensFor(filesPerPage, (SELECTED_CATEGORY && SELECTED_CATEGORY !== null) ? SELECTED_CATEGORY : (currentCategory && currentCategory !== 'all' ? currentCategory : ''));

                    // If non-lazy (client-side) update table pagination instance
                    if (!LAZY_LOAD) {
                        if (window.filesPagination) {
                            window.filesPagination.rowsPerPage = filesPerPage;
                            window.filesPagination.currentPage = 1;
                            try { window.filesPagination.render(); } catch (e) {}
                        } else {
                            window.filesPagination = initTablePagination('filesTable', { rowsPerPage: filesPerPage, rowsPerPageOptions: [5,10,15,25,50] });
                        }
                    }

                    reloadFilesTable(1);
                };
            }

            // re-render controls on resize to update labels for mobile/desktop
            if (!container._filesPaginationResizeAttached) {
                window.addEventListener('resize', () => {
                    clearTimeout(container._filesPaginationResizeTimer);
                    container._filesPaginationResizeTimer = setTimeout(() => renderFilesPaginationControls(), 150);
                });
                container._filesPaginationResizeAttached = true;
            }
        }

        async function fetchFilesPage(page) {
            try {
                const url = new URL(FILES_DATA_URL, window.location.origin);
                url.searchParams.set('ajax', '1');

                // Determine category to request: server-provided SELECTED_CATEGORY (page param) takes precedence,
                // otherwise use client-selected currentCategory when it's not 'all'.
                const categoryParam = (SELECTED_CATEGORY && SELECTED_CATEGORY !== null) ? SELECTED_CATEGORY : (currentCategory && currentCategory !== 'all' ? currentCategory : '');

                // Adaptive page size: use user-selected filesPerPage when present; fallback to FILES_PER_PAGE for categories
                const perPage = (typeof filesPerPage === 'number' && filesPerPage > 0) ? filesPerPage : (categoryParam === '' ? 25 : FILES_PER_PAGE);
                url.searchParams.set('per_page', perPage);

                // only send pageToken when it belongs to the same perPage+category context
                const token = getPageTokenFor(page, perPage, categoryParam);
                if (token) url.searchParams.set('page_token', token);
                if (categoryParam) url.searchParams.set('category', categoryParam);

                // Default sort: when viewing all categories ensure default is 'modified_desc'
                if (!categoryParam) {
                    const sortEl = document.getElementById('sortSelect');
                    if (sortEl && !sortEl.value) sortEl.value = 'modified_desc';
                }

                const data = await fetchJson(url.toString());
                const files = (data && data.data && data.data.files) ? data.data.files : [];

                renderFilesTable(files);

                // update tokens and hasMore (store token in per-(perPage,category) map)
                const nextToken = (data && data.data && (data.data.next_page_token ?? data.data.files_next_page_token)) || null;
                filesHasMore = !!(nextToken && nextToken !== '');
                if (nextToken) setPageTokenFor(page + 1, nextToken, perPage, categoryParam);

                // render pagination UI
                renderFilesPaginationControls();

                // apply filters/sorts locally for current page
                try { sortFiles(); } catch (e) {}
                try { filterFiles(); } catch (e) {}

                // Show table
                const skeleton = document.getElementById('skeletonLoader');
                if (skeleton) skeleton.style.display = 'none';
                const filesTable = document.getElementById('filesTable');
                if (filesTable) filesTable.style.display = files.length === 0 ? 'none' : 'table';
            } catch (e) {
                console.error('Failed to fetch files page:', e);
            }
        }

        // Lazy load first page on start
        async function loadFilesData() {
            const skeleton = document.getElementById('skeletonLoader');
            if (skeleton) skeleton.style.display = '';
            filesPage = 1;
            filesPageTokens = {1: ''};
            filesHasMore = false;
            await fetchFilesPage(1);
        }
    </script>
</body>
</html>