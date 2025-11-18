<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';

requireLogin();

$error = '';
$success = '';

// Define folder categories
$categories = [
    'kesiswaan' => [
        'name' => 'Kesiswaan',
        'folder_id' => '1ek7EDGg525Nr6sT30yNhyLvphgXGN-3z'
    ],
    'kurikulum' => [
        'name' => 'Kurikulum',
        'folder_id' => '1JlqjO6AxW2ML-FuP14f22wMmLSbASSzA'
    ],
    'sapras' => [
        'name' => 'Sapras & Humas',
        'folder_id' => '13F-Cg44IpKOn-iWPpPfRq5A57Adf9VM6'
    ],
    'tata_usaha' => [
        'name' => 'Tata Usaha',
        'folder_id' => '1P_z7_txZbvQX4yLJez4Zzh0gViBDHd30'
    ]
];

$selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $category = $_POST['category'] ?? '';
    
    if (!isset($categories[$category])) {
        $error = 'Kategori tidak valid!';
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'File harus diupload!';
    } else {
        try {
            $file = $_FILES['file'];
            $folderId = $categories[$category]['folder_id'];
            
            // Upload to Google Drive
            $client = getGoogleClient();
            $driveService = new Google_Service_Drive($client);
            
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $file['name'],
                'parents' => [$folderId]
            ]);
            
            $content = file_get_contents($file['tmp_name']);
            $mimeType = $file['type'];
            
            $uploadedFile = $driveService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, name, mimeType, size, createdTime'
            ]);
            
            if ($uploadedFile->id) {
                $success = 'File berhasil diupload!';
                
                // Clear cache
                if (isset($_SESSION["category_{$category}_files"])) {
                    unset($_SESSION["category_{$category}_files"]);
                    unset($_SESSION["category_{$category}_files_time"]);
                }
                
                // Send message to parent window
                echo "<script>
                    if (window.parent) {
                        window.parent.postMessage('file_uploaded', '*');
                    }
                </script>";
            } else {
                $error = 'Gagal mengupload file!';
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
    <title>Upload File</title>
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
            overflow-x: hidden;
        }

        .upload-container {
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

        select, input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
            background: white;
        }

        select:focus, input[type="file"]:focus {
            outline: none;
            border-color: #50e3c2;
            box-shadow: 0 0 0 3px rgba(80, 227, 194, 0.1);
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            cursor: pointer;
        }

        .file-input-wrapper input[type="file"]::file-selector-button {
            padding: 0.5rem 1rem;
            border: none;
            background: #50e3c2;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            margin-right: 1rem;
            transition: background 0.2s;
        }

        .file-input-wrapper input[type="file"]::file-selector-button:hover {
            background: #3dd4b3;
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

        .file-requirements {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #50e3c2;
            margin-bottom: 1.5rem;
        }

        .file-requirements h4 {
            color: #334155;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .file-requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .file-requirements li {
            color: #64748b;
            font-size: 0.85rem;
            padding: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-requirements li i {
            color: #50e3c2;
            font-size: 0.75rem;
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

            select, input[type="file"] {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            .btn {
                width: 100%;
                justify-content: center;
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .file-requirements {
                padding: 0.75rem;
            }

            .file-requirements h4 {
                font-size: 0.85rem;
            }

            .file-requirements li {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="upload-container">
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

        <div class="file-requirements">
            <h4><i class="fas fa-info-circle"></i> Ketentuan Upload</h4>
            <ul>
                <li><i class="fas fa-check"></i> Ukuran maksimal: 100 MB</li>
                <li><i class="fas fa-check"></i> Format: PDF, Word, Excel, PowerPoint, Gambar</li>
                <li><i class="fas fa-check"></i> Nama file sebaiknya deskriptif</li>
            </ul>
        </div>

        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label for="category">
                    <i class="fas fa-folder"></i> Kategori
                </label>
                <select name="category" id="category" required>
                    <option value="">Pilih Kategori</option>
                    <?php foreach ($categories as $key => $cat): ?>
                        <option value="<?php echo $key; ?>" <?php echo $selectedCategory === $key ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="file">
                    <i class="fas fa-file"></i> Pilih File
                </label>
                <div class="file-input-wrapper">
                    <input type="file" name="file" id="file" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-upload"></i> Upload File
            </button>
        </form>
    </div>

    <script>
        // Handle form submission
        document.getElementById('uploadForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengupload...';
        });

        // Auto-close modal on success
        <?php if ($success): ?>
            setTimeout(function() {
                if (window.parent && window.parent.closeUploadModal) {
                    window.parent.closeUploadModal();
                }
            }, 1500);
        <?php endif; ?>
    </script>
</body>
</html>
