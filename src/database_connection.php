<?php
require_once __DIR__ . '/../config/db_config.php';

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In a real application, log this error and show a user-friendly message
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Could not connect to the database. Please try again later.');
        }
    }
    return $pdo;
}
?>
