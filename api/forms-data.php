<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/ajax_helpers.php';

requireLogin();

$category = isset($_GET['category']) ? sanitize((string)$_GET['category']) : '';
$categories = getFormCategories();

try {
    if ($category !== '' && isset($categories[$category])) {
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
