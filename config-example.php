<?php
session_start();

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'generator_web_undangan');
define('BASE_URL', 'http://localhost');

$DB = null;

function getDB() {
    global $DB;
    if (!$DB) {
        try {
            $DB = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("DB connection failed: " . $e->getMessage());
        }
    }
    return $DB;
}

function redirect($to) {
    header("Location: " . $to);
    exit;
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function flash($key, $msg = null) {
    if ($msg === null) {
        return $_SESSION['flash'][$key] ?? null;
    }
    $_SESSION['flash'][$key] = $msg;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}