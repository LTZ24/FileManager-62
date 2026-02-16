<?php
/**
 * Database Connection (PDO) — FileManager SMKN 62 Jakarta
 *
 * Returns a singleton PDO instance connected to the filemanager_smkn62 database.
 * Adjust DB_HOST / DB_USER / DB_PASS for production.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'filemanager_smkn62');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default — change for production
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection (singleton).
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            http_response_code(500);
            die('Koneksi database gagal. Silakan hubungi administrator.');
        }
    }

    return $pdo;
}
