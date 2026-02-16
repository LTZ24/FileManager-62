<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in (local or Google)
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Cache configuration: 15 minutes (shorter cache for recent uploads)
$cacheTime = 900;
$cacheKey = 'api_recent_cache_storage_v1';

// Check cache
if (isset($_SESSION[$cacheKey]) && 
    isset($_SESSION[$cacheKey . '_time']) && 
    (time() - $_SESSION[$cacheKey . '_time']) < $cacheTime) {
    
    $data = $_SESSION[$cacheKey];
    $data['cached'] = true;
    echo json_encode($data);
    exit;
}

try {
    $driveService = getDriveService();
    
    // Get recent files (5 most recent)
    $recentFiles = $driveService->files->listFiles([
        'q' => "mimeType != 'application/vnd.google-apps.folder' and trashed = false",
        'orderBy' => 'createdTime desc',
        'fields' => 'files(id, name, createdTime, mimeType, size, webViewLink)',
        'pageSize' => 5,
        'supportsAllDrives' => true,
        'includeItemsFromAllDrives' => true
    ]);
    
    $uploads = [];
    foreach ($recentFiles->getFiles() as $file) {
        $uploads[] = [
            'id' => $file->getId(),
            'name' => $file->getName(),
            'createdTime' => $file->getCreatedTime(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize(),
            'webViewLink' => $file->getWebViewLink()
        ];
    }
    
    $data = [
        'uploads' => $uploads,
        'cached' => false,
        'source' => 'drive',
        'timestamp' => time()
    ];
    
    // Cache the result
    $_SESSION[$cacheKey] = $data;
    $_SESSION[$cacheKey . '_time'] = time();
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch recent uploads',
        'message' => $e->getMessage()
    ]);
}
