<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/ajax_helpers.php';

requireLogin();

requireRateLimit('files_delete', null, null, BASE_URL . '/pages/files/');

requirePostMethod(BASE_URL . '/pages/files/');
requireValidCsrfToken(BASE_URL . '/pages/files/');

$fileId = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
$confirm = isset($_POST['confirm']) ? (string)$_POST['confirm'] : '';
$redirect = isset($_POST['redirect']) ? (string)$_POST['redirect'] : 'files';
$category = isset($_POST['category']) ? (string)$_POST['category'] : '';

if ($fileId === '') {
    if (isAjaxRequest()) {
        ajaxError('ID file tidak valid');
    }
    redirect(BASE_URL . '/pages/files/');
}

if ($confirm !== '1') {
    if (isAjaxRequest()) {
        ajaxError('Konfirmasi diperlukan');
    }
    if ($redirect === 'category' && $category !== '') {
        redirect(BASE_URL . '/pages/category/' . $category);
    }
    $url = BASE_URL . '/pages/files/';
    if ($category !== '') $url .= '?category=' . urlencode($category);
    redirect($url);
}

try {
    // Use OAuth-based Drive service for WRITE operations (delete/trash)
    $driveService = getDriveServiceForWrite();

    // Move to trash
    $driveFile = new Google_Service_Drive_DriveFile();
    $driveFile->setTrashed(true);
    $driveService->files->update($fileId, $driveFile, ['supportsAllDrives' => true]);

    // Remove from MySQL
    try {
        $db = getDB();
        $stmt = $db->prepare('DELETE FROM app_files WHERE google_file_id = ?');
        $stmt->execute([$fileId]);
    } catch (Exception $dbErr) {
        error_log('delete.php DB cleanup error: ' . $dbErr->getMessage());
    }

    if (isAjaxRequest()) {
        ajaxSuccess('File berhasil dihapus', ['id' => $fileId]);
    }

    if ($redirect === 'category' && $category !== '') {
        redirect(BASE_URL . '/pages/category/' . $category . '?success=' . urlencode('File berhasil dihapus'));
    }
    $url = BASE_URL . '/pages/files/?success=' . urlencode('File berhasil dihapus');
    if ($category !== '') $url .= '&category=' . urlencode($category);
    redirect($url);

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    if (stripos($errorMsg, 'permission') !== false || stripos($errorMsg, '403') !== false) {
        $errorMsg = 'Tidak dapat menghapus file. Pastikan Service Account memiliki akses.';
    }
    if (isAjaxRequest()) {
        ajaxError($errorMsg);
    }
    if ($redirect === 'category' && $category !== '') {
        redirect(BASE_URL . '/pages/category/' . $category . '?error=' . urlencode($errorMsg));
    }
    $url = BASE_URL . '/pages/files/?error=' . urlencode($errorMsg);
    if ($category !== '') $url .= '&category=' . urlencode($category);
    redirect($url);
}
