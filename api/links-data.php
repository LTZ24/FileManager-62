<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';

requireLogin();

$category = isset($_GET['category']) ? sanitize((string)$_GET['category']) : '';
$categories = getLinkCategories();

try {
    if ($category !== '' && isset($categories[$category])) {
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
