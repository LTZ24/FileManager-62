<?php
/**
 * Category Data API - Lazy Loading Endpoint
 * Returns files, links, and forms for a specific category in one request
 * Optimized for faster page loading
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';

requireLogin();

$categoryKey = isset($_GET['category']) ? sanitize((string)$_GET['category']) : '';
$dataType = isset($_GET['type']) ? sanitize((string)$_GET['type']) : 'all'; // all, files, links, forms

// Map URL slugs to category keys
$slugToKey = [
    'kesiswaan' => 'kesiswaan',
    'kurikulum' => 'kurikulum',
    'sapras-humas' => 'sapras',
    'tata-usaha' => 'tata_usaha',
];

if (isset($slugToKey[$categoryKey])) {
    $categoryKey = $slugToKey[$categoryKey];
}

// Validate category
$validCategories = ['kesiswaan', 'kurikulum', 'sapras', 'tata_usaha'];
if (!in_array($categoryKey, $validCategories)) {
    ajaxError('Invalid category');
}

// Get category configuration
$categories = getDriveCategories();
if (!isset($categories[$categoryKey])) {
    ajaxError('Category not found');
}

$category = $categories[$categoryKey];
$folderId = $category['folder_id'];
$sheetId = $category['sheets_id'];

// Cache configuration
$cacheTime = 300; // 5 minutes

// Prepare response
$response = [
    'category' => $categoryKey,
    'categoryName' => $category['name'],
    'files' => [],
    'links' => [],
    'forms' => [],
    'counts' => [
        'files' => 0,
        'links' => 0,
        'forms' => 0
    ]
];

try {
    // Fetch files if needed
    if ($dataType === 'all' || $dataType === 'files') {
        $filesCacheKey = "api_category_{$categoryKey}_files";
        
        if (isset($_SESSION[$filesCacheKey]) && 
            isset($_SESSION[$filesCacheKey . '_time']) && 
            (time() - $_SESSION[$filesCacheKey . '_time']) < $cacheTime) {
            $response['files'] = $_SESSION[$filesCacheKey];
        } else {
            $driveService = getDriveService();
            
            $parameters = [
                'q' => "'{$folderId}' in parents and trashed=false",
                'fields' => 'files(id, name, mimeType, size, createdTime, modifiedTime, webViewLink, iconLink, thumbnailLink)',
                'orderBy' => 'modifiedTime desc',
                'pageSize' => 1000,
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true
            ];
            
            $results = $driveService->files->listFiles($parameters);
            $files = [];
            
            foreach ($results->getFiles() as $file) {
                $modifiedTime = $file->getModifiedTime();
                $files[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'mimeType' => $file->getMimeType(),
                    'size' => $file->getSize() ?? 0,
                    'sizeFormatted' => formatFileSize($file->getSize() ?? 0),
                    'modifiedTime' => $modifiedTime,
                    'modifiedFormatted' => $modifiedTime ? formatDateTime($modifiedTime) : '-',
                    'webViewLink' => $file->getWebViewLink(),
                    'iconLink' => $file->getIconLink(),
                    'thumbnailLink' => $file->getThumbnailLink()
                ];
            }
            
            $_SESSION[$filesCacheKey] = $files;
            $_SESSION[$filesCacheKey . '_time'] = time();
            $response['files'] = $files;
        }
        $response['counts']['files'] = count($response['files']);
    }
    
    // Fetch links if needed
    if ($dataType === 'all' || $dataType === 'links') {
        $linksCacheKey = "api_category_{$categoryKey}_links";
        
        if (isset($_SESSION[$linksCacheKey]) && 
            isset($_SESSION[$linksCacheKey . '_time']) && 
            (time() - $_SESSION[$linksCacheKey . '_time']) < $cacheTime) {
            $response['links'] = $_SESSION[$linksCacheKey];
        } else {
            $sheetsService = getSheetsService();
            $sheetTitle = 'Links-' . ucfirst($categoryKey === 'tata_usaha' ? 'Tata_usaha' : $categoryKey);
            
            try {
                $range = $sheetTitle . '!A2:E';
                $result = $sheetsService->spreadsheets_values->get($sheetId, $range);
                $values = $result->getValues() ?? [];
                
                $links = [];
                foreach ($values as $index => $row) {
                    if (!empty($row[0]) && !empty($row[1])) {
                        $links[] = [
                            'id' => $index,
                            'title' => $row[0] ?? '',
                            'url' => $row[1] ?? '',
                            'date' => $row[2] ?? date('Y-m-d'),
                            'dateFormatted' => isset($row[2]) ? formatDateTime($row[2]) : '-'
                        ];
                    }
                }
                
                $_SESSION[$linksCacheKey] = $links;
                $_SESSION[$linksCacheKey . '_time'] = time();
                $response['links'] = $links;
            } catch (Exception $e) {
                $response['links'] = [];
            }
        }
        $response['counts']['links'] = count($response['links']);
    }
    
    // Fetch forms if needed
    if ($dataType === 'all' || $dataType === 'forms') {
        $formsCacheKey = "api_category_{$categoryKey}_forms";
        
        if (isset($_SESSION[$formsCacheKey]) && 
            isset($_SESSION[$formsCacheKey . '_time']) && 
            (time() - $_SESSION[$formsCacheKey . '_time']) < $cacheTime) {
            $response['forms'] = $_SESSION[$formsCacheKey];
        } else {
            $sheetsService = getSheetsService();
            $sheetTitle = 'Forms-' . ucfirst($categoryKey === 'tata_usaha' ? 'Tata_usaha' : $categoryKey);
            
            try {
                $range = $sheetTitle . '!A2:E';
                $result = $sheetsService->spreadsheets_values->get($sheetId, $range);
                $values = $result->getValues() ?? [];
                
                $forms = [];
                foreach ($values as $index => $row) {
                    if (!empty($row[0]) && !empty($row[1])) {
                        $forms[] = [
                            'id' => $index,
                            'title' => $row[0] ?? '',
                            'url' => $row[1] ?? '',
                            'date' => $row[2] ?? date('Y-m-d'),
                            'dateFormatted' => isset($row[2]) ? formatDateTime($row[2]) : '-'
                        ];
                    }
                }
                
                $_SESSION[$formsCacheKey] = $forms;
                $_SESSION[$formsCacheKey . '_time'] = time();
                $response['forms'] = $forms;
            } catch (Exception $e) {
                $response['forms'] = [];
            }
        }
        $response['counts']['forms'] = count($response['forms']);
    }
    
    ajaxSuccess('OK', $response);
    
} catch (Exception $e) {
    error_log('category-data.php error: ' . $e->getMessage());
    ajaxError($e->getMessage());
}
