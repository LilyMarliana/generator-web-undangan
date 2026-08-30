<?php
require_once __DIR__ . '/../config.php';

function is_logged_in() {
    return !empty($_SESSION['user']);
}

function require_login() {
    if (!is_logged_in()) {
        redirect(BASE_URL . '/pages/login.php');
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['user']['role'] !== 'admin') {
        redirect(BASE_URL . '/pages/user/dashboard.php');
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function attempt_login($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        $_SESSION['user'] = $user;
        return $user;
    }
    return null;
}

function logout() {
    unset($_SESSION['user']);
    redirect(BASE_URL . '/index.php');
}