<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/ajax_helpers.php';

requireLogin();

requireRateLimit('links_delete', null, null, BASE_URL . '/pages/links/index');

requirePostMethod(BASE_URL . '/pages/links/index');
requireValidCsrfToken(BASE_URL . '/pages/links/index');

$id = isset($_POST['id']) ? intval($_POST['id']) : -1;
$category = isset($_POST['category']) ? (string)$_POST['category'] : '';
$redirect = isset($_POST['redirect']) ? (string)$_POST['redirect'] : '';
$confirm = isset($_POST['confirm']) ? (string)$_POST['confirm'] : '';

if ($id < 0 || empty($category)) {
    if (isAjaxRequest()) {
        ajaxError('ID atau kategori tidak valid');
    }
    redirect(BASE_URL . '/pages/links/index');
}

if ($confirm === '1') {
    try {
        if (deleteLinkFromSheets($id, $category)) {
            // Clear cache
            foreach (array_keys($_SESSION) as $key) {
                if (strpos($key, 'links_cache_') === 0 || strpos($key, 'category_') === 0) {
                    unset($_SESSION[$key]);
                }
            }
            
            $redirectUrl = BASE_URL . '/pages/links/index?success=Link berhasil dihapus&category=' . $category;
            if ($redirect === 'category') {
                $redirectUrl = BASE_URL . '/pages/category/' . $category . '?success=Link berhasil dihapus';
            }
            
            if (isAjaxRequest()) {
                ajaxSuccess('Link berhasil dihapus', ['id' => $id, 'category' => $category]);
            }
            redirect($redirectUrl);
        } else {
            $redirectUrl = BASE_URL . '/pages/links/index?error=Gagal menghapus link&category=' . $category;
            if ($redirect === 'category') {
                $redirectUrl = BASE_URL . '/pages/category/' . $category . '?error=Gagal menghapus link';
            }
            
            if (isAjaxRequest()) {
                ajaxError('Gagal menghapus link');
            }
            redirect($redirectUrl);
        }
    } catch (Exception $e) {
        $redirectUrl = BASE_URL . '/pages/links/index?error=' . urlencode($e->getMessage()) . '&category=' . $category;
        if ($redirect === 'category') {
            $redirectUrl = BASE_URL . '/pages/category/' . $category . '?error=' . urlencode($e->getMessage());
        }
        
        if (isAjaxRequest()) {
            ajaxError($e->getMessage());
        }
        redirect($redirectUrl);
    }
} else {
    if (isAjaxRequest()) {
        ajaxError('Konfirmasi diperlukan');
    }
    redirect(BASE_URL . '/pages/links/index');
}
