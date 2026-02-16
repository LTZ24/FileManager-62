<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Cache configuration: 30 minutes
$cacheTime = 1800;
$cacheKey = 'api_categories_cache';

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
    // Get all links and forms
    $links = getLinksFromSheets();
    $forms = getFormsFromSheets();
    
    // Group by category
    $categories = [
        'kesiswaan' => [
            'name' => 'Kesiswaan',
            'links' => 0,
            'forms' => 0
        ],
        'kurikulum' => [
            'name' => 'Kurikulum',
            'links' => 0,
            'forms' => 0
        ],
        'sapras' => [
            'name' => 'Sapras & Humas',
            'links' => 0,
            'forms' => 0
        ],
        'tata_usaha' => [
            'name' => 'Tata Usaha',
            'links' => 0,
            'forms' => 0
        ]
    ];
    
    // Count links per category
    foreach ($links as $link) {
        $cat = strtolower(str_replace([' ', '&'], ['_', ''], $link['category']));
        if ($cat === 'sapras_humas') $cat = 'sapras';
        if (isset($categories[$cat])) {
            $categories[$cat]['links']++;
        }
    }
    
    // Count forms per category
    foreach ($forms as $form) {
        $cat = strtolower(str_replace([' ', '&'], ['_', ''], $form['category']));
        if ($cat === 'sapras_humas') $cat = 'sapras';
        if (isset($categories[$cat])) {
            $categories[$cat]['forms']++;
        }
    }
    
    $data = [
        'categories' => $categories,
        'cached' => false,
        'timestamp' => time()
    ];
    
    // Cache the result
    $_SESSION[$cacheKey] = $data;
    $_SESSION[$cacheKey . '_time'] = time();
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch categories',
        'message' => $e->getMessage()
    ]);
}
