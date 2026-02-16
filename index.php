<?php
require_once __DIR__ . '/includes/config.php';

// Jika belum login, redirect ke login page
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FileManager SMKN62</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="description" content="File Manager internal SMK Negeri 62 Jakarta">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="FileManager SMKN62">
    <meta name="msapplication-TileColor" content="#2563eb">
    <meta name="msapplication-TileImage" content="assets/images/icons/icon-144x144.png">
    
    <!-- Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/icons/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icons/apple-touch-icon.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/images/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="144x144" href="assets/images/icons/icon-144x144.png">
    <link rel="apple-touch-icon" sizes="120x120" href="assets/images/icons/icon-128x128.png">
    <link rel="mask-icon" href="assets/images/icons/icon-512x512.png" color="#2563eb">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json?v=<?php echo urlencode(APP_VERSION); ?>">
    
    <!-- Resource Hints for Faster Loading -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://www.googleapis.com" crossorigin>
    <link rel="dns-prefetch" href="https://drive.google.com">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous">
    
    <style>
        /* Skeleton Loader */
        .skeleton-loader {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding: 0.5rem;
        }
        
        .skeleton-item {
            height: 48px;
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            border-radius: 0.5rem;
            animation: shimmer 1.5s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .upload-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: var(--light-color);
            border-radius: 0.5rem;
            font-size: 0.8rem;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .upload-item i {
            color: #3b82f6;
            font-size: 0.9rem;
        }
        
        .upload-details {
            flex: 1;
            min-width: 0;
        }
        
        .upload-name {
            font-weight: 600;
            color: var(--dark-color);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .upload-time {
            color: var(--secondary-color);
            font-size: 0.75rem;
        }
        
        .empty-uploads {
            text-align: center;
            padding: 1.5rem;
            color: var(--secondary-color);
            font-size: 0.875rem;
        }
        
        .empty-uploads i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="dashboard">
                <p>Selamat datang, <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User'); ?>!</p>
                
                <?php
                // Lazy loading: Data akan di-fetch via AJAX setelah page load
                // Tidak ada API calls di PHP untuk performa maksimal
                ?>
                
                <div class="category-section">
                    <h2>Kategori</h2>
                    <div class="category-grid">
                        <a href="<?php echo BASE_URL; ?>/pages/category/kesiswaan" class="category-card">
                            <i class="fas fa-user-graduate" style="color: #3b82f6;"></i>
                            <h3>Kesiswaan</h3>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/category/kurikulum" class="category-card">
                            <i class="fas fa-book" style="color: #10b981;"></i>
                            <h3>Kurikulum</h3>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/category/sapras-humas" class="category-card">
                            <i class="fas fa-building" style="color: #f59e0b;"></i>
                            <h3>Sapras & Humas</h3>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/category/tata-usaha" class="category-card">
                            <i class="fas fa-briefcase" style="color: #8b5cf6;"></i>
                            <h3>Tata Usaha</h3>
                        </a>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <h2>Aksi Cepat</h2>
                    <div class="action-grid">
                        <a href="<?php echo BASE_URL; ?>/pages/links/" class="action-card">
                            <i class="fas fa-link"></i>
                            <span>Kelola Links</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/forms/" class="action-card">
                            <i class="fas fa-file-alt"></i>
                            <span>Kelola Forms</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/files/" class="action-card">
                            <i class="fas fa-folder-open"></i>
                            <span>File Manager</span>
                        </a>
                        <button onclick="refreshPage()" class="action-card" type="button">
                            <i class="fas fa-sync-alt"></i>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <!-- Card: Upload Terbaru -->
                    <div class="stat-card upload-card">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                            <i class="fas fa-clock-rotate-left" style="font-size: 2.5rem; color: var(--primary-color);"></i>
                            <div>
                                <h3 style="margin: 0; font-size: 1rem;">Upload Terbaru</h3>
                                <p style="margin: 0; font-size: 0.875rem; color: var(--secondary-color);">
                                    Riwayat upload file terakhir dari Google Drive
                                </p>
                            </div>
                        </div>
                        
                        <!-- Upload History Table (Lazy Loading) -->
                        <div id="uploads-container" style="max-height: 140px; overflow-y: auto;">
                            <!-- Skeleton Loader -->
                            <div class="skeleton-loader">
                                <div class="skeleton-item"></div>
                                <div class="skeleton-item"></div>
                                <div class="skeleton-item"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'includes/footer.php'; ?>
        </div> <!-- End content-wrapper -->
    </div> <!-- End main-content -->
    
    <script src="assets/js/main.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script src="assets/js/pwa.js?v=<?php echo urlencode(APP_VERSION); ?>"></script>
    <script>
        function refreshPage() {
            const icon = event.target.classList.contains('fa-sync-alt') 
                ? event.target 
                : event.target.querySelector('i');
            
            if (icon) {
                icon.classList.add('fa-spin');
            }
            
            setTimeout(() => {
                window.location.reload();
            }, 800);
        }
        
        // Lazy Load Recent Uploads
        async function loadRecentUploads() {
            try {
                const response = await fetch('<?php echo BASE_URL; ?>/api/recent');
                const data = await response.json();
                
                const container = document.getElementById('uploads-container');
                
                if (data.uploads && data.uploads.length > 0) {
                    container.innerHTML = '<div style="display: flex; flex-direction: column; gap: 0.5rem;">' +
                        data.uploads.map(file => {
                            const date = new Date(file.createdTime);
                            const timeAgo = date.toLocaleDateString('id-ID', { 
                                day: '2-digit', 
                                month: '2-digit', 
                                year: '2-digit' 
                            }) + ' ' + date.toLocaleTimeString('id-ID', { 
                                hour: '2-digit', 
                                minute: '2-digit' 
                            });
                            
                            const shortName = file.name.length > 20 
                                ? file.name.substring(0, 20) + '...' 
                                : file.name;
                            
                            return `
                                <div class="upload-item">
                                    <i class="fas fa-file"></i>
                                    <div class="upload-details">
                                        <div class="upload-name" title="${file.name}">${shortName}</div>
                                        <div class="upload-time">${timeAgo}</div>
                                    </div>
                                </div>
                            `;
                        }).join('') +
                        '</div>';
                } else {
                    container.innerHTML = `
                        <div class="empty-uploads">
                            <i class="fas fa-inbox"></i>
                            Belum ada upload
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading recent uploads:', error);
                document.getElementById('uploads-container').innerHTML = `
                    <div class="empty-uploads">
                        <i class="fas fa-exclamation-triangle"></i>
                        Gagal memuat data
                    </div>
                `;
            }
        }
        
        // Load data setelah page ready
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentUploads();
        });
    </script>
</body>
</html>
