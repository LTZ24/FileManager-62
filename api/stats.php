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

// Cache configuration: 30 minutes
$cacheTime = 1800;
$cacheKey = 'api_stats_cache';

// Check cache
if (isset($_SESSION[$cacheKey]) && 
    isset($_SESSION[$cacheKey . '_time']) && 
    (time() - $_SESSION[$cacheKey . '_time']) < $cacheTime) {
    
    echo json_encode($_SESSION[$cacheKey]);
    exit;
}

try {
    $driveService = getDriveService();
    
    // Get total files count (optimized with pageSize 1)
    $filesResult = $driveService->files->listFiles([
        'q' => "mimeType != 'application/vnd.google-apps.folder' and trashed = false",
        'fields' => 'files(id)',
        'pageSize' => 1,
        'supportsAllDrives' => true,
        'includeItemsFromAllDrives' => true
    ]);
    
    // For accurate count, we need to iterate through pages
    // But for performance, we'll use a quick estimate
    $totalFiles = 0;
    $pageToken = null;
    $maxPages = 10; // Limit to prevent timeout
    $page = 0;
    
    do {
        $params = [
            'q' => "mimeType != 'application/vnd.google-apps.folder' and trashed = false",
            'fields' => 'nextPageToken, files(id)',
            'pageSize' => 100,
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true
        ];
        
        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }
        
        $result = $driveService->files->listFiles($params);
        $totalFiles += count($result->getFiles());
        $pageToken = $result->getNextPageToken();
        $page++;
        
    } while ($pageToken && $page < $maxPages);
    
    // Get links and forms count
    $links = getLinksFromSheets();
    $forms = getFormsFromSheets();
    
    $stats = [
        'totalFiles' => $totalFiles,
        'totalLinks' => count($links),
        'totalForms' => count($forms),
        'cached' => false,
        'timestamp' => time()
    ];
    
    // Cache the result
    $_SESSION[$cacheKey] = $stats;
    $_SESSION[$cacheKey . '_time'] = time();
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch stats',
        'message' => $e->getMessage()
    ]);
}
