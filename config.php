<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'toko_tahu_krispy');
define('DB_USER', 'root');
define('DB_PASS', ''); // sesuaikan jika MySQL Anda memakai password

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
    return $pdo;
}
