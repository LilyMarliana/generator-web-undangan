<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/supabase.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/pages/user/dashboard.php');
}

// 1. Tangani jika ada error callback dari provider / Supabase
if (!empty($_GET['error']) || !empty($_GET['error_description'])) {
    $errMsg = $_GET['error_description'] ?? $_GET['error'] ?? 'Login dibatalkan.';
    flash('error', 'Login Google gagal: ' . $errMsg);
    redirect(BASE_URL . '/pages/login.php');
}

// 2. Tangani jika ini adalah callback yang membawa authorization code
if (!empty($_GET['code'])) {
    $res = supabase_handle_oauth_callback();
    if (!empty($res['error'])) {
        flash('error', 'Gagal memverifikasi login Google: ' . $res['error']);
        redirect(BASE_URL . '/pages/login.php');
    }

    $sbUser = $res['user'] ?? [];
    $email  = $sbUser['email'] ?? '';
    if (!$email) {
        flash('error', 'Tidak dapat mengambil alamat email dari akun Google.');
        redirect(BASE_URL . '/pages/login.php');
    }

    $name = $sbUser['user_metadata']['full_name']
        ?? $sbUser['user_metadata']['name']
        ?? $sbUser['user_metadata']['displayName']
        ?? explode('@', $email)[0];

    // Sinkronisasi ke MySQL lokal agar relasi prompts, rsvp, dan kuota bekerja normal
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, '', 'user')");
        $stmt->execute([$name, $email]);
        $user = [
            'id'    => (int)$db->lastInsertId(),
            'name'  => $name,
            'email' => $email,
            'role'  => 'user',
        ];
    }

    unset($user['password']);
    $_SESSION['user'] = $user;
    $_SESSION['supabase_token'] = $res['access_token'] ?? null;

    redirect(BASE_URL . '/pages/user/dashboard.php');
}

// 3. Jika bukan callback (user baru klik tombol login Google)
$provider = $_GET['provider'] ?? '';
$allowedProviders = ['google', 'apple', 'github', 'gitlab', 'bitbucket'];

if (!$provider || !in_array($provider, $allowedProviders, true)) {
    flash('error', 'Provider autentikasi tidak valid.');
    redirect(BASE_URL . '/pages/login.php');
}

// Mulai alur OAuth dengan PKCE dan redirect ke Supabase
supabase_get_oauth_provider($provider);
