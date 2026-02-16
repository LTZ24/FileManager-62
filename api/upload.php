<?php
/**
 * Upload API — Service Account + MySQL metadata
 * FileManager SMKN 62 Jakarta v3.0
 *
 * Supports:
 * - Single file upload
 * - Batch upload (max 15 files)
 * - Resumable upload for large files (up to 100MB)
 */

@ini_set('upload_max_filesize', '100M');
@ini_set('post_max_size', '105M');
@ini_set('max_execution_time', '600');
@ini_set('max_input_time', '600');
@ini_set('memory_limit', '256M');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Auth check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$_SESSION['last_activity'] = time();

// Category → folder mapping from config
$categories = getCategories();
$categoryFolders = [];
foreach ($categories as $key => $cat) {
    $categoryFolders[$key] = [
        'name' => $cat['name'],
        'folder_id' => $cat['folder_id'],
        'icon' => str_replace('fa-', '', $cat['icon']),
        'color' => $cat['color']
    ];
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'upload';

try {
    switch ($action) {
        case 'upload':
            handleSingleUpload($categoryFolders);
            break;
        case 'batch':
            handleBatchUpload($categoryFolders);
            break;
        case 'keepalive':
            echo json_encode(['success' => true, 'message' => 'Session active', 'timestamp' => time()]);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $e->getMessage()]);
}

// ── Single Upload ────────────────────────────────────────
function handleSingleUpload($categories) {
    $category = sanitize($_POST['category'] ?? '');

    if (!isset($categories[$category])) {
        echo json_encode(['success' => false, 'message' => 'Kategori tidak valid: ' . $category]);
        return;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'File melebihi batas maksimum server',
            UPLOAD_ERR_FORM_SIZE  => 'File melebihi batas maksimum form',
            UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak tersedia',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION  => 'Upload dihentikan oleh extension PHP'
        ];
        $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        echo json_encode(['success' => false, 'message' => $errorMessages[$code] ?? 'Error upload']);
        return;
    }

    $file     = $_FILES['file'];
    $folderId = $categories[$category]['folder_id'];

    // Use OAuth-based Drive service for WRITE operations (upload)
    $driveService = getDriveServiceForWrite();

    $mimeType = $file['type'];
    if (empty($mimeType) || $mimeType === 'application/octet-stream') {
        $mimeType = getMimeTypeFromExtension($file['name']);
    }

    $fileSize = $file['size'];

    if ($fileSize > 5 * 1024 * 1024) {
        $uploadedFile = uploadResumable($driveService, $file['tmp_name'], $file['name'], $mimeType, $folderId, $fileSize);
    } else {
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $file['name'],
            'parents' => [$folderId]
        ]);
        $content = file_get_contents($file['tmp_name']);
        $uploadedFile = $driveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'supportsAllDrives' => true,
            'fields' => 'id, name, mimeType, size, createdTime, webViewLink, thumbnailLink'
        ]);
    }

    if ($uploadedFile && $uploadedFile->id) {
        // Save metadata to MySQL
        saveFileMetadata($uploadedFile, $category);

        // Make file publicly readable  
        setFilePublicPermission($driveService, $uploadedFile->id);

        echo json_encode([
            'success' => true,
            'message' => 'File berhasil diupload ke ' . $categories[$category]['name'] . '!',
            'data' => [
                'file_id'   => $uploadedFile->id,
                'file_name' => $uploadedFile->name,
                'mime_type' => $uploadedFile->mimeType,
                'size'      => $uploadedFile->size
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupload file ke Google Drive']);
    }
}

// ── Batch Upload ─────────────────────────────────────────
function handleBatchUpload($categories) {
    $category = sanitize($_POST['category'] ?? '');

    if (!isset($categories[$category])) {
        echo json_encode(['success' => false, 'message' => 'Kategori tidak valid: ' . $category]);
        return;
    }

    if (!isset($_FILES['files']) || !is_array($_FILES['files']['name'])) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada file yang diupload']);
        return;
    }

    $files = $_FILES['files'];
    $fileCount = count($files['name']);

    if ($fileCount > 15) {
        echo json_encode(['success' => false, 'message' => 'Maksimal 15 file dalam satu batch upload']);
        return;
    }

    $folderId = $categories[$category]['folder_id'];
    // Use OAuth-based Drive service for WRITE operations (upload)
    $driveService = getDriveServiceForWrite();

    $results = [];
    $successCount = 0;
    $failCount = 0;

    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $results[] = ['name' => $files['name'][$i], 'success' => false, 'message' => 'Error: ' . $files['error'][$i]];
            $failCount++;
            continue;
        }

        try {
            $mimeType = $files['type'][$i];
            if (empty($mimeType) || $mimeType === 'application/octet-stream') {
                $mimeType = getMimeTypeFromExtension($files['name'][$i]);
            }

            $fileSize = $files['size'][$i];

            if ($fileSize > 5 * 1024 * 1024) {
                $uploadedFile = uploadResumable($driveService, $files['tmp_name'][$i], $files['name'][$i], $mimeType, $folderId, $fileSize);
            } else {
                $fileMetadata = new Google_Service_Drive_DriveFile([
                    'name' => $files['name'][$i],
                    'parents' => [$folderId]
                ]);
                $content = file_get_contents($files['tmp_name'][$i]);
                $uploadedFile = $driveService->files->create($fileMetadata, [
                    'data' => $content,
                    'mimeType' => $mimeType,
                    'uploadType' => 'multipart',
                    'supportsAllDrives' => true,
                    'fields' => 'id, name, mimeType, size, createdTime, webViewLink, thumbnailLink'
                ]);
            }

            if ($uploadedFile && $uploadedFile->id) {
                saveFileMetadata($uploadedFile, $category);
                setFilePublicPermission($driveService, $uploadedFile->id);
                $results[] = ['name' => $files['name'][$i], 'success' => true, 'file_id' => $uploadedFile->id];
                $successCount++;
            } else {
                $results[] = ['name' => $files['name'][$i], 'success' => false, 'message' => 'Gagal upload'];
                $failCount++;
            }
        } catch (Exception $e) {
            $results[] = ['name' => $files['name'][$i], 'success' => false, 'message' => $e->getMessage()];
            $failCount++;
        }

        $_SESSION['last_activity'] = time();
    }

    echo json_encode([
        'success' => $successCount > 0,
        'message' => "$successCount file berhasil" . ($failCount > 0 ? ", $failCount gagal" : ""),
        'data' => ['success_count' => $successCount, 'fail_count' => $failCount, 'results' => $results]
    ]);
}

// ── Save file metadata to MySQL ──────────────────────────
function saveFileMetadata($driveFile, $category) {
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO app_files (google_file_id, filename, mime_type, file_size, category, web_view_link, thumbnail_link, uploaded_by)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $driveFile->id,
            $driveFile->name,
            $driveFile->mimeType,
            $driveFile->size ?? 0,
            $category,
            $driveFile->webViewLink ?? null,
            $driveFile->thumbnailLink ?? null,
            $_SESSION['user_id'] ?? null
        ]);
    } catch (Exception $e) {
        error_log('saveFileMetadata error: ' . $e->getMessage());
    }
}

// ── Set file publicly readable ───────────────────────────
function setFilePublicPermission($driveService, $fileId) {
    try {
        $permission = new Google_Service_Drive_Permission([
            'type' => 'anyone',
            'role' => 'reader'
        ]);
        $driveService->permissions->create($fileId, $permission, ['supportsAllDrives' => true]);
    } catch (Exception $e) {
        error_log('setFilePublicPermission error: ' . $e->getMessage());
    }
}

// ── Resumable Upload ─────────────────────────────────────
function uploadResumable($driveService, $filePath, $fileName, $mimeType, $folderId, $fileSize) {
    $client = $driveService->getClient();
    $chunkSize = 1024 * 1024;
    $client->setDefer(true);

    $fileMetadata = new Google_Service_Drive_DriveFile([
        'name' => $fileName,
        'parents' => [$folderId]
    ]);

    $request = $driveService->files->create($fileMetadata, [
        'mimeType' => $mimeType,
        'uploadType' => 'resumable',
        'supportsAllDrives' => true,
        'fields' => 'id, name, mimeType, size, createdTime, webViewLink, thumbnailLink'
    ]);

    $media = new Google_Http_MediaFileUpload($client, $request, $mimeType, null, true, $chunkSize);
    $media->setFileSize($fileSize);

    $status = false;
    $handle = fopen($filePath, 'rb');
    while (!$status && !feof($handle)) {
        $chunk = fread($handle, $chunkSize);
        $status = $media->nextChunk($chunk);
        if (isset($_SESSION)) $_SESSION['last_activity'] = time();
    }
    fclose($handle);
    $client->setDefer(false);

    return $status;
}

// ── MIME Type Lookup ─────────────────────────────────────
function getMimeTypeFromExtension($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $map = [
        'pdf'=>'application/pdf','doc'=>'application/msword',
        'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'=>'application/vnd.ms-excel',
        'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'=>'application/vnd.ms-powerpoint',
        'pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt'=>'application/vnd.oasis.opendocument.text',
        'ods'=>'application/vnd.oasis.opendocument.spreadsheet',
        'odp'=>'application/vnd.oasis.opendocument.presentation',
        'rtf'=>'application/rtf','txt'=>'text/plain','csv'=>'text/csv',
        'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
        'gif'=>'image/gif','bmp'=>'image/bmp','webp'=>'image/webp',
        'svg'=>'image/svg+xml','ico'=>'image/x-icon',
        'mp3'=>'audio/mpeg','wav'=>'audio/wav','ogg'=>'audio/ogg',
        'mp4'=>'video/mp4','avi'=>'video/x-msvideo','mkv'=>'video/x-matroska',
        'mov'=>'video/quicktime','webm'=>'video/webm',
        'zip'=>'application/zip','rar'=>'application/vnd.rar',
        '7z'=>'application/x-7z-compressed','tar'=>'application/x-tar',
        'gz'=>'application/gzip',
        'html'=>'text/html','css'=>'text/css','js'=>'application/javascript',
        'json'=>'application/json','xml'=>'application/xml',
        'php'=>'application/x-php','py'=>'text/x-python',
        'exe'=>'application/x-msdownload','apk'=>'application/vnd.android.package-archive',
    ];
    return $map[$ext] ?? 'application/octet-stream';
}
