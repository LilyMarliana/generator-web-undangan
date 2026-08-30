<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/auth.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/pages/user/dashboard.php');
}

// Seeder: buat akun demo jika belum ada
$db = getDB();
$demoAccounts = [
    ['Administrator', 'admin@example.com', 'admin123', 'admin'],
    ['Pengguna Demo', 'user@example.com',  'user123',  'user'],
];
foreach ($demoAccounts as [$name, $email, $pass, $role]) {
    $chk = $db->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $chk->execute([$email]);
    if (!$chk->fetch()) {
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Token tidak valid. Coba lagi.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $user = attempt_login($email, $pass);
        if ($user) {
            redirect(($user['role'] === 'admin')
                ? BASE_URL . '/pages/admin/dashboard.php'
                : BASE_URL . '/pages/user/dashboard.php');
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Wedding Website Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-eyebrow">Wedding Website Generator</span>
            <h1>Login</h1>
            <p>Masuk ke akunmu untuk lanjut generate.</p>
        </div>
        <div class="form-container">
            <?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
            <?php if (flash('register')): ?>
                <div class="success-msg"><?= e(flash('register')) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary btn-block">Login</button>
            </form>

            <div class="demo-box">
                <p>Akun Demo (Auto-fill):</p>
                <div class="demo-buttons">
                    <button type="button" class="btn-secondary" onclick="autofill('admin@example.com', 'admin123')">Login sebagai Admin</button>
                    <button type="button" class="btn-secondary" onclick="autofill('user@example.com', 'user123')">Login sebagai Pengguna</button>
                </div>
            </div>
            <div class="auth-links">
                Belum punya akun? <a href="register.php">Daftar di sini</a><br>
                <a href="../index.php">Kembali ke Beranda</a>
            </div>
        </div>
    </div>

    <script>
    function autofill(email, pass) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = pass;
    }
    </script>
</body>
</html>