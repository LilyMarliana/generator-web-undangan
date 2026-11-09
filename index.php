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

/* ------------------------------------------------------------------
 * PENGATURAN LANDING PAGE
 * Angka paket mengikuti sistem pembayaran: Free 5 prompt/hari,
 * Pro Rp18.000 untuk 30 hari dengan prompt tanpa batas harian.
 * Jika harga di lib/payment.php (PRO_PRICE) berubah, ubah juga di sini.
 * ------------------------------------------------------------------ */
$PLAN_FREE = [
    'price'  => 'Rp0',
    'period' => 'selamanya',
    'quota'  => 5,
    'photo'  => '2 MB',
    'music'  => '5 MB',
];
$PLAN_PRO = [
    'price'  => 'Rp18.000',
    'period' => '/ 30 hari',
    'quota'  => 'Tanpa batas',
    'photo'  => '2 MB',
    'music'  => '5 MB',
];

$loggedIn  = is_logged_in();
$dashUrl   = $loggedIn ? (($_SESSION['user']['role'] === 'admin') ? BASE_URL . '/pages/admin/dashboard.php' : BASE_URL . '/pages/user/dashboard.php') : '';
$genUrl    = BASE_URL . '/pages/user/generator.php';
$regUrl    = BASE_URL . '/pages/register.php';
$loginUrl  = BASE_URL . '/pages/login.php';
$ctaUrl    = $loggedIn ? $genUrl : $regUrl;
$ctaLabel  = $loggedIn ? 'Mulai Generate' : 'Mulai Gratis';
$cssVer    = @filemtime(__DIR__ . '/assets/style.css') ?: time();
$jsVer     = @filemtime(__DIR__ . '/assets/landing.js') ?: time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitia — Undangan Digital Siap Pakai</title>
    <meta name="description" content="Invitia: generate prompt AI lengkap untuk website undangan pernikahan dalam hitungan menit. Isi data pasangan, pilih tema, upload foto & musik, dapatkan prompt siap pakai untuk AI developer favoritmu.">
    <meta name="theme-color" content="#fbf3e5">
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,600;0,700;1,500&family=Pinyon+Script&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css?v=<?= $cssVer ?>">
    <noscript><style>.rv{opacity:1!important;transform:none!important}.loader{display:none!important}body.is-loading{overflow:auto!important}.story{height:auto!important}.story-sticky{position:static!important;height:auto!important}</style></noscript>
</head>
<body class="is-loading">

    <!-- PRELOADER -->
    <div class="loader" id="loader" aria-hidden="true">
        <div class="loader-inner">
            <img src="assets/logo-symbol.png" alt="" width="168" height="93">
            <div class="loader-bar"><span id="loaderFill"></span></div>
            <p>Menyiapkan undangan… <b id="loaderPct">0</b>%</p>
        </div>
    </div>

    <!-- LAPISAN AMBIEN -->
    <div class="progress" id="progress" aria-hidden="true"><span></span></div>
    <canvas class="petals" id="petals" aria-hidden="true"></canvas>
    <div class="cursor" id="cursor" aria-hidden="true"><span></span></div>

    <!-- TOP BAR -->
    <nav class="topbar" id="topbar" role="navigation" aria-label="Navigasi utama">
        <a href="<?= BASE_URL ?>/index.php" class="topbar-brand">
            <img src="assets/logo-wordmark.png" alt="Invitia" width="180" height="34">
        </a>
        <div class="topbar-menu">
            <a href="#cara">Cara Kerja</a>
            <a href="#fitur">Fitur</a>
            <a href="#tema">Tema</a>
            <a href="#harga">Harga</a>
            <a href="#faq">FAQ</a>
        </div>
        <div class="topbar-links">
            <?php if ($loggedIn): ?>
                <a href="<?= $dashUrl ?>">Dashboard</a>
                <a href="<?= BASE_URL ?>/index.php?logout=1" class="topbar-cta">Logout</a>
            <?php else: ?>
                <a href="<?= $loginUrl ?>">Login</a>
                <a href="<?= $regUrl ?>" class="topbar-cta">Mulai Gratis</a>
            <?php endif; ?>
        </div>
    </nav>

    <main>
        <!-- ================= HERO ================= -->
        <section class="hero" id="top" aria-labelledby="hero-title">
            <div class="hero-bg" aria-hidden="true">
                <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
                <svg class="ring r1" viewBox="0 0 100 100"><circle cx="50" cy="50" r="48" fill="none" stroke="currentColor" stroke-width=".4" stroke-dasharray="1.5 2.5"/></svg>
                <svg class="ring r2" viewBox="0 0 100 100"><circle cx="50" cy="50" r="48" fill="none" stroke="currentColor" stroke-width=".4"/></svg>
            </div>

            <div class="hero-inner">
                <div class="hero-copy">
                    <span class="eyebrow rv"><i></i> UNDANGAN DIGITAL BERBASIS AI</span>
                    <h1 id="hero-title" class="split">Wujudkan Undangan Pernikahan <em>Impianmu</em></h1>
                    <p class="hero-lead rv" style="--d:.5s">Isi data pasangan, pilih tema &amp; elemen, upload foto &amp; musik — dapatkan prompt lengkap untuk AI developer favoritmu. Tidak perlu coding, tidak perlu menulis prompt dari nol.</p>
                    <div class="hero-actions rv" style="--d:.65s">
                        <a href="<?= $ctaUrl ?>" class="btn btn-primary btn-lg magnetic"><span><?= $ctaLabel ?></span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                        <?php if (!$loggedIn): ?>
                            <a href="<?= $loginUrl ?>" class="btn btn-ghost btn-lg">Sudah punya akun? Login</a>
                        <?php else: ?>
                            <a href="<?= $dashUrl ?>" class="btn btn-ghost btn-lg">Buka Dashboard</a>
                        <?php endif; ?>
                    </div>
                    <ul class="hero-trust rv" style="--d:.8s">
                        <li>Gratis <?= $PLAN_FREE['quota'] ?> prompt/hari</li>
                        <li>Tanpa kartu kredit</li>
                        <li>Login Google tersedia</li>
                    </ul>
                </div>

                <!-- Mockup ponsel: pratinjau undangan yang bergerak sendiri -->
                <div class="hero-visual rv" style="--d:.4s" aria-hidden="true">
                    <div class="phone" id="phone">
                        <div class="phone-notch"></div>
                        <div class="phone-screen">
                            <div class="inv-scroll">
                                <div class="inv-cover">
                                    <p class="inv-small">THE WEDDING OF</p>
                                    <p class="inv-names">Marliana <span>&amp;</span> Choi Seungcheol</p>
                                    <p class="inv-date">Sabtu, 14 Februari 2027</p>
                                    <div class="inv-open">Buka Undangan</div>
                                </div>
                                <div class="inv-block">
                                    <p class="inv-small">MENUJU HARI BAHAGIA</p>
                                    <div class="inv-count">
                                        <div><b data-cd="d">00</b><small>Hari</small></div>
                                        <div><b data-cd="h">00</b><small>Jam</small></div>
                                        <div><b data-cd="m">00</b><small>Menit</small></div>
                                        <div><b data-cd="s">00</b><small>Detik</small></div>
                                    </div>
                                </div>
                                <div class="inv-block">
                                    <p class="inv-small">GALERI</p>
                                    <div class="inv-gal"><i></i><i></i><i></i><i></i></div>
                                </div>
                                <div class="inv-block">
                                    <p class="inv-small">RSVP</p>
                                    <div class="inv-form"><span></span><span></span><span class="btn-fake">Kirim Konfirmasi</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chip c1" data-px="26"><svg viewBox="0 0 24 24"><path d="M12 21s-7-4.4-9.3-9A5.3 5.3 0 0112 6a5.3 5.3 0 019.3 6c-2.3 4.6-9.3 9-9.3 9z"/></svg> RSVP masuk: <b>Hadir</b></div>
                    <div class="chip c2" data-px="-18"><svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg> Musik latar aktif</div>
                    <div class="chip c3" data-px="34"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg> Prompt siap disalin</div>
                </div>
            </div>

            <a href="#cara" class="scroll-hint" aria-label="Gulir ke bawah"><span></span></a>
        </section>

        <!-- ================= MARQUEE TEMA ================= -->
        <section class="marquee" aria-label="Palet warna yang tersedia">
            <div class="marquee-track">
                <?php
                $themes = [
                    ['Dusty Rose', '#d9a4a0'], ['Sage Green', '#a3b18a'], ['Champagne / Gold', '#d8c28b'], ['Ivory', '#efe7d6'],
                    ['Blush Pink', '#f2c6cf'], ['Lavender', '#c3b1e1'], ['Emerald', '#2f7d5b'], ['Navy Blue', '#26385c'],
                    ['Terracotta', '#c1633f'], ['Maroon', '#6e1f2f'], ['Peach / Coral', '#f4a58a'], ['Dusty Blue', '#8fa8c4'],
                    ['Mint Green', '#b5e2cf'], ['Rose Gold', '#c98f87'], ['Teal', '#2b8a8c'], ['Mustard', '#d9a520'],
                ];
                for ($loop = 0; $loop < 2; $loop++):
                    foreach ($themes as [$n, $c]): ?>
                        <span class="m-item"<?= $loop ? ' aria-hidden="true"' : '' ?>><i style="background:<?= $c ?>"></i><?= $n ?></span>
                    <?php endforeach;
                endfor; ?>
            </div>
        </section>

        <!-- ================= CARA KERJA — SCROLL STORY ================= -->
        <section class="story" id="cara" aria-labelledby="story-title">
            <div class="story-sticky">
                <header class="section-header story-head">
                    <span class="eyebrow rv"><i></i> CARA KERJA</span>
                    <h2 id="story-title" class="rv">Dari Amplop Kosong Menjadi Undangan Utuh</h2>
                </header>

                <div class="story-grid">
                    <ol class="story-steps" id="storySteps">
                        <li class="on" data-i="0">
                            <span class="sn">01</span>
                            <div><h3>Isi Data Pernikahan</h3><p>Nama pasangan, tanggal &amp; jam akad/resepsi, lokasi, hingga orang tua. Semua tersusun lewat form terpandu dengan validasi langsung.</p></div>
                        </li>
                        <li data-i="1">
                            <span class="sn">02</span>
                            <div><h3>Pilih Tema &amp; Gaya</h3><p>17 palet warna dan 13 gaya visual — dari Minimalis Elegan, Rustic, Floral, sampai Kultural/Adat. Pilihanmu otomatis masuk ke prompt.</p></div>
                        </li>
                        <li data-i="2">
                            <span class="sn">03</span>
                            <div><h3>Pilih Elemen &amp; Upload</h3><p>Centang elemen yang diinginkan (countdown, galeri, RSVP, musik, Google Maps, wedding gift…) lalu upload foto &amp; musik. URL file disisipkan otomatis.</p></div>
                        </li>
                        <li data-i="3">
                            <span class="sn">04</span>
                            <div><h3>Tulis Cerita &amp; Ucapan</h3><p>Kutipan pembuka, kisah cinta, dan kata sambutan. Cukup tulis garis besarnya — AI yang merapikan bahasanya.</p></div>
                        </li>
                        <li data-i="4">
                            <span class="sn">05</span>
                            <div><h3>Copy Prompt ke AI</h3><p>Prompt terstruktur siap tempel ke ChatGPT, Claude, Cursor, atau AI lain. Hasilnya: website undangan responsif yang siap dibagikan.</p></div>
                        </li>
                    </ol>

                    <div class="stage" aria-hidden="true">
                        <div class="env" id="env">
                            <div class="env-back"></div>
                            <div class="env-card" id="envCard">
                                <div class="panel on" data-i="0">
                                    <p class="p-title">Data Pernikahan</p>
                                    <div class="field"><label>Mempelai pria</label><b data-type="Choi Seungcheol">Choi Seungcheol</b></div>
                                    <div class="field"><label>Mempelai wanita</label><b data-type="Marliana">Marliana</b></div>
                                    <div class="field-row">
                                        <div class="field"><label>Akad</label><b>14 Feb 2027</b></div>
                                        <div class="field"><label>Jam</label><b>09.00</b></div>
                                    </div>
                                    <div class="field"><label>Lokasi</label><b>Gedung Serbaguna Makassar</b></div>
                                </div>
                                <div class="panel" data-i="1">
                                    <p class="p-title">Tema &amp; Gaya Visual</p>
                                    <div class="swatches">
                                        <i style="--c:#d9a4a0"></i><i style="--c:#a3b18a"></i><i style="--c:#d8c28b"></i><i style="--c:#26385c"></i>
                                        <i style="--c:#c1633f"></i><i style="--c:#c3b1e1"></i><i style="--c:#2f7d5b"></i><i style="--c:#6e1f2f"></i>
                                    </div>
                                    <div class="tags"><span class="sel">Minimalis elegan</span><span>Rustic</span><span>Floral</span><span>Art Deco</span></div>
                                </div>
                                <div class="panel" data-i="2">
                                    <p class="p-title">Elemen &amp; Upload</p>
                                    <div class="tags"><span class="sel">Countdown</span><span class="sel">Galeri foto</span><span class="sel">RSVP</span><span class="sel">Musik</span><span>Dress code</span><span class="sel">Google Maps</span></div>
                                    <div class="upl"><label>foto-prewed.jpg</label><div><span style="--w:100%"></span></div></div>
                                    <div class="upl"><label>lagu-latar.mp3</label><div><span style="--w:72%"></span></div></div>
                                </div>
                                <div class="panel" data-i="3">
                                    <p class="p-title">Cerita &amp; Ucapan</p>
                                    <p class="quote">“Dan di antara tanda-tanda kekuasaan-Nya ialah Dia menciptakan untukmu pasangan…”</p>
                                    <div class="lines"><span style="--w:92%"></span><span style="--w:78%"></span><span style="--w:86%"></span><span style="--w:55%"></span></div>
                                </div>
                                <div class="panel" data-i="4">
                                    <p class="p-title">Prompt Siap Pakai</p>
                                    <div class="code">
                                        <span style="--w:70%"></span><span style="--w:92%"></span><span style="--w:55%"></span><span style="--w:84%"></span><span style="--w:64%"></span><span style="--w:78%"></span>
                                    </div>
                                    <div class="copy-fake"><svg viewBox="0 0 24 24"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 012-2h9"/></svg> Copy Prompt</div>
                                </div>
                            </div>
                            <div class="env-flap" id="envFlap"></div>
                            <div class="env-front"></div>
                            <div class="env-seal"><svg viewBox="0 0 24 24"><path d="M12 21s-7-4.4-9.3-9A5.3 5.3 0 0112 6a5.3 5.3 0 019.3 6c-2.3 4.6-9.3 9-9.3 9z"/></svg></div>
                        </div>
                        <div class="story-meter"><span id="storyBar"></span></div>
                        <p class="story-cap" id="storyCap">Gulir untuk membuka amplop</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= STATS ================= -->
        <section class="stats" aria-label="Angka-angka Invitia">
            <div class="wrap stats-grid">
                <div class="stat rv"><b data-count="17">0</b><span>Palet warna</span></div>
                <div class="stat rv" style="--d:.1s"><b data-count="13">0</b><span>Gaya visual</span></div>
                <div class="stat rv" style="--d:.2s"><b data-count="14">0</b><span>Elemen website</span></div>
                <div class="stat rv" style="--d:.3s"><b data-count="5">0</b><span>Langkah mudah</span></div>
            </div>
        </section>
                <!-- ================= FITUR ================= -->
        <section class="features" id="fitur" aria-labelledby="features-title">
            <div class="wrap">
                <header class="section-header">
                    <span class="eyebrow rv"><i></i> KENAPA MEMILIH INVITIA</span>
                    <h2 id="features-title" class="rv">Semua yang Kamu Butuhkan untuk Undangan Digital</h2>
                    <p class="rv">Bukan template kaku. Invitia menyusun prompt yang detail, sehingga AI bisa membangun undangan yang benar-benar terasa milik kalian.</p>
                </header>

                <div class="bento">
                    <article class="card spot rv b-wide">
                        <div class="ico"><svg viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z"/></svg></div>
                        <h3>Wizard 5 Langkah</h3>
                        <p>Form terstruktur memandu dari data pasangan hingga review final, dengan validasi langsung supaya tidak ada data penting yang terlewat. Isi sambil santai, tidak perlu menyiapkan apa pun sebelumnya.</p>
                        <div class="mini-steps"><span class="a">1</span><span>2</span><span>3</span><span>4</span><span>5</span></div>
                    </article>

                    <article class="card spot rv" style="--d:.08s">
                        <div class="ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 000 18c1.5 0 2-1 1.5-2-.6-1.2.2-2.5 1.6-2.5H17a4 4 0 004-4A9 9 0 0012 3z"/></svg></div>
                        <h3>17 Palet &amp; 13 Gaya</h3>
                        <p>Dusty Rose, Sage Green, Champagne Gold, Navy… dipadukan gaya Rustic, Floral, Art Deco, atau Kultural/Adat. Langsung terefleksi di prompt.</p>
                    </article>

                    <article class="card spot rv" style="--d:.16s">
                        <div class="ico"><svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M17 8l-5-5-5 5M12 3v12"/></svg></div>
                        <h3>Upload Foto &amp; Musik</h3>
                        <p>Unggah foto pasangan (<?= $PLAN_FREE['photo'] ?>) dan musik latar (<?= $PLAN_FREE['music'] ?>). URL file otomatis disisipkan ke prompt.</p>
                    </article>

                    <article class="card spot rv" style="--d:.08s">
                        <div class="ico"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></div>
                        <h3>RSVP &amp; Ucapan Tamu</h3>
                        <p>API publik siap pakai dengan API key unik per undangan. Tamu konfirmasi kehadiran &amp; menulis ucapan, kamu pantau dari dashboard.</p>
                    </article>

                    <article class="card spot rv" style="--d:.24s">
                        <div class="ico"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/></svg></div>
                        <h3>Kelola &amp; Edit Kapan Saja</h3>
                        <p>Semua prompt tersimpan di dashboard. Edit, hapus, atau regenerate kapan pun — termasuk ekspor daftar RSVP.</p>
                    </article>

                    <article class="card spot rv b-full" style="--d:.1s">
                        <div class="ico"><svg viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg></div>
                        <h3>14 Elemen Website, Kamu yang Pilih</h3>
                        <p>Cover pembuka, countdown, galeri, susunan acara, dress code, wedding gift, Google Maps, sosial media, dan lainnya. Aktifkan yang perlu, abaikan yang tidak.</p>
                        <div class="tags small"><span>Countdown</span><span>Galeri</span><span>RSVP</span><span>Musik</span><span>Maps</span><span>Wedding gift</span><span>Dress code</span><span>+7 lagi</span></div>
                    </article>
                </div>
            </div>
        </section>

        <!-- ================= PLAYGROUND TEMA ================= -->
        <section class="theme" id="tema" aria-labelledby="theme-title">
            <div class="wrap theme-grid">
                <div class="theme-copy">
                    <span class="eyebrow rv"><i></i> COBA SENDIRI</span>
                    <h2 id="theme-title" class="rv">Pilih Nuansa, Lihat Langsung Hasilnya</h2>
                    <p class="rv">Geser pilihan warna dan gaya di bawah. Begitulah cara Invitia meneruskan seleramu ke AI: bukan sekadar “bagus ya”, tapi instruksi desain yang spesifik.</p>

                    <p class="mini-label rv">Palet warna</p>
                    <div class="pal rv" id="palPick">
                        <button type="button" class="on" data-name="Dusty Rose" data-bg="#fbeeec" data-ac="#c9847f" data-ink="#4a2b2b" aria-label="Dusty Rose" style="--c:#d9a4a0"></button>
                        <button type="button" data-name="Sage Green" data-bg="#f1f4ea" data-ac="#7a8f62" data-ink="#2f3a26" aria-label="Sage Green" style="--c:#a3b18a"></button>
                        <button type="button" data-name="Champagne / Gold" data-bg="#fbf5e3" data-ac="#b8943f" data-ink="#3b3220" aria-label="Champagne / Gold" style="--c:#d8c28b"></button>
                        <button type="button" data-name="Navy Blue" data-bg="#e9edf5" data-ac="#26385c" data-ink="#16223a" aria-label="Navy Blue" style="--c:#26385c"></button>
                        <button type="button" data-name="Terracotta" data-bg="#f8ece4" data-ac="#c1633f" data-ink="#4a2418" aria-label="Terracotta" style="--c:#c1633f"></button>
                        <button type="button" data-name="Lavender" data-bg="#f3eefa" data-ac="#8e75c4" data-ink="#33284f" aria-label="Lavender" style="--c:#c3b1e1"></button>
                    </div>

                    <p class="mini-label rv">Gaya visual</p>
                    <div class="sty rv" id="styPick">
                        <button type="button" class="on" data-name="Minimalis elegan" data-font="'Fraunces', serif" data-script="0">Minimalis elegan</button>
                        <button type="button" data-name="Floral / Botanical" data-font="'Pinyon Script', cursive" data-script="1">Floral</button>
                        <button type="button" data-name="Rustic" data-font="'Work Sans', sans-serif" data-script="0">Rustic</button>
                        <button type="button" data-name="Art Deco" data-font="'Fraunces', serif" data-script="2">Art Deco</button>
                    </div>

                    <div class="prompt-peek rv">
                        <span class="pp-k">// potongan prompt</span>
                        <p>Tema warna: <b id="ppColor">Dusty Rose</b><br>Gaya visual: <b id="ppStyle">Minimalis elegan</b></p>
                    </div>
                </div>

                <div class="theme-stage rv" style="--d:.15s">
                    <div class="tv" id="tv" data-script="0">
                        <div class="tv-orn" aria-hidden="true"></div>
                        <p class="tv-small">KAMI MENGUNDANG ANDA</p>
                        <p class="tv-names">Marliana &amp; Choi Seungcheol</p>
                        <div class="tv-line"></div>
                        <p class="tv-date">14 · 02 · 2027</p>
                        <p class="tv-place">Gedung Serbaguna, Makassar</p>
                        <span class="tv-btn">Konfirmasi Kehadiran</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= PROMPT DEMO ================= -->
        <section class="promptdemo" aria-labelledby="pd-title">
            <div class="wrap pd-grid">
                <div class="pd-copy">
                    <span class="eyebrow light rv"><i></i> HASIL AKHIR</span>
                    <h2 id="pd-title" class="rv">Satu Prompt, Website Undangan Utuh</h2>
                    <p class="rv">Prompt yang dihasilkan terstruktur: konteks acara, spesifikasi teknis, instruksi desain, sampai cara menyambungkan RSVP dengan API kamu. Tinggal salin, tempel, selesai.</p>
                    <div class="ai-chips rv">
                        <span>ChatGPT</span><span>Claude</span><span>Cursor</span><span>Gemini</span><span>& AI lain</span>
                    </div>
                </div>

                <div class="term rv" style="--d:.15s">
                    <div class="term-bar"><span></span><span></span><span></span><em>prompt-undangan.txt</em><button type="button" class="term-copy" id="termCopy">Salin</button></div>
                    <pre id="termBody" aria-label="Contoh prompt"></pre>
                </div>
            </div>
        </section>

        <!-- ================= HARGA ================= -->
        <section class="pricing" id="harga" aria-labelledby="price-title">
            <div class="wrap">
                <header class="section-header">
                    <span class="eyebrow rv"><i></i> HARGA</span>
                    <h2 id="price-title" class="rv">Mulai Gratis, Naik Kelas Saat Butuh</h2>
                    <p class="rv">Paket Free sudah cukup untuk satu undangan lengkap. Pro untuk kamu yang butuh membuat prompt tanpa batas harian.</p>
                </header>

                <div class="plans">
                    <article class="plan rv">
                        <header>
                            <h3>Free</h3>
                            <p class="plan-sub">Untuk mencoba &amp; satu undangan pribadi</p>
                            <p class="plan-price"><b><?= $PLAN_FREE['price'] ?></b><span><?= $PLAN_FREE['period'] ?></span></p>
                        </header>
                        <ul>
                            <li><?= $PLAN_FREE['quota'] ?> prompt per hari</li>
                            <li>Wizard 5 langkah lengkap</li>
                            <li>17 palet warna &amp; 13 gaya visual</li>
                            <li>14 elemen website</li>
                            <li>Upload foto <?= $PLAN_FREE['photo'] ?> &amp; musik <?= $PLAN_FREE['music'] ?></li>
                            <li>API RSVP &amp; ucapan tamu</li>
                            <li>Dashboard kelola &amp; edit prompt</li>
                        </ul>
                        <a href="<?= $ctaUrl ?>" class="btn btn-ghost btn-block"><?= $loggedIn ? 'Buka Generator' : 'Daftar Gratis' ?></a>
                    </article>

                    <article class="plan plan-pro rv" style="--d:.12s">
                        <span class="plan-badge">Paling lengkap</span>
                        <header>
                            <h3>Pro</h3>
                            <p class="plan-sub">Untuk yang butuh banyak prompt</p>
                            <p class="plan-price"><b><?= $PLAN_PRO['price'] ?></b><span><?= $PLAN_PRO['period'] ?></span></p>
                        </header>
                        <ul>
                            <li>Semua fitur Free, plus:</li>
                            <li><b>Prompt tanpa batas</b> setiap hari</li>
                            <li>Aktif <b>30 hari</b> per pembayaran</li>
                            <li>Bayar aman lewat <b>Midtrans</b></li>
                            <li>Perpanjang kapan saja, tanpa langganan otomatis</li>
                        </ul>
                        <a href="<?= $loggedIn ? $dashUrl : $regUrl ?>" class="btn btn-primary btn-block magnetic"><span><?= $loggedIn ? 'Upgrade ke Pro' : 'Daftar &amp; upgrade ke Pro' ?></span></a>
                        <p class="plan-note">Bayar sekali untuk 30 hari. Tidak diperpanjang otomatis.</p>
                    </article>
                </div>

                <div class="compare rv">
                    <table>
                        <caption class="sr">Perbandingan paket Free dan Pro</caption>
                        <thead><tr><th scope="col">Fitur</th><th scope="col">Free</th><th scope="col" class="hl">Pro</th></tr></thead>
                        <tbody>
                            <tr><th scope="row">Prompt per hari</th><td><?= $PLAN_FREE['quota'] ?></td><td class="hl"><?= $PLAN_PRO['quota'] ?></td></tr>
                            <tr><th scope="row">Wizard 5 langkah</th><td class="y">✓</td><td class="y hl">✓</td></tr>
                            <tr><th scope="row">17 palet &amp; 13 gaya visual</th><td class="y">✓</td><td class="y hl">✓</td></tr>
                            <tr><th scope="row">14 elemen website</th><td class="y">✓</td><td class="y hl">✓</td></tr>
                            <tr><th scope="row">Batas upload foto</th><td><?= $PLAN_FREE['photo'] ?></td><td class="hl"><?= $PLAN_PRO['photo'] ?></td></tr>
                            <tr><th scope="row">Batas upload musik</th><td><?= $PLAN_FREE['music'] ?></td><td class="hl"><?= $PLAN_PRO['music'] ?></td></tr>
                            <tr><th scope="row">API RSVP &amp; ucapan</th><td class="y">✓</td><td class="y hl">✓</td></tr>
                            <tr><th scope="row">Ekspor RSVP</th><td class="y">✓</td><td class="y hl">✓</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ================= FAQ ================= -->
        <section class="faq" id="faq" aria-labelledby="faq-title">
            <div class="wrap faq-wrap">
                <header class="section-header">
                    <span class="eyebrow rv"><i></i> PERTANYAAN UMUM</span>
                    <h2 id="faq-title" class="rv">Masih Ada yang Ingin Ditanyakan?</h2>
                </header>
                <div class="faq-list rv">
                    <details open>
                        <summary>Apa sebenarnya yang Invitia hasilkan?</summary>
                        <p>Invitia menghasilkan <b>prompt</b> — instruksi lengkap yang kamu tempel ke AI (ChatGPT, Claude, Cursor, dll). AI-lah yang kemudian menulis kode website undangannya. Dengan begitu, hasilnya fleksibel dan bisa kamu minta revisi sesuka hati.</p>
                    </details>
                    <details>
                        <summary>Saya tidak bisa coding, apakah tetap bisa?</summary>
                        <p>Bisa. Kamu hanya mengisi form dan menyalin hasilnya. Sebagian besar AI developer bahkan bisa langsung menampilkan hasilnya tanpa kamu menyentuh kode.</p>
                    </details>
                    <details>
                        <summary>Bagaimana RSVP dan ucapan tamu bekerja?</summary>
                        <p>Setiap undangan punya API key unik. Prompt sudah memuat cara memanggil API tersebut, jadi website hasil AI bisa langsung mengirim RSVP dan ucapan yang kemudian muncul di dashboard-mu.</p>
                    </details>
                    <details>
                        <summary>Kuota harian saya berapa dan kapan direset?</summary>
                        <p>Paket Free mendapat <?= $PLAN_FREE['quota'] ?> prompt per hari, dan kuota reset otomatis setiap hari. Paket Pro tidak dibatasi per hari.</p>
                    </details>
                    <details>
                        <summary>Apakah foto dan musik saya aman?</summary>
                        <p>File diunggah ke server aplikasi dan otomatis dihapus ketika prompt terkait dihapus. Batas ukuran: foto <?= $PLAN_FREE['photo'] ?>, musik <?= $PLAN_FREE['music'] ?> pada paket Free.</p>
                    </details>
                    <details>
                        <summary>Boleh edit setelah prompt dibuat?</summary>
                        <p>Boleh. Semua prompt tersimpan di dashboard — kamu dapat mengedit data, mengganti tema, atau regenerate kapan pun.</p>
                    </details>
                    <details>
                        <summary>Bagaimana cara upgrade ke Pro?</summary>
                        <p>Masuk ke dashboard, pilih upgrade ke Pro, lalu bayar lewat Midtrans. Paket aktif otomatis setelah pembayaran lunas dan berlaku 30 hari.</p>
                    </details>
                </div>
            </div>
        </section>

        <!-- ================= CTA PENUTUP ================= -->
        <section class="final" aria-labelledby="cta-title">
            <div class="final-ring" aria-hidden="true"><span></span><span></span><span></span></div>
            <div class="wrap final-in">
                <span class="eyebrow light rv"><i></i> MULAI SEKARANG</span>
                <h2 id="cta-title" class="rv">Siap Membuat Undangan Pertamamu?</h2>
                <p class="rv">Daftar gratis atau masuk dengan Google, lalu buat prompt undangan lengkapmu dalam hitungan menit.</p>
                <div class="hero-actions center rv">
                    <?php if ($loggedIn): ?>
                        <a href="<?= $genUrl ?>" class="btn btn-light btn-lg magnetic"><span>Buat Undangan</span></a>
                    <?php else: ?>
                        <a href="<?= $regUrl ?>" class="btn btn-light btn-lg magnetic"><span>Daftar Gratis</span></a>
                        <a href="<?= $loginUrl ?>" class="btn btn-outline-light btn-lg">Masuk</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/lib/site_footer.php'; ?>

    <script src="assets/landing.js?v=<?= $jsVer ?>" defer></script>
</body>
</html>