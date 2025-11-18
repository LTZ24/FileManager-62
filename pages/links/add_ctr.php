<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';

requireLogin();

$error = '';
$success = '';
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';

$categories = getLinkCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $url = sanitize($_POST['url']);
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category']);
    
    if (empty($title) || empty($url) || empty($category)) {
        $error = 'Judul dan URL harus diisi!';
    } elseif (!isset($categories[$category])) {
        $error = 'Kategori tidak valid!';
    } else {
        try {
            if (addLinkToSheets($title, $url, $category, $description)) {
                // Clear cache
                unset($_SESSION["category_{$category}_links"]);
                unset($_SESSION["category_{$category}_links_time"]);
                
                $success = 'Link berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan link!';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Link</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #ffffff;
            padding: 1rem;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .link-container {
            max-width: 100%;
            margin: 0 auto;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #334155;
            font-weight: 500;
            font-size: 0.95rem;
        }

        label i {
            margin-right: 0.25rem;
            color: #64748b;
        }

        input[type="text"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
            background: white;
        }

        input[type="text"]:focus,
        input[type="url"]:focus,
        textarea:focus {
            outline: none;
            border-color: #50e3c2;
            box-shadow: 0 0 0 3px rgba(80, 227, 194, 0.1);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #50e3c2 0%, #3dd4b3 100%);
            color: white;
            box-shadow: 0 4px 6px rgba(80, 227, 194, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(80, 227, 194, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        @media (max-width: 768px) {
            body {
                padding: 0.75rem;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            label {
                font-size: 0.85rem;
            }

            input[type="text"],
            input[type="url"],
            textarea {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            textarea {
                min-height: 80px;
            }

            .btn {
                width: 100%;
                justify-content: center;
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="link-container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="linkForm">
            <div class="form-group">
                <label for="title">
                    <i class="fas fa-heading"></i> Judul Link
                </label>
                <input type="text" id="title" name="title" required placeholder="Masukkan judul link">
            </div>

            <div class="form-group">
                <label for="url">
                    <i class="fas fa-globe"></i> URL
                </label>
                <input type="url" id="url" name="url" required placeholder="https://example.com">
            </div>

            <div class="form-group">
                <label for="description">
                    <i class="fas fa-align-left"></i> Deskripsi (Opsional)
                </label>
                <textarea id="description" name="description" placeholder="Deskripsi link"></textarea>
            </div>

            <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory); ?>">

            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> Simpan Link
            </button>
        </form>
    </div>

    <script>
        // Handle form submission
        document.getElementById('linkForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        });

        // Auto-close on success
        <?php if ($success): ?>
            if (window.parent) {
                window.parent.postMessage('link_added', '*');
            }
            
            setTimeout(function() {
                if (window.parent && window.parent.closeLinkModal) {
                    window.parent.closeLinkModal();
                }
            }, 1500);
        <?php endif; ?>
    </script>
</body>
</html>
