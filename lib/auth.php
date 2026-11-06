<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/supabase.php';

function is_logged_in() {
    return !empty($_SESSION['user']);
}

/**
 * Guard login. Role dan status aktif dicek ulang ke database di setiap request,
 * jadi perubahan role / blokir dari admin langsung berlaku tanpa user perlu login ulang.
 */
function require_login() {
    if (!is_logged_in()) {
        redirect(BASE_URL . '/pages/login.php');
    }
    $st = getDB()->prepare("SELECT role, is_active FROM users WHERE id = ? LIMIT 1");
    $st->execute([(int)$_SESSION['user']['id']]);
    $row = $st->fetch();
    if (!$row || !(int)$row['is_active']) {
        unset($_SESSION['user'], $_SESSION['supabase_token']);
        flash('error', 'Akun kamu dinonaktifkan atau tidak ditemukan. Hubungi admin.');
        redirect(BASE_URL . '/pages/login.php');
    }
    $_SESSION['user']['role'] = $row['role'];
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

function attempt_login($email, $password, &$authError = null) {
    $inactiveMsg = 'Akun kamu dinonaktifkan. Hubungi admin.';

    // 1. Jika Supabase dikonfigurasi, autentikasi via Supabase Auth
    if (defined('SUPABASE_URL') && defined('SUPABASE_KEY') && !empty(SUPABASE_URL) && !empty(SUPABASE_KEY)) {
        $res = supabase_sign_in($email, $password);
        if (isset($res['error'])) {
            $authError = $res['error'];
            return null;
        }

        $sbUser = $res['user'] ?? [];
        $email  = $sbUser['email'] ?? $email;
        $name   = $sbUser['user_metadata']['name'] ?? explode('@', $email)[0];

        // Sinkronisasi ke MySQL lokal agar relasi prompts, rsvp, kuota tetap bekerja
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, '', 'user')");
            $stmt->execute([$name, $email]);
            $user = [
                'id'        => (int)$db->lastInsertId(),
                'name'      => $name,
                'email'     => $email,
                'role'      => 'user',
                'is_active' => 1,
            ];
        }

        if (!(int)$user['is_active']) {
            $authError = $inactiveMsg;
            return null;
        }

        unset($user['password']);
        $_SESSION['user'] = $user;
        $_SESSION['supabase_token'] = $res['access_token'] ?? null;
        return $user;
    }

    // 2. Fallback: Autentikasi lokal MySQL jika Supabase belum diisi URL-nya
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        if (!(int)$user['is_active']) {
            $authError = $inactiveMsg;
            return null;
        }
        unset($user['password']);
        $_SESSION['user'] = $user;
        return $user;
    }

    $authError = 'Email atau password tidak sesuai.';
    return null;
}

function logout() {
    unset($_SESSION['user']);
    unset($_SESSION['supabase_token']);
    redirect(BASE_URL . '/index.php');
}