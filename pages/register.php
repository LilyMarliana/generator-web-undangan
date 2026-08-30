<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/auth.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/pages/user/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Token tidak valid.';
    } else {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

        if ($name === '' || $email === '' || $pass === '') {
            $error = 'Semua field wajib diisi.';
        } elseif ($pass !== $pass2) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password minimal 6 karakter.';
        } else {
            $db = getDB();
            $chk = $db->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'Email sudah terdaftar.';
            } else {
                $stmt = $db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'user')");
                $stmt->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                flash('register', 'Pendaftaran berhasil. Silakan login.');
                redirect(BASE_URL . '/pages/login.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Wedding Website Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-eyebrow">Wedding Website Generator</span>
            <h1>Daftar Akun</h1>
            <p>Buat akun baru untuk mulai generate prompt undangan.</p>
        </div>
        <div class="form-container">
            <?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label for="name">Nama Lengkap:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password (min. 6 karakter):</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="password_confirm">Konfirmasi Password:</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
                </div>
                <button type="submit" class="btn-primary btn-block">Daftar</button>
            </form>
            <div class="auth-links">
                Sudah punya akun? <a href="login.php">Login di sini</a><br>
                <a href="../index.php">Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</body>
</html>