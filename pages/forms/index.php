<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';

requireLogin();

$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

$cacheTime = 300;

$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';

$categories = getFormCategories();

$cacheKey = 'forms_cache_' . ($selectedCategory ?: 'all');

if (isset($_SESSION[$cacheKey]) && 
    isset($_SESSION[$cacheKey . '_time']) && 
    (time() - $_SESSION[$cacheKey . '_time']) < $cacheTime) {
    $forms = $_SESSION[$cacheKey];
} else {
    if ($selectedCategory && isset($categories[$selectedCategory])) {
        $forms = getFormsFromSheets($selectedCategory);
    } else {
        $forms = [];
        foreach ($categories as $key => $category) {
            $categoryForms = getFormsFromSheets($key);
            foreach ($categoryForms as $form) {
                $form['category'] = $key;
                $form['category_name'] = $category['name'];
                $form['category_color'] = $category['color'];
                $forms[] = $form;
            }
        }
    }
    $_SESSION[$cacheKey] = $forms;
    $_SESSION[$cacheKey . '_time'] = time();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Forms - <?php echo APP_NAME; ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/smk62.png">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        }
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
                    <h2><i class="fas fa-file-alt"></i> Daftar Forms (<?php echo count($forms); ?>)</h2>
                    <a href="add.php" class="btn btn-primary" style="padding: 0.5rem 0.875rem; font-size: 0.875rem;">
                        <i class="fas fa-plus"></i> Tambah Form
                    </a>
                </div>
                
                <!-- Mobile Category Dropdown -->
                <div class="category-filter-dropdown">
                    <select onchange="window.location.href=this.value">
                        <option value="index.php" <?php echo empty($selectedCategory) ? 'selected' : ''; ?>>Semua Kategori</option>
                        <?php foreach ($categories as $key => $category): ?>
                            <option value="index.php?category=<?php echo $key; ?>" 
                                    <?php echo $selectedCategory === $key ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Desktop Category Buttons -->
                <div class="category-filter">
                    <a href="index.php" 
                       class="category-btn <?php echo empty($selectedCategory) ? 'active' : ''; ?>"
                       style="<?php echo empty($selectedCategory) ? 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);' : ''; ?>">
                        <i class="fas fa-th"></i>
                        Semua
                    </a>
                    <?php foreach ($categories as $key => $category): ?>
                        <a href="index.php?category=<?php echo $key; ?>" 
                           class="category-btn <?php echo $selectedCategory === $key ? 'active' : ''; ?>"
                           style="<?php echo $selectedCategory === $key ? 'background: ' . $category['color'] . ';' : ''; ?>">
                            <i class="fas <?php echo $category['icon']; ?>"></i>
                            <?php echo $category['name']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($forms)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Belum ada form. Klik tombol "Tambah Form" untuk menambahkan.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 40%;">Judul</th>
                                <th style="width: 35%;">URL</th>
                                <th style="width: 15%;">Kategori</th>
                                <th style="width: 10%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($forms as $form): ?>
                                <tr>
                                    <td>
                                        <div class="file-info">
                                            <div class="file-icon">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                            <div class="file-details">
                                                <span class="file-name"><?php echo htmlspecialchars($form['title']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="color: #64748b; font-size: 0.8125rem;">
                                        <a href="<?php echo htmlspecialchars($form['url']); ?>" 
                                           target="_blank" 
                                           style="color: var(--primary-color); text-decoration: none;">
                                            <?php 
                                            $url = $form['url'];
                                            echo htmlspecialchars(strlen($url) > 50 ? substr($url, 0, 50) . '...' : $url); 
                                            ?>
                                            <i class="fas fa-external-link-alt" style="font-size: 0.75rem; margin-left: 0.25rem;"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if (isset($form['category_name'])): ?>
                                            <span class="category-badge" style="background: <?php echo $form['category_color']; ?>">
                                                <i class="fas <?php echo $categories[$form['category']]['icon']; ?>"></i>
                                                <?php echo $form['category_name']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #64748b; font-size: 0.8125rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="<?php echo htmlspecialchars($form['url']); ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-info" 
                                           style="padding: 0.375rem 0.625rem; font-size: 0.8125rem;">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <?php include __DIR__ . '/../../includes/footer.php'; ?>
            
            <?php include __DIR__ . '/../../includes/footer.php'; ?>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js\"></script>
</body>
</html>
