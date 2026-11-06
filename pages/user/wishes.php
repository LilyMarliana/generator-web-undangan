<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/rsvp.php';
require_user();

$db  = getDB();
$uid = (int)$_SESSION['user']['id'];

/* Kolom is_hidden berasal dari migration_dashboard.sql. Kalau belum dijalankan, beri petunjuk jelas. */
$hasCol = (bool)$db->query("SHOW COLUMNS FROM wishes LIKE 'is_hidden'")->fetch();
if (!$hasCol) {
    app_header('Ucapan Tamu', 'wishes', ['subtitle' => 'Moderasi ucapan dari tamu undangan.']);
    ?>
    <div class="alert alert-error">Kolom <code>is_hidden</code> belum ada di database. Jalankan sekali file <code>migration_dashboard.sql</code> di database kamu, lalu muat ulang halaman ini.</div>
    <?php
    app_footer();
    exit;
}

$st = $db->prepare("SELECT id, title FROM prompts WHERE user_id=? ORDER BY id DESC");
$st->execute([$uid]);
$myPrompts = $st->fetchAll();
$myIds     = array_map('intval', array_column($myPrompts, 'id'));

/* Filter (divalidasi ketat) */
$pid = 0;
if (is_string($_GET['prompt_id'] ?? null) && ctype_digit($_GET['prompt_id'])) {
    $pid = (int)$_GET['prompt_id'];
}
if (!in_array($pid, $myIds, true)) {
    $pid = 0;
}
$visOptions = ['' => 'Semua ucapan', 'tampil' => 'Sedang tampil', 'sembunyi' => 'Disembunyikan'];
$vis = is_string($_GET['vis'] ?? null) && isset($visOptions[$_GET['vis']]) ? $_GET['vis'] : '';
$q   = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';

$where  = "p.user_id = ?";
$params = [$uid];
if ($pid)           { $where .= " AND w.prompt_id = ?"; $params[] = $pid; }
if ($vis === 'tampil')   { $where .= " AND w.is_hidden = 0"; }
if ($vis === 'sembunyi') { $where .= " AND w.is_hidden = 1"; }
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where .= " AND (w.name LIKE ? OR w.message LIKE ?)";
    array_push($params, $like, $like);
}

/* Ringkasan (mengikuti pilihan undangan) */
$st = $db->prepare(
    "SELECT COUNT(*) total, COALESCE(SUM(w.is_hidden=0),0) tampil, COALESCE(SUM(w.is_hidden=1),0) sembunyi
     FROM wishes w JOIN prompts p ON p.id=w.prompt_id WHERE p.user_id=?" . ($pid ? " AND w.prompt_id=?" : "")
);
$st->execute($pid ? [$uid, $pid] : [$uid]);
$sum = $st->fetch();

$st = $db->prepare("SELECT COUNT(*) FROM wishes w JOIN prompts p ON p.id=w.prompt_id WHERE $where");
$st->execute($params);
$total = (int)$st->fetchColumn();

[$page, $per] = page_params(10);
$pages  = max(1, (int)ceil($total / $per));
$page   = min($page, $pages);
$offset = ($page - 1) * $per;

$st = $db->prepare(
    "SELECT w.id, w.name, w.message, w.is_hidden, w.created_at, p.title
     FROM wishes w JOIN prompts p ON p.id=w.prompt_id
     WHERE $where ORDER BY w.id DESC LIMIT $per OFFSET $offset"
);
$st->execute($params);
$rows = $st->fetchAll();

$back = 'wishes.php' . ($_GET ? '?' . http_build_query($_GET) : '');
$filtered = ($pid || $vis !== '' || $q !== '');

app_header('Ucapan Tamu', 'wishes', ['subtitle' => 'Moderasi ucapan dari tamu: sembunyikan yang tidak pantas, atau hapus.']);
?>
<?php if (!$myPrompts): ?>
    <div class="card empty">
        <h3>Belum ada undangan</h3>
        <p>Ucapan masuk dari website undangan yang kamu buat. Buat undangan dulu.</p>
        <a class="btn-primary" href="generator.php">Buat Undangan</a>
    </div>
<?php else: ?>

<section class="stats">
    <div class="stat"><span class="stat-ico"><?= icon('chat') ?></span><div><div class="stat-val"><?= (int)$sum['total'] ?></div><div class="stat-label">Total ucapan</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('eye') ?></span><div><div class="stat-val"><?= (int)$sum['tampil'] ?></div><div class="stat-label">Sedang tampil</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('shield') ?></span><div><div class="stat-val"><?= (int)$sum['sembunyi'] ?></div><div class="stat-label">Disembunyikan</div></div></div>
</section>

<form class="toolbar" method="get">
    <select name="prompt_id" aria-label="Undangan" onchange="this.form.submit()">
        <option value="0">Semua undangan</option>
        <?php foreach ($myPrompts as $mp): ?>
            <option value="<?= (int)$mp['id'] ?>" <?= (int)$mp['id'] === $pid ? 'selected' : '' ?>><?= e($mp['title']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="vis" aria-label="Tampilan" onchange="this.form.submit()">
        <?php foreach ($visOptions as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $k === $vis ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="search">
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau isi ucapan..." aria-label="Cari">
    </div>
    <button class="btn-outline" type="submit">Cari</button>
    <?php if ($filtered): ?><a class="btn-outline" href="wishes.php">Reset</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
    <div class="card empty">
        <?php if ($filtered): ?>
            <h3>Tidak ditemukan</h3><p>Tidak ada ucapan yang cocok dengan filter ini.</p>
        <?php else: ?>
            <h3>Belum ada ucapan</h3><p>Ucapan dari tamu akan muncul di sini begitu mereka mengirimnya dari website undangan.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="small muted" style="margin-bottom:10px;"><?= $total ?> ucapan</p>
    <div class="inv-list">
        <?php foreach ($rows as $w): $hidden = (int)$w['is_hidden'] === 1; ?>
            <article class="inv wish<?= $hidden ? ' wish-hidden' : '' ?>">
                <div class="inv-main">
                    <div class="wish-head">
                        <strong><?= e($w['name']) ?></strong>
                        <?php if ($hidden): ?><span class="badge b-warn">Disembunyikan</span><?php endif; ?>
                    </div>
                    <div class="inv-sub"><?= e($w['title']) ?> &middot; <?= e(fmt_date($w['created_at'])) ?></div>
                    <p class="wish-msg"><?= e($w['message']) ?></p>
                </div>
                <div class="inv-actions">
                    <form method="post" action="wish_action.php" class="inline">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$w['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
                        <input type="hidden" name="do" value="<?= $hidden ? 'show' : 'hide' ?>">
                        <button class="btn btn-sm" type="submit"><?= icon('eye') ?> <?= $hidden ? 'Tampilkan' : 'Sembunyikan' ?></button>
                    </form>
                    <form method="post" action="wish_action.php" class="inline" data-confirm="Hapus ucapan ini? Tindakan ini tidak bisa dibatalkan.">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$w['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
                        <input type="hidden" name="do" value="delete">
                        <button class="btn-danger btn-sm" type="submit"><?= icon('trash') ?> Hapus</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?= pager($total, $page, $per) ?>
<?php endif; ?>
<?php endif; ?>
<?php app_footer();