<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';

requireLogin();

$categoryKey = isset($_GET['category']) ? sanitize((string)$_GET['category']) : '';
$categoryParam = isset($_GET['categoryParam']) ? sanitize((string)$_GET['categoryParam']) : '';

$paramToKey = [
    'kesiswaan' => 'kesiswaan',
    'kurikulum' => 'kurikulum',
    'sapras-humas' => 'sapras',
    'tata-usaha' => 'tata_usaha',
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
    ];

    $allFiles = [];

    foreach ($selected as $key => $cat) {
        $folderId = $cat['folder_id'];

        $parameters = [
            'q' => "'{$folderId}' in parents and trashed=false",
            'fields' => 'files(id, name, mimeType, size, createdTime, modifiedTime, webViewLink, iconLink, thumbnailLink)',
            'orderBy' => 'modifiedTime desc',
            'pageSize' => 1000,
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ];

        $results = $driveService->files->listFiles($parameters);
        $files = $results->getFiles();

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

    ajaxSuccess('OK', ['files' => $allFiles]);
} catch (Exception $e) {
    ajaxError($e->getMessage());
}
