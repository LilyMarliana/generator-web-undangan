<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/auth.php';

if (is_logged_in()) {
    redirect(BASE_URL . '/pages/user/dashboard.php');
}

$error = '';
$flashError = flash('error');
if ($flashError) {
    $error = $flashError;
    unset($_SESSION['flash']['error']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Token keamanan tidak valid. Silakan coba lagi.';
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
        } elseif (defined('SUPABASE_URL') && !empty(SUPABASE_URL)) {
            $res = supabase_sign_up($email, $pass, ['name' => $name]);
            if (isset($res['error'])) {
                $error = $res['error'];
            } else {
                $db = getDB();
                $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, '', 'user')");
                $stmt->execute([$name, $email]);
                flash('register', 'Pendaftaran berhasil. Silakan login.');
                redirect(BASE_URL . '/pages/login.php');
            }
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
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar · Undangan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="<?= e(BASE_URL) ?>/assets/theme-init.js"></script>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/auth.css">
</head>
<body>
    <div class="ambient-canvas" aria-hidden="true">
        <div class="ambient-light-1"></div>
        <div class="ambient-light-2"></div>
        <div class="ambient-light-3"></div>
    </div>

    <main class="template-shell">
        <section class="showcase-panel" aria-label="Informasi Studio">
            <header>
                <div class="brand-eyebrow">Studio Undangan Digital</div>
            </header>

            <div class="card-preview-wrap">
                <article class="card-preview">
                    <span class="preview-tag">Preview Undangan</span>
                    <h2 class="preview-names">Ananda <em>&amp;</em> Kirana</h2>
                    <div class="preview-meta">
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            Sabtu, 28 Nov 2026
                        </span>
                        <span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            Plataran Cilandak
                        </span>
                    </div>
                </article>
            </div>

            <footer class="showcase-footer">
                <p>Bergabunglah dan ciptakan undangan pernikahan impian Anda.</p>
            </footer>
        </section>

        <section class="form-panel" aria-label="Formulir Pendaftaran">
            <div class="corner-controls">
                <a href="../index.php" class="corner-btn" aria-label="Kembali ke beranda">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                <button type="button" class="corner-btn" data-theme-toggle aria-label="Ganti tema">
                    <svg data-theme-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"></svg>
                </button>
            </div>

            <div class="form-header">
                <h1>Buat Akun Baru</h1>
                <p>Daftar untuk mulai generate prompt undangan.</p>
            </div>

            <?php if ($error): ?>
                <div class="auth-alert auth-alert-error" role="alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                <div class="input-group">
                    <div class="input-label-row">
                        <label for="name">Nama Lengkap</label>
                    </div>
                    <div class="input-field-wrap">
                        <input type="text" id="name" name="name" class="glass-input" placeholder="Nama Anda" required autocomplete="name" value="<?= e(trim($_POST['name'] ?? '')) ?>">
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-label-row">
                        <label for="email">Alamat Email</label>
                    </div>
                    <div class="input-field-wrap">
                        <input type="email" id="email" name="email" class="glass-input" placeholder="nama@email.com" required autocomplete="email" value="<?= e(trim($_POST['email'] ?? '')) ?>">
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-label-row">
                        <label for="password">Kata Sandi (min. 6 karakter)</label>
                    </div>
                    <div class="input-field-wrap">
                        <input type="password" id="password" name="password" class="glass-input pw-input" placeholder="••••••••" required minlength="6" autocomplete="new-password">
                        <button type="button" class="pw-btn" data-pw-toggle="password" aria-label="Tampilkan kata sandi">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></svg>
                        </button>
                    </div>
                </div>

                <div class="input-group">
                    <div class="input-label-row">
                        <label for="password_confirm">Konfirmasi Kata Sandi</label>
                    </div>
                    <div class="input-field-wrap">
                        <input type="password" id="password_confirm" name="password_confirm" class="glass-input pw-input" placeholder="••••••••" required minlength="6" autocomplete="new-password">
                        <button type="button" class="pw-btn" data-pw-toggle="password_confirm" aria-label="Tampilkan kata sandi">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-glass-primary">
                    <span>Daftar Sekarang</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
            </form>

            <div class="divider">atau daftar dengan</div>

            <div class="social-login">
                <a href="<?= e(BASE_URL) ?>/pages/auth.php?provider=google" class="btn-social btn-google">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Daftar dengan Google
                </a>
            </div>

            <footer class="form-footer">
                Sudah punya akun? <a href="login.php">Masuk di sini</a>
            </footer>
        </section>
    </main>

    <script src="<?= e(BASE_URL) ?>/assets/theme.js"></script>
    <script src="<?= e(BASE_URL) ?>/assets/pw-toggle.js"></script>
</body>
</html>
