<?php
/**
 * Layout bersama untuk semua halaman dashboard (user & admin).
 *
 * Cara pakai di setiap halaman:
 *   require_once __DIR__ . '/../../lib/layout.php';
 *   require_user();            // atau require_admin();
 *   app_header('Judul', 'menu_aktif', ['subtitle' => '...', 'actions' => '<html tepercaya>']);
 *   ... isi halaman ...
 *   app_footer();
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

/* ---------- Pesan flash (tampil sekali lalu hilang) ---------- */
function msg_add($type, $text) {
    $_SESSION['_msgs'][] = ['type' => $type, 'text' => $text];
}
function msg_pull() {
    $m = $_SESSION['_msgs'] ?? [];
    unset($_SESSION['_msgs']);
    return $m;
}

/* ---------- Helper umum ---------- */
function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Pastikan request POST dan CSRF valid. Kalau tidak, kembali ke $back dengan pesan error. */
function require_post_csrf($back) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? '')) {
        msg_add('error', 'Permintaan tidak valid. Silakan muat ulang halaman lalu coba lagi.');
        redirect($back);
    }
}

/** Ambil prompt milik user yang sedang login (null kalau bukan miliknya). */
function owned_prompt($id) {
    $st = getDB()->prepare("SELECT * FROM prompts WHERE id=? AND user_id=?");
    $st->execute([(int)$id, (int)$_SESSION['user']['id']]);
    return $st->fetch() ?: null;
}

/** Guard halaman user: harus login. Admin diarahkan ke dashboard admin (tanpa redirect loop). */
function require_user() {
    require_login();
    if (($_SESSION['user']['role'] ?? '') === 'admin') {
        redirect(BASE_URL . '/pages/admin/dashboard.php');
    }
}

function url_user($file)  { return BASE_URL . '/pages/user/' . $file; }
function url_admin($file) { return BASE_URL . '/pages/admin/' . $file; }

function fmt_date($ts, $withTime = true) {
    return date($withTime ? 'd M Y, H:i' : 'd M Y', strtotime($ts));
}

function attending_label($v) {
    return ['hadir' => 'Hadir', 'tidak_hadir' => 'Tidak hadir', 'belum_pasti' => 'Belum pasti'][$v] ?? (string)$v;
}
function attending_badge($v) {
    $cls = ['hadir' => 'b-ok', 'tidak_hadir' => 'b-no', 'belum_pasti' => 'b-warn'][$v] ?? 'b-muted';
    return '<span class="badge ' . $cls . '">' . e(attending_label($v)) . '</span>';
}

/** Hitung offset pagination. Return [page, perPage, offset]. */
function page_params($perPage = 8) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    return [$page, $perPage, ($page - 1) * $perPage];
}

/** Render link pagination sambil mempertahankan query string lain (cari, urutkan, dll). */
function pager($total, $page, $perPage) {
    $pages = (int)ceil($total / $perPage);
    if ($pages <= 1) return '';
    $q = $_GET;
    $link = function ($p, $label) use ($q) {
        $q['page'] = $p;
        return '<a href="?' . e(http_build_query($q)) . '">' . $label . '</a>';
    };
    $out  = '<nav class="pager" aria-label="Halaman">';
    $out .= $page > 1 ? $link($page - 1, '&larr; Sebelumnya') : '<span class="disabled">&larr; Sebelumnya</span>';
    $out .= '<span class="pager-info">Halaman ' . $page . ' dari ' . $pages . '</span>';
    $out .= $page < $pages ? $link($page + 1, 'Berikutnya &rarr;') : '<span class="disabled">Berikutnya &rarr;</span>';
    return $out . '</nav>';
}

/* ---------- Ikon SVG (gaya garis) ---------- */
function icon($name) {
    $paths = [
        'home'     => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'list'     => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><path d="M16 4.6a3.5 3.5 0 010 6.8M18 14.4c2 .7 3.5 2.6 3.5 5.6"/>',
        'check'    => '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.5"/>',
        'chat'     => '<path d="M21 12a8 8 0 01-11.7 7.1L4 20l1-4.6A8 8 0 1121 12z"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'doc'      => '<path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'shield'   => '<path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6z"/>',
        'download' => '<path d="M12 3v12m0 0l-4-4m4 4l4-4M4 21h16"/>',
        'copy'     => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 012-2h9"/>',
        'eye'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'edit'     => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="M13.5 6.5l4 4"/>',
        'key'      => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9M16 7l3 3"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
    ];
    return '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

/* ---------- Menu sidebar ----------
   Menu yang file halamannya belum ada otomatis disembunyikan, jadi aman
   di-merge bertahap (tidak ada link mati ke halaman yang belum dibuat). */
function app_menu($role) {
    if ($role === 'admin') {
        $dir   = __DIR__ . '/../pages/admin/';
        $items = [
            ['dashboard', 'Ringkasan',     'dashboard.php', 'home'],
            ['users',     'Pengguna',      'users.php',     'users'],
            ['prompts',   'Semua Prompt',  'prompts.php',   'doc'],
        ];
        $url = 'url_admin';
    } else {
        $dir   = __DIR__ . '/../pages/user/';
        $items = [
            ['dashboard', 'Ringkasan',     'dashboard.php', 'home'],
            ['undangan',  'Undangan Saya', 'undangan.php',  'list'],
            ['generator', 'Buat Undangan', 'generator.php', 'plus'],
            ['rsvp',      'RSVP Tamu',     'rsvp.php',      'check'],
            ['wishes',    'Ucapan Tamu',   'wishes.php',    'chat'],
            ['profile',   'Profil',        'profile.php',   'user'],
        ];
        $url = 'url_user';
    }
    $menu = [];
    foreach ($items as [$key, $label, $file, $ico]) {
        if (is_file($dir . $file)) {
            $menu[] = [$key, $label, $url($file), $ico];
        }
    }
    return $menu;
}

/* ---------- Header & footer halaman ---------- */
function app_header($title, $active = '', $opts = []) {
    $user    = current_user();
    $role    = $user['role'] ?? 'user';
    $initial = strtoupper(mb_substr($user['name'] ?? '?', 0, 1));
    $css     = BASE_URL . '/assets/app.css';
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> · Undangan Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e($css) ?>">
</head>
<body>
<div class="app">
    <header class="mobilebar">
        <button type="button" class="icon-btn" id="navToggle" aria-label="Buka menu"><?= icon('menu') ?></button>
        <span class="brand-name">Undangan Digital</span>
    </header>
    <div class="scrim" id="navScrim"></div>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= e(BASE_URL) ?>/index.php">
            <span class="brand-mark">U</span>
            <span class="brand-name">Undangan Digital</span>
        </a>
        <?php if ($role === 'admin'): ?><div class="role-tag"><?= icon('shield') ?> Admin</div><?php endif; ?>
        <nav class="nav" aria-label="Menu utama">
            <?php foreach (app_menu($role) as [$key, $label, $href, $ico]): ?>
                <a href="<?= e($href) ?>" class="<?= $key === $active ? 'active' : '' ?>"><?= icon($ico) ?><span><?= e($label) ?></span></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-foot">
            <div class="who">
                <span class="avatar"><?= e($initial) ?></span>
                <span class="who-text"><strong><?= e($user['name'] ?? '') ?></strong><small><?= e($user['email'] ?? '') ?></small></span>
            </div>
            <a class="logout" href="<?= e(BASE_URL) ?>/index.php?logout=1"><?= icon('logout') ?><span>Keluar</span></a>
        </div>
    </aside>
    <main class="main">
        <div class="page-head">
            <div>
                <h1><?= e($opts['heading'] ?? $title) ?></h1>
                <?php if (!empty($opts['subtitle'])): ?><p class="sub"><?= e($opts['subtitle']) ?></p><?php endif; ?>
            </div>
            <?php if (!empty($opts['actions'])): ?><div class="page-actions"><?= $opts['actions'] /* HTML tepercaya dari kode kita sendiri */ ?></div><?php endif; ?>
        </div>
        <?php foreach (msg_pull() as $m): ?>
            <div class="alert alert-<?= e($m['type']) ?>" role="status"><?= e($m['text']) ?></div>
        <?php endforeach; ?>
<?php
}

function app_footer() {
    ?>
    </main>
</div>
<script>
(function () {
    var sb = document.getElementById('sidebar'), sc = document.getElementById('navScrim'), bt = document.getElementById('navToggle');
    function toggle(open) {
        sb.classList.toggle('open', open);
        sc.classList.toggle('show', open);
    }
    if (bt) bt.addEventListener('click', function () { toggle(!sb.classList.contains('open')); });
    if (sc) sc.addEventListener('click', function () { toggle(false); });
    // Tombol salin generik: <button data-copy="teks" data-label="Salin">
    document.addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-copy]');
        if (!b) return;
        var txt = b.dataset.copy, label = b.dataset.label || b.textContent;
        var done = function () { b.textContent = 'Tersalin!'; setTimeout(function () { b.textContent = label; }, 1500); };
        if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(txt).then(done);
        else { var t = document.createElement('textarea'); t.value = txt; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done(); }
    });
    // Konfirmasi aksi berbahaya: <form data-confirm="Yakin?">
    document.addEventListener('submit', function (ev) {
        var f = ev.target, m = f.getAttribute('data-confirm');
        if (m && !window.confirm(m)) ev.preventDefault();
    });
})();
</script>
</body>
</html>
<?php
}