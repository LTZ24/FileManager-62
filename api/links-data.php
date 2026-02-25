<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';

requireLogin();

$category = isset($_GET['category']) ? sanitize((string)$_GET['category']) : '';
$categories = getLinkCategories();

// Pagination for links (server-side via Sheets ranges)
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(0, (int)$_GET['per_page']) : 0; // 0 = no pagination

try {
    if ($perPage > 0 && $category !== '' && isset($categories[$category])) {
        // Paged fetch for a single category
        $service = getSheetsService();
        $sheetId = $categories[$category]['sheets_id'];
        $sheetTitle = getLinkSheetName($category);
        $startRow = 2 + ($page - 1) * $perPage;
        $endRow = $startRow + $perPage - 1;
        $range = buildA1Range($sheetTitle, "A{$startRow}:E{$endRow}");

        try {
            $result = $service->spreadsheets_values->get($sheetId, $range);
            $values = $result->getValues() ?? [];
        } catch (Exception $e) {
            $values = [];
        }

        $links = [];
        foreach ($values as $index => $row) {
            if (!empty($row[0]) && !empty($row[1])) {
                $links[] = [
                    'id' => (($page - 1) * $perPage) + $index,
                    'title' => $row[0] ?? '',
                    'url' => $row[1] ?? '',
                    'date' => $row[2] ?? date('Y-m-d')
                ];
            }
        }
        // Attach metadata
        foreach ($links as &$link) {
            $link['category'] = $category;
            $link['category_name'] = $categories[$category]['name'];
            $link['category_color'] = $categories[$category]['color'];
            $link['category_icon'] = $categories[$category]['icon'];
        }
        unset($link);

        $hasMore = count($values) >= $perPage;
        ajaxSuccess('OK', ['links' => $links, 'page' => $page, 'per_page' => $perPage, 'has_more' => $hasMore]);
        exit;
    } elseif ($category !== '' && isset($categories[$category])) {
        $links = getLinksFromSheets($category);
        foreach ($links as &$link) {
            $link['category'] = $category;
            $link['category_name'] = $categories[$category]['name'];
            $link['category_color'] = $categories[$category]['color'];
            $link['category_icon'] = $categories[$category]['icon'];
        }
        unset($link);
    } else {
        $links = [];
        foreach ($categories as $key => $cat) {
            $categoryLinks = getLinksFromSheets($key);
            foreach ($categoryLinks as $link) {
                $link['category'] = $key;
                $link['category_name'] = $cat['name'];
                $link['category_color'] = $cat['color'];
                $link['category_icon'] = $cat['icon'];
                $links[] = $link;
            }
        }
    }

    ajaxSuccess('OK', ['links' => $links]);
} catch (Exception $e) {
    ajaxError($e->getMessage());
}
