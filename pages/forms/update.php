<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/ajax_helpers.php';

requireLogin();

requireRateLimit('forms_update');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ajaxError('Invalid request method');
}

requireValidCsrfToken(null);

$id = isset($_POST['id']) ? $_POST['id'] : '';
$category = isset($_POST['category']) ? $_POST['category'] : '';
$title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
$url = isset($_POST['url']) ? sanitize($_POST['url']) : '';

// Note: Don't use empty() for ID because empty('0') returns true
if ($id === '' || empty($category) || empty($title) || empty($url)) {
    ajaxError('Semua field harus diisi!');
}

try {
    // Parse ID - handle both formats: "category_index" or just "index"
    if (strpos($id, '_') !== false) {
        // Format: category_index
        $parts = explode('_', $id);
        $actualId = intval($parts[1]);
    } else {
        // Format: index
        $actualId = intval($id);
    }
    
    // Update form in sheets (category should be the internal key)
    if (updateFormInSheets($actualId, $title, $url, $category)) {
        // Clear cache
        foreach (array_keys($_SESSION) as $key) {
            if (strpos($key, 'forms_cache_') === 0 || strpos($key, 'category_') === 0) {
                unset($_SESSION[$key]);
            }
        }
        
        ajaxSuccess('Form berhasil diupdate!');
    } else {
        ajaxError('Gagal mengupdate form di Google Sheets!');
    }
} catch (Exception $e) {
    error_log('Error updating form: ' . $e->getMessage());
    ajaxError('Terjadi kesalahan: ' . $e->getMessage());
}
