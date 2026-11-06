<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/auth.php';

if (isset($_GET['logout'])) {
    logout();
}

// Supabase kadang mengembalikan login Google ke halaman utama (membawa ?code=...).
// Teruskan ke handler OAuth, hanya kalau memang ada proses login Google yang sedang berjalan.
if (!empty($_GET['code']) && is_string($_GET['code']) && !empty($_SESSION['oauth_code_verifier'])) {
    redirect(BASE_URL . '/pages/auth.php?provider=' . urlencode($_SESSION['oauth_provider'] ?? 'google') . '&code=' . urlencode($_GET['code']));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PromptNikah — Undangan Digital Siap Pakai</title>
    <meta name="description" content="Generate prompt AI lengkap untuk website undangan pernikahan dalam hitungan menit. Isi data pasangan, pilih tema, upload foto & musik, dapatkan prompt siap pakai untuk AI developer favoritmu.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="topbar" role="navigation" aria-label="Navigasi utama">
        <span class="topbar-brand">
            <img src="assets/title.svg" alt="PromptNikah">
        </span>
        <div class="topbar-links">
            <?php if (is_logged_in()): ?>
                <a href="<?= ($_SESSION['user']['role'] === 'admin') ? BASE_URL . '/pages/admin/dashboard.php' : BASE_URL . '/pages/user/dashboard.php' ?>">Dashboard</a>
                <a href="<?= BASE_URL . '/index.php?logout=1' ?>">Logout</a>
            <?php else: ?>
                <a href="<?= BASE_URL . '/pages/login.php' ?>">Login</a>
                <a href="<?= BASE_URL . '/pages/register.php' ?>" class="topbar-cta">Mulai Gratis</a>
            <?php endif; ?>
        </div>
    </nav>

    <main>
        <!-- HERO -->
        <section class="hero-main" aria-labelledby="hero-title">
            <div class="hero-content">
                <span class="header-eyebrow">UNDANGAN DIGITAL</span>
                <h1 id="hero-title">Wujudkan Undangan Pernikahan Impianmu</h1>
                <p class="hero-lead">Isi data pasangan, pilih tema & elemen, upload foto & musik — dapatkan prompt lengkap untuk AI developer favoritmu. Tidak perlu coding, tidak perlu menulis prompt dari nol.</p>

                <div class="hero-actions">
                    <?php if (is_logged_in()): ?>
                        <a href="<?= BASE_URL . '/pages/user/generator.php' ?>" class="btn-primary btn-lg">Mulai Generate</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL . '/pages/register.php' ?>" class="btn-primary btn-lg">Mulai Gratis</a>
                        <a href="<?= BASE_URL . '/pages/login.php' ?>" class="btn-outline btn-lg">Sudah punya akun? Login</a>
                    <?php endif; ?>
                </div>

                <p class="hero-trust">Gratis 5 prompt/hari · Tidak perlu kartu kredit · Data aman di servermu</p>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <div class="preview-card">
                    <div class="preview-header">
                        <span class="preview-dots" aria-hidden="true"><span></span><span></span><span></span></span>
                    </div>
                    <div class="preview-content">
                        <div class="preview-step active" data-step="1">
                            <span class="step-badge">1</span>
                            <strong>Data Pernikahan</strong>
                            <small>Nama pasangan, tanggal, lokasi, orang tua</small>
                        </div>
                        <div class="preview-step" data-step="2">
                            <span class="step-badge">2</span>
                            <strong>Tema & Gaya</strong>
                            <small>Warna, tipografi, nuansa visual</small>
                        </div>
                        <div class="preview-step" data-step="3">
                            <span class="step-badge">3</span>
                            <strong>Elemen & Upload</strong>
                            <small>Galeri, musik, RSVP, ucapan tamu</small>
                        </div>
                        <div class="preview-step" data-step="4">
                            <span class="step-badge">4</span>
                            <strong>Cerita & Ucapan</strong>
                            <small>Kisah cinta, sambutan, doa</small>
                        </div>
                        <div class="preview-step" data-step="5">
                            <span class="step-badge">5</span>
                            <strong>Review & Generate</strong>
                            <small>Prompt AI siap copy-paste</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="features" aria-labelledby="features-title">
            <div class="container">
                <header class="section-header">
                    <span class="header-eyebrow">KENAPA MEMILIH KAMI</span>
                    <h2 id="features-title">Semua yang Kamu Butuhkan untuk Undangan Digital</h2>
                </header>

                <div class="features-grid">
                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <h3>Wizard 5 Langkah</h3>
                        <p>Form terstruktur memandu dari data pasangan hingga review final. Validasi real-time mencegah data kosong.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                        </div>
                        <h3>Tema & Gaya Visual</h3>
                        <p>Pilih palet warna, tipografi, dan nuansa (elegan, modern, rustic, minimalis) — langsung terefleksi di prompt.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        </div>
                        <h3>Upload Foto & Musik</h3>
                        <p>Drag-drop foto pasangan (≤2MB) & musik latar (≤5MB). URL file otomatis disisipkan ke prompt AI.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"></path></svg>
                        </div>
                        <h3>RSVP & Ucapan Tamu</h3>
                        <p>API publik siap pakai untuk form RSVP dan buku tamu digital. Terintegrasi otomatis ke prompt.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"></rect><path d="M8 21h8"></path><path d="M12 17v4"></path></svg>
                        </div>
                        <h3>Prompt Siap Pakai</h3>
                        <p>Output prompt terstruktur: konteks, spesifikasi teknis, instruksi AI, hingga fallback. Copy sekali klik.</p>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        </div>
                        <h3>Kelola & Edit Kapan Saja</h3>
                        <p>Semua prompt tersimpan di dashboard. Edit, hapus, atau regenerate kapan pun. Kuota harian 5×/hari.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section class="how-it-works" aria-labelledby="how-title">
            <div class="container">
                <header class="section-header">
                    <span class="header-eyebrow">MUDAH DALAM 3 LANGKAH</span>
                    <h2 id="how-title">Dari Ide Menjadi Undangan Digital</h2>
                </header>

                <ol class="steps-timeline">
                    <li>
                        <div class="step-marker" aria-hidden="true"><span>1</span></div>
                        <div class="step-content">
                            <h3>Daftar & Masuk</h3>
                            <p>Buat akun gratis atau masuk dengan Google. Langsung ke dashboard setelah login.</p>
                        </div>
                    </li>
                    <li>
                        <div class="step-marker" aria-hidden="true"><span>2</span></div>
                        <div class="step-content">
                            <h3>Isi Wizard Generator</h3>
                            <p>Lengkapi 5 langkah: data acara, tema, elemen & upload, cerita, review. Simpan otomatis per langkah.</p>
                        </div>
                    </li>
                    <li>
                        <div class="step-marker" aria-hidden="true"><span>3</span></div>
                        <div class="step-content">
                            <h3>Copy Prompt ke AI</h3>
                            <p>Paste ke ChatGPT, Claude, Cursor, atau AI lain. Dapatkan kode website undangan utuh & responsif.</p>
                        </div>
                    </li>
                </ol>
            </div>
        </section>

        <!-- AJAKAN MULAI -->
        <section class="demo-section" aria-labelledby="cta-title">
            <div class="container">
                <header class="section-header">
                    <span class="header-eyebrow">MULAI SEKARANG</span>
                    <h2 id="cta-title">Siap Membuat Undangan Pertamamu?</h2>
                    <p>Daftar gratis atau masuk dengan Google, lalu buat prompt undangan lengkapmu dalam hitungan menit.</p>
                </header>

                <div class="hero-actions" style="justify-content:center;">
                    <?php if (is_logged_in()): ?>
                        <a href="<?= BASE_URL . '/pages/user/generator.php' ?>" class="btn-primary btn-lg">Buat Undangan</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL . '/pages/register.php' ?>" class="btn-primary btn-lg">Daftar Gratis</a>
                        <a href="<?= BASE_URL . '/pages/login.php' ?>" class="btn-outline btn-lg">Masuk</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer" role="contentinfo">
        <div class="container">
            <p>&copy; <?= date('Y') ?> PromptNikah. Dibangun dengan PHP 8 + MySQL.</p>
            <nav aria-label="Footer links">
                <a href="<?= BASE_URL . '/pages/login.php' ?>">Login</a>
                <a href="<?= BASE_URL . '/pages/register.php' ?>">Daftar</a>
                <a href="<?= BASE_URL . '/api.php?action=info' ?>" target="_blank" rel="noopener">API Docs</a>
            </nav>
        </div>
    </footer>

    <script>
    // Animate preview steps on hero
    const steps = document.querySelectorAll('.preview-step');
    let current = 0;
    function cycleSteps() {
        steps.forEach((s, i) => s.classList.toggle('active', i === current));
        current = (current + 1) % steps.length;
    }
    setInterval(cycleSteps, 2500);
    </script>
</body>
</html>