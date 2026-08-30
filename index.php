<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/auth.php';

if (isset($_GET['logout'])) {
    logout();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wedding Website Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <nav class="topbar">
            <span class="topbar-brand">Wedding Website Generator</span>
            <div class="topbar-links">
                <?php if (is_logged_in()): ?>
                    <a href="<?= ($_SESSION['user']['role'] === 'admin') ? 'pages/admin/dashboard.php' : 'pages/user/dashboard.php' ?>">Dashboard</a>
                    <a href="index.php?logout=1">Logout</a>
                <?php else: ?>
                    <a href="pages/login.php">Login</a>
                    <a href="pages/register.php" class="topbar-cta">Daftar</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="hero-main">
            <span class="header-eyebrow">Undangan Digital</span>
            <h1>Website undangan pernikahan, siap dalam hitungan menit</h1>

            <div class="hero-columns">
                <div class="hero-desc">
                    <p>Isi data pasangan dan pilih elemen yang diinginkan lewat wizard singkat yang dipandu langkah demi langkah. Generator akan menyusun prompt lengkap dan siap pakai untuk AI developer, mencakup detail acara, tema warna, gaya visual, hingga instruksi teknis untuk fitur RSVP dan ucapan tamu. Tidak perlu menulis prompt dari nol — cukup isi formulirnya, salin hasilnya, dan langsung berikan ke AI favoritmu untuk dibangun jadi website utuh.</p>
                </div>
                <div class="hero-steps">
                    <div class="hero-steps-label">Alur Singkat</div>
                    <ol>
                        <li>Data Pernikahan</li>
                        <li>Tema &amp; Gaya Visual</li>
                        <li>Elemen Website &amp; Upload</li>
                        <li>Cerita &amp; Ucapan</li>
                        <li>Review &amp; Generate</li>
                    </ol>
                </div>
            </div>

            <div class="hero-actions">
                <?php if (is_logged_in()): ?>
                    <a href="pages/user/generator.php" class="btn-primary">Mulai Generate</a>
                <?php else: ?>
                    <a href="pages/login.php" class="btn-primary">Mulai Generate</a>
                    <span>Belum punya akun? <a href="pages/register.php">Daftar di sini</a></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>