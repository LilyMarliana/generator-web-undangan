<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/rsvp.php';
require_admin();

$db = getDB();

/* Kolom is_hidden berasal dari migration_dashboard.sql */
if (!$db->query("SHOW COLUMNS FROM wishes LIKE 'is_hidden'")->fetch()) {
    app_header('Moderasi Ucapan', 'wishes', ['subtitle' => 'Pantau ucapan dari semua undangan.']);
    echo '<div class="alert alert-error">Kolom <code>is_hidden</code> belum ada di database. Jalankan <code>migration_dashboard.sql</code> lalu muat ulang halaman ini.</div>';
    app_footer();
    exit;
}

$q   = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$vis = (is_string($_GET['vis'] ?? null) && in_array($_GET['vis'], ['tampil', 'sembunyi'], true)) ? $_GET['vis'] : '';

$where  = '1=1';
$params = [];
if ($q !== '') {
    $like   = '%' . addcslashes($q, '%_\\') . '%';
    $where .= " AND (w.name LIKE ? OR w.message LIKE ? OR p.title LIKE ? OR u.name LIKE ?)";
    array_push($params, $like, $like, $like, $like);
}
if ($vis === 'tampil')   { $where .= " AND w.is_hidden = 0"; }
if ($vis === 'sembunyi') { $where .= " AND w.is_hidden = 1"; }

$base = "FROM wishes w JOIN prompts p ON p.id = w.prompt_id JOIN users u ON u.id = p.user_id WHERE $where";

$st = $db->prepare("SELECT COUNT(*) $base");
$st->execute($params);
$total = (int)$st->fetchColumn();

[$page, $per] = page_params(10);
$page   = min($page, max(1, (int)ceil($total / $per)));
$offset = ($page - 1) * $per;

$st = $db->prepare(
    "SELECT w.id, w.name, w.message, w.is_hidden, w.created_at, p.title, u.name AS owner
     $base ORDER BY w.id DESC LIMIT $per OFFSET $offset"
);
$st->execute($params);
$rows = $st->fetchAll();

$hiddenTotal = (int)$db->query("SELECT COUNT(*) FROM wishes WHERE is_hidden = 1")->fetchColumn();
$allTotal    = (int)$db->query("SELECT COUNT(*) FROM wishes")->fetchColumn();

$backQuery = array_filter(['q' => $q, 'vis' => $vis, 'page' => $page > 1 ? $page : ''], fn($v) => $v !== '');
$back      = 'wishes.php' . ($backQuery ? '?' . http_build_query($backQuery) : '');

app_header('Moderasi Ucapan', 'wishes', [
    'subtitle' => 'Ucapan dari seluruh undangan. Sembunyikan atau hapus yang spam atau tidak pantas (' . $allTotal . ' total, ' . $hiddenTotal . ' disembunyikan).',
]);
?>
<form class="toolbar" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama tamu, isi ucapan, undangan, atau pemilik" maxlength="100">
    <select name="vis" aria-label="Filter tampil">
        <option value="">Semua ucapan</option>
        <option value="tampil"<?= $vis === 'tampil' ? ' selected' : '' ?>>Sedang tampil</option>
        <option value="sembunyi"<?= $vis === 'sembunyi' ? ' selected' : '' ?>>Disembunyikan</option>
    </select>
    <button class="btn-outline" type="submit">Terapkan</button>
    <?php if ($q !== '' || $vis !== ''): ?><a class="btn-outline" href="wishes.php">Reset</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
    <div class="card empty"><p><?= ($q !== '' || $vis !== '') ? 'Tidak ada ucapan yang cocok.' : 'Belum ada ucapan dari tamu.' ?></p></div>
<?php else: ?>
    <div class="card flush">
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr><th>Tamu</th><th>Ucapan</th><th>Undangan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $w): $hidden = (int)$w['is_hidden'] === 1; ?>
                    <tr>
                        <td class="cell-title"><?= e($w['name']) ?><br><small class="muted"><?= e(fmt_date($w['created_at'])) ?></small></td>
                        <td class="msg"><?= e($w['message']) ?></td>
                        <td class="cell-sub"><?= e($w['title']) ?><br><small class="muted"><?= e($w['owner']) ?></small></td>
                        <td><span class="badge <?= $hidden ? 'b-muted' : 'b-ok' ?>"><?= $hidden ? 'Disembunyikan' : 'Tampil' ?></span></td>
                        <td>
                            <div class="row-actions">
                                <form method="post" action="wish_action.php" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
                                    <input type="hidden" name="do" value="<?= $hidden ? 'show' : 'hide' ?>">
                                    <input type="hidden" name="back" value="<?= e($back) ?>">
                                    <button class="btn btn-sm" type="submit"><?= $hidden ? 'Tampilkan' : 'Sembunyikan' ?></button>
                                </form>
                                <form method="post" action="wish_action.php" class="inline" data-confirm="Hapus ucapan ini secara permanen?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
                                    <input type="hidden" name="do" value="delete">
                                    <input type="hidden" name="back" value="<?= e($back) ?>">
                                    <button class="btn-danger btn-sm" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?= pager($total, $page, $per) ?>
<?php endif; ?>
<?php app_footer();