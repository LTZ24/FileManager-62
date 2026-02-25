<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';

requireLogin();

$categoryKey = isset($_GET['category']) ? sanitize((string)$_GET['category']) : '';
$categoryParam = isset($_GET['categoryParam']) ? sanitize((string)$_GET['categoryParam']) : '';

// Pagination (server-side)
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(0, (int)$_GET['per_page']) : 0; // 0 = no pagination (legacy)
$pageToken = isset($_GET['page_token']) ? sanitize((string)$_GET['page_token']) : '';

$paramToKey = [
    'kesiswaan' => 'kesiswaan',
    'kurikulum' => 'kurikulum',
    'sapras-humas' => 'sapras',
    'tata-usaha' => 'tata_usaha',
    'dokumentasi' => 'dokumentasi',
];

if ($categoryKey === '' && $categoryParam !== '' && isset($paramToKey[$categoryParam])) {
    $categoryKey = $paramToKey[$categoryParam];
}

$categories = getDriveCategories();

try {
    $driveService = getDriveService();

    $selected = [];
    if ($categoryKey !== '' && isset($categories[$categoryKey])) {
        $selected[$categoryKey] = $categories[$categoryKey];
    } else {
        $selected = $categories;
    }

    // Match existing slugs used in UI
    $keyToParam = [
        'kesiswaan' => 'kesiswaan',
        'kurikulum' => 'kurikulum',
        'sapras' => 'sapras-humas',
        'tata_usaha' => 'tata-usaha',
        'dokumentasi' => 'dokumentasi',
    ];

    $allFiles = [];

    foreach ($selected as $key => $cat) {
        $folderId = $cat['folder_id'];

        $parameters = [
            'q' => "'{$folderId}' in parents and trashed=false",
            'fields' => 'files(id, name, mimeType, size, createdTime, modifiedTime, webViewLink, iconLink, thumbnailLink),nextPageToken',
            'orderBy' => 'modifiedTime desc',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ];

        // If perPage is provided, use it for server-side pagination
        if ($perPage > 0) {
            $parameters['pageSize'] = min(100, $perPage);
            if ($pageToken !== '') $parameters['pageToken'] = $pageToken;
        } else {
            // legacy behaviour - fetch up to 1000
            $parameters['pageSize'] = 1000;
        }

        $results = $driveService->files->listFiles($parameters);
        $files = $results->getFiles();
        $nextPageToken = method_exists($results, 'getNextPageToken') ? $results->getNextPageToken() : null;

        foreach ($files as $file) {
            $modifiedTime = $file->getModifiedTime();
            $createdTime = $file->getCreatedTime();

            $allFiles[] = [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize() ?? 0,
                'sizeFormatted' => formatFileSize($file->getSize() ?? 0),
                'createdTime' => $createdTime,
                'createdTimestamp' => $createdTime ? strtotime($createdTime) : null,
                'modifiedTime' => $modifiedTime,
                'modifiedTimestamp' => $modifiedTime ? strtotime($modifiedTime) : null,
                'modifiedFormatted' => $modifiedTime ? formatDateTime($modifiedTime) : '-',
                'webViewLink' => $file->getWebViewLink(),
                'iconLink' => $file->getIconLink(),
                'thumbnailLink' => $file->getThumbnailLink(),
                'category' => $key,
                'categoryParam' => $keyToParam[$key] ?? $key,
                'categoryName' => $cat['name'],
                'categoryIcon' => $cat['icon'],
                'categoryColor' => $cat['color'],
                'date' => $modifiedTime ? date('Y-m-d', strtotime($modifiedTime)) : null,
            ];
        }
    }

    $resp = ['files' => $allFiles];
    if ($perPage > 0) {
        $resp['page'] = $page;
        $resp['per_page'] = $perPage;
        $resp['next_page_token'] = $nextPageToken ?? null;
        $resp['count'] = count($allFiles);
    }
    ajaxSuccess('OK', $resp);
} catch (Exception $e) {
    ajaxError($e->getMessage());
}
