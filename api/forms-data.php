<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';

requireLogin();

$category = isset($_GET['category']) ? sanitize((string)$_GET['category']) : '';
$categories = getFormCategories();

// Pagination for forms (server-side via Sheets ranges)
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(0, (int)$_GET['per_page']) : 0; // 0 = no pagination

try {
    if ($perPage > 0 && $category !== '' && isset($categories[$category])) {
        // Paged fetch for a single category
        $service = getSheetsService();
        $sheetId = $categories[$category]['sheets_id'];
        $sheetTitle = getFormSheetName($category);
        $startRow = 2 + ($page - 1) * $perPage;
        $endRow = $startRow + $perPage - 1;
        $range = buildA1Range($sheetTitle, "A{$startRow}:E{$endRow}");

        try {
            $result = $service->spreadsheets_values->get($sheetId, $range);
            $values = $result->getValues() ?? [];
        } catch (Exception $e) {
            $values = [];
        }

        $forms = [];
        foreach ($values as $index => $row) {
            if (!empty($row[0]) && !empty($row[1])) {
                $forms[] = [
                    'id' => (($page - 1) * $perPage) + $index,
                    'title' => $row[0] ?? '',
                    'url' => $row[1] ?? '',
                    'date' => $row[2] ?? date('Y-m-d')
                ];
            }
        }
        // Attach metadata
        foreach ($forms as &$form) {
            $form['category'] = $category;
            $form['category_name'] = $categories[$category]['name'];
            $form['category_color'] = $categories[$category]['color'];
            $form['category_icon'] = $categories[$category]['icon'];
        }
        unset($form);

        $hasMore = count($values) >= $perPage;
        ajaxSuccess('OK', ['forms' => $forms, 'page' => $page, 'per_page' => $perPage, 'has_more' => $hasMore]);
        exit;
    } elseif ($category !== '' && isset($categories[$category])) {
        $forms = getFormsFromSheets($category);
        foreach ($forms as &$form) {
            $form['category'] = $category;
            $form['category_name'] = $categories[$category]['name'];
            $form['category_color'] = $categories[$category]['color'];
            $form['category_icon'] = $categories[$category]['icon'];
        }
        unset($form);
    } else {
        $forms = [];
        foreach ($categories as $key => $cat) {
            $categoryForms = getFormsFromSheets($key);
            foreach ($categoryForms as $form) {
                $form['category'] = $key;
                $form['category_name'] = $cat['name'];
                $form['category_color'] = $cat['color'];
                $form['category_icon'] = $cat['icon'];
                $forms[] = $form;
            }
        }
    }

    ajaxSuccess('OK', ['forms' => $forms]);
} catch (Exception $e) {
    ajaxError($e->getMessage());
}
