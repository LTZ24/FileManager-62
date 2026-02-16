<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/ajax_helpers.php';

requireLogin();

// Function to get forms from category
function getCategoryForms($sheetId, $category) {
    try {
        $sheetsService = getSheetsService();
        
        $sheetTitle = 'Forms-' . ucfirst($category);
        $range = $sheetTitle . '!A2:E';
        $response = $sheetsService->spreadsheets_values->get($sheetId, $range);
        $values = $response->getValues();
        
        $forms = [];
        if (!empty($values)) {
            foreach ($values as $index => $row) {
                if (!isset($row[0]) || !isset($row[1])) {
                    continue;
                }
                $forms[] = [
                    'id' => $index,
                    'row_number' => $index + 2, // Actual row in sheet (A2 = row 2)
                    'title' => $row[0] ?? '',
                    'url' => $row[1] ?? '',
                    'date' => $row[2] ?? '',
                    'updated_at' => $row[3] ?? '',
                    'category' => $row[4] ?? $category
                ];
            }
        }
        return $forms;
    } catch (Exception $e) {
        error_log("Error getting forms: " . $e->getMessage());
        return [];
    }
}

$id = isset($_GET['id']) ? $_GET['id'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

if ($id === '' || empty($category)) {
    echo '<div style="padding: 2rem; text-align: center; color: #ef4444;">ID atau kategori tidak valid</div>';
    exit;
}

// Parse ID - handle both formats: "category_index" or just "index"
if (strpos($id, '_') !== false) {
    // Format: category_index
    $parts = explode('_', $id);
    $actualId = intval($parts[1]);
} else {
    // Format: index
    $actualId = intval($id);
}

$categoryName = ucfirst($category);
$forms = getCategoryForms(constant('SHEETS_' . strtoupper($category)), $categoryName);

// Find form by ID
$form = null;
foreach ($forms as $item) {
    if ($item['id'] == $actualId) {
        $form = $item;
        break;
    }
}

if (!$form) {
    echo '<div style="padding: 2rem; text-align: center; color: #ef4444;">Form tidak ditemukan (ID: ' . htmlspecialchars($id) . ', Category: ' . htmlspecialchars($category) . ')</div>';
    exit;
}
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRateLimit('forms_edit_ctr');
    requireValidCsrfToken(null);

    $title = sanitize($_POST['title']);
    $url = sanitize($_POST['url']);
    
    if (empty($title) || empty($url)) {
        $error = 'Semua field harus diisi!';
    } else {
        try {
            // Use ID (0-based index) - updateFormInSheets will add 2 to get row number
            if (updateFormInSheets($form['id'], $title, $url, $categoryName)) {
                // Clear cache
                foreach (array_keys($_SESSION) as $key) {
                    if (strpos($key, 'forms_cache_') === 0 || strpos($key, 'category_') === 0) {
                        unset($_SESSION[$key]);
                    }
                }
                
                $success = 'Form berhasil diupdate!';
                echo '<script>
                    setTimeout(function() {
                        window.parent.postMessage("form_updated", "*");
                    }, 1500);
                </script>';
            } else {
                $error = 'Gagal mengupdate form di Google Sheets!';
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
    <title>Edit Form</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo urlencode(APP_VERSION); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        body {
            background: #f8fafc;
            padding: 1.5rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .form-container {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #334155;
            font-size: 0.875rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        .btn {
            flex: 1;
            padding: 0.625rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #64748b;
            color: white;
        }
        .btn-secondary:hover {
            background: #475569;
        }
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateSecureToken()); ?>">
            <div class="form-group">
                <label for="title"><i class="fas fa-heading"></i> Judul Form</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($form['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="url"><i class="fas fa-link"></i> URL</label>
                <input type="url" id="url" name="url" value="<?php echo htmlspecialchars($form['url']); ?>" required>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.parent.postMessage('close_edit', '*')">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</body>
</html>
