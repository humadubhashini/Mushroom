<?php
/**
 * Database connection for XAMPP's default MySQL/MariaDB instance.
 * Default XAMPP credentials: host=localhost, user=root, password=''.
 * Update these if your local XAMPP setup differs.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'mushroom_system');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Make sure XAMPP\'s MySQL service is running and the '
        . '"mushroom_system" database has been imported (see database/schema.sql). Details: '
        . htmlspecialchars($e->getMessage()));
}
