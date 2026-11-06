<?php
require_once __DIR__ . '/../../lib/layout.php';
require_admin();

$db = getDB();

$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$where  = '1=1';
$params = [];
if ($q !== '') {
    $like   = '%' . addcslashes($q, '%_\\') . '%';
    $where .= " AND (p.title LIKE ? OR p.groom_name LIKE ? OR p.bride_name LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    array_push($params, $like, $like, $like, $like, $like);
}

$st = $db->prepare("SELECT COUNT(*) FROM prompts p JOIN users u ON u.id = p.user_id WHERE $where");
$st->execute($params);
$total = (int)$st->fetchColumn();

[$page, $per] = page_params(10);
$page   = min($page, max(1, (int)ceil($total / $per)));
$offset = ($page - 1) * $per;

$st = $db->prepare(
    "SELECT p.id, p.title, p.groom_name, p.bride_name, p.created_at, p.updated_at,
            u.name AS owner, u.email AS owner_email,
            (SELECT COUNT(*) FROM rsvps  WHERE prompt_id = p.id) AS rsvp_count,
            (SELECT COUNT(*) FROM wishes WHERE prompt_id = p.id) AS wish_count
     FROM prompts p JOIN users u ON u.id = p.user_id
     WHERE $where ORDER BY p.id DESC LIMIT $per OFFSET $offset"
);
$st->execute($params);
$prompts = $st->fetchAll();

app_header('Semua Prompt', 'prompts', [
    'heading'  => 'Semua Undangan',
    'subtitle' => 'Seluruh undangan yang dibuat pengguna (' . $total . ' total).',
]);
?>
<form class="toolbar" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari judul, nama mempelai, atau pemilik" maxlength="100">
    <button class="btn-outline" type="submit">Cari</button>
    <?php if ($q !== ''): ?><a class="btn-outline" href="prompts.php">Reset</a><?php endif; ?>
</form>

<?php if (!$prompts): ?>
    <div class="card empty"><p><?= $q !== '' ? 'Tidak ada undangan yang cocok dengan pencarian.' : 'Belum ada undangan yang dibuat.' ?></p></div>
<?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                    <tr><th>Judul</th><th>Pemilik</th><th class="num">RSVP</th><th class="num">Ucapan</th><th>Dibuat</th><th>Diperbarui</th></tr>
                </thead>
                <tbody>
                <?php foreach ($prompts as $p):
                    $couple = trim(($p['groom_name'] ?? '') . ' & ' . ($p['bride_name'] ?? ''), ' &');
                ?>
                    <tr>
                        <td class="cell-title"><?= e($p['title']) ?><?php if ($couple !== ''): ?><br><small class="muted"><?= e($couple) ?></small><?php endif; ?></td>
                        <td class="cell-sub"><?= e($p['owner']) ?><br><small class="muted"><?= e($p['owner_email']) ?></small></td>
                        <td class="num"><?= (int)$p['rsvp_count'] ?></td>
                        <td class="num"><?= (int)$p['wish_count'] ?></td>
                        <td class="cell-sub nowrap"><?= e(fmt_date($p['created_at'])) ?></td>
                        <td class="cell-sub nowrap"><?= e(fmt_date($p['updated_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?= pager($total, $page, $per) ?>
<?php endif; ?>
<?php app_footer();