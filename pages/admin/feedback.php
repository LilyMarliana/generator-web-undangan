<?php
require_once __DIR__ . '/../../lib/layout.php';
require_admin();

$db = getDB();

if (!$db->query("SHOW TABLES LIKE 'feedback'")->fetch()) {
    app_header('Masukan Penguji', 'feedback', ['subtitle' => 'Kumpulan masukan dari pengguna.']);
    echo '<div class="alert alert-error">Tabel <code>feedback</code> belum ada. Jalankan <code>migration_feedback.sql</code> di database lalu muat ulang halaman ini.</div>';
    app_footer();
    exit;
}

$cats   = ['saran' => 'Saran fitur', 'bug' => 'Ada yang error', 'tampilan' => 'Tampilan / desain', 'lainnya' => 'Lainnya'];
$stats  = ['baru' => 'Baru', 'dibaca' => 'Sudah dibaca', 'ditindaklanjuti' => 'Ditindaklanjuti'];

/* Ekspor CSV seluruh masukan (untuk bahan analisis di laporan) */
if (($_GET['export'] ?? '') === '1') {
    $csv = function ($v) { $v = (string)$v; return ($v !== '' && preg_match('/^[=+\-@\t\r]/', $v)) ? "'" . $v : $v; };
    $rows = $db->query(
        "SELECT f.id, f.created_at, u.name, u.email, f.rating, f.category, f.message, f.status, f.admin_note
         FROM feedback f JOIN users u ON u.id = f.user_id ORDER BY f.id ASC"
    );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="masukan-invitia-' . date('Ymd') . '.csv"');
    header('X-Content-Type-Options: nosniff');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Waktu', 'Nama', 'Email', 'Rating', 'Kategori', 'Masukan', 'Status', 'Catatan admin'], ',', '"', '');
    while ($r = $rows->fetch()) {
        fputcsv($out, [$r['id'], $r['created_at'], $csv($r['name']), $csv($r['email']), $r['rating'], $cats[$r['category']] ?? $r['category'], $csv($r['message']), $stats[$r['status']] ?? $r['status'], $csv($r['admin_note'])], ',', '"', '');
    }
    exit;
}

$status = (is_string($_GET['status'] ?? null) && isset($stats[$_GET['status']])) ? $_GET['status'] : '';
$cat    = (is_string($_GET['cat'] ?? null) && isset($cats[$_GET['cat']])) ? $_GET['cat'] : '';

$where = '1=1'; $params = [];
if ($status !== '') { $where .= " AND f.status = ?"; $params[] = $status; }
if ($cat !== '')    { $where .= " AND f.category = ?"; $params[] = $cat; }

$st = $db->prepare("SELECT COUNT(*) FROM feedback f WHERE $where");
$st->execute($params);
$total = (int)$st->fetchColumn();

[$page, $per] = page_params(8);
$page   = min($page, max(1, (int)ceil($total / $per)));
$offset = ($page - 1) * $per;

$st = $db->prepare(
    "SELECT f.id, f.rating, f.category, f.message, f.status, f.admin_note, f.created_at, u.name, u.email
     FROM feedback f JOIN users u ON u.id = f.user_id
     WHERE $where ORDER BY f.id DESC LIMIT $per OFFSET $offset"
);
$st->execute($params);
$list = $st->fetchAll();

/* Ringkasan (seluruh data, bukan hanya hasil filter) */
$all    = (int)$db->query("SELECT COUNT(*) FROM feedback")->fetchColumn();
$avg    = $all ? round((float)$db->query("SELECT AVG(rating) FROM feedback")->fetchColumn(), 1) : 0;
$byStat = array_column($db->query("SELECT status, COUNT(*) c FROM feedback GROUP BY status")->fetchAll(), 'c', 'status');
$byRate = array_column($db->query("SELECT rating, COUNT(*) c FROM feedback GROUP BY rating")->fetchAll(), 'c', 'rating');
$byCat  = array_column($db->query("SELECT category, COUNT(*) c FROM feedback GROUP BY category")->fetchAll(), 'c', 'category');
$testers = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM feedback")->fetchColumn();

$backQ = array_filter(['status' => $status, 'cat' => $cat, 'page' => $page > 1 ? $page : ''], fn($v) => $v !== '');
$back  = 'feedback.php' . ($backQ ? '?' . http_build_query($backQ) : '');

app_header('Masukan Penguji', 'feedback', [
    'subtitle' => 'Masukan dari pengguna dan penguji awal. Tandai statusnya dan tulis tindak lanjut untuk bahan laporan.',
    'actions'  => '<a class="btn" href="feedback.php?export=1">' . icon('download') . ' Ekspor CSV</a>',
]);
?>
<div class="stats">
    <div class="stat"><span class="stat-ico"><?= icon('star') ?></span><div><div class="stat-val"><?= $all ?></div><div class="stat-label">Total masukan</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('users') ?></span><div><div class="stat-val"><?= $testers ?></div><div class="stat-label">Pengguna yang memberi masukan</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('chart') ?></span><div><div class="stat-val"><?= $all ? e((string)$avg) : '-' ?></div><div class="stat-label">Rata-rata penilaian (dari 5)</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('check') ?></span><div><div class="stat-val"><?= (int)($byStat['ditindaklanjuti'] ?? 0) ?></div><div class="stat-label">Sudah ditindaklanjuti</div></div></div>
</div>

<?php if ($all): ?>
<div class="grid-2">
    <div class="card">
        <h2 class="card-title">Sebaran penilaian</h2>
        <?php $mx = max(1, max(array_map('intval', $byRate ?: [1]))); for ($i = 5; $i >= 1; $i--): $n = (int)($byRate[$i] ?? 0); ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span class="stars" style="display:inline-block;width:6.2em"><?= str_repeat('★', $i) ?></span>
                <div class="meter" style="flex:1"><b style="width:<?= round($n / $mx * 100) ?>%"></b></div>
                <span style="min-width:2em;text-align:right"><?= $n ?></span>
            </div>
        <?php endfor; ?>
    </div>
    <div class="card">
        <h2 class="card-title">Jenis masukan</h2>
        <?php $mc = max(1, max(array_map('intval', $byCat ?: [1]))); foreach ($cats as $k => $lab): $n = (int)($byCat[$k] ?? 0); ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="min-width:9.5em;font-size:.9rem"><?= e($lab) ?></span>
                <div class="meter" style="flex:1"><b style="width:<?= round($n / $mc * 100) ?>%"></b></div>
                <span style="min-width:2em;text-align:right"><?= $n ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<form class="toolbar" method="get" style="margin-top:20px">
    <select name="status" aria-label="Filter status">
        <option value="">Semua status</option>
        <?php foreach ($stats as $k => $lab): ?><option value="<?= e($k) ?>"<?= $status === $k ? ' selected' : '' ?>><?= e($lab) ?></option><?php endforeach; ?>
    </select>
    <select name="cat" aria-label="Filter jenis">
        <option value="">Semua jenis</option>
        <?php foreach ($cats as $k => $lab): ?><option value="<?= e($k) ?>"<?= $cat === $k ? ' selected' : '' ?>><?= e($lab) ?></option><?php endforeach; ?>
    </select>
    <button class="btn-outline" type="submit">Terapkan</button>
    <?php if ($status !== '' || $cat !== ''): ?><a class="btn-outline" href="feedback.php">Reset</a><?php endif; ?>
</form>

<?php if (!$list): ?>
    <div class="card empty"><p><?= $all ? 'Tidak ada masukan yang cocok dengan filter.' : 'Belum ada masukan. Minta penguji membuka menu "Beri Masukan" di dashboard mereka.' ?></p></div>
<?php else: ?>
    <?php foreach ($list as $f): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
                <div>
                    <span class="stars" aria-label="Penilaian <?= (int)$f['rating'] ?> dari 5"><?= str_repeat('★', (int)$f['rating']) ?><span class="off"><?= str_repeat('★', 5 - (int)$f['rating']) ?></span></span>
                    <span class="badge b-accent" style="margin-left:6px"><?= e($cats[$f['category']] ?? $f['category']) ?></span>
                    <div style="margin-top:4px"><strong><?= e($f['name']) ?></strong> <small class="muted"><?= e($f['email']) ?> · <?= e(fmt_date($f['created_at'])) ?></small></div>
                </div>
                <span class="badge <?= $f['status'] === 'ditindaklanjuti' ? 'b-ok' : ($f['status'] === 'dibaca' ? 'b-warn' : 'b-muted') ?>"><?= e($stats[$f['status']] ?? $f['status']) ?></span>
            </div>
            <div class="quote" style="margin:12px 0"><?= e($f['message']) ?></div>
            <form method="post" action="feedback_action.php" class="stack-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                <input type="hidden" name="back" value="<?= e($back) ?>">
                <select name="status" aria-label="Status">
                    <?php foreach ($stats as $k => $lab): ?><option value="<?= e($k) ?>"<?= $f['status'] === $k ? ' selected' : '' ?>><?= e($lab) ?></option><?php endforeach; ?>
                </select>
                <input type="text" name="admin_note" value="<?= e((string)$f['admin_note']) ?>" maxlength="255" placeholder="Tindak lanjut (mis. tombol upload diperjelas)">
                <button class="btn btn-sm" type="submit">Simpan</button>
            </form>
        </div>
    <?php endforeach; ?>
    <?= pager($total, $page, $per) ?>
<?php endif; ?>
<?php app_footer();