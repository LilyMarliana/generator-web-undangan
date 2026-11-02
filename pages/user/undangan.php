<?php
require_once __DIR__ . '/../../lib/layout.php';
require_user();

$db  = getDB();
$uid = (int)$_SESSION['user']['id'];

/* Pencarian & urutan (nilai dari URL divalidasi ketat) */
$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$sorts = [
    'terbaru' => ['Terbaru',        'p.id DESC'],
    'terlama' => ['Terlama',        'p.id ASC'],
    'nama'    => ['Judul A-Z',      'p.title ASC'],
    'rsvp'    => ['RSVP terbanyak', 'rsvp_count DESC, p.id DESC'],
];
$sortKey = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : '';
$sort    = isset($sorts[$sortKey]) ? $sortKey : 'terbaru';

$where  = "p.user_id = ?";
$params = [$uid];
if ($q !== '') {
    // tanda % dan _ dari pengguna dianggap huruf biasa, bukan karakter pencarian khusus
    $like  = '%' . addcslashes($q, '%_\\') . '%';
    $where .= " AND (p.title LIKE ? OR p.groom_name LIKE ? OR p.bride_name LIKE ?)";
    array_push($params, $like, $like, $like);
}

$st = $db->prepare("SELECT COUNT(*) FROM prompts p WHERE $where");
$st->execute($params);
$total = (int)$st->fetchColumn();

[$page, $per] = page_params(8);
$pages  = max(1, (int)ceil($total / $per));
$page   = min($page, $pages);
$offset = ($page - 1) * $per;

$st = $db->prepare(
    "SELECT p.id, p.title, p.groom_name, p.bride_name, p.created_at,
            (SELECT COUNT(*) FROM rsvps  WHERE prompt_id=p.id) AS rsvp_count,
            (SELECT COUNT(*) FROM wishes WHERE prompt_id=p.id) AS wish_count
     FROM prompts p WHERE $where ORDER BY {$sorts[$sort][1]} LIMIT $per OFFSET $offset"
);
$st->execute($params);
$rows = $st->fetchAll();

app_header('Undangan Saya', 'undangan', [
    'subtitle' => 'Semua prompt undangan yang sudah kamu buat.',
    'actions'  => '<a class="btn-primary" href="generator.php">' . icon('plus') . ' Buat Undangan</a>',
]);
?>
<form class="toolbar" method="get">
    <div class="search">
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari judul atau nama pengantin..." aria-label="Cari">
    </div>
    <select name="sort" aria-label="Urutkan" onchange="this.form.submit()">
        <?php foreach ($sorts as $k => [$label]): ?>
            <option value="<?= e($k) ?>" <?= $k === $sort ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn-outline" type="submit">Cari</button>
    <?php if ($q !== ''): ?><a class="btn-outline" href="undangan.php">Reset</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
    <div class="card empty">
        <?php if ($q !== ''): ?>
            <h3>Tidak ditemukan</h3>
            <p>Tidak ada undangan yang cocok dengan &ldquo;<?= e($q) ?>&rdquo;.</p>
        <?php else: ?>
            <h3>Belum ada undangan</h3>
            <p>Buat prompt undangan pertama kamu dalam 5 langkah singkat.</p>
            <a class="btn-primary" href="generator.php">Buat Undangan</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="small muted" style="margin-bottom:10px;"><?= $total ?> undangan</p>
    <div class="inv-list">
        <?php foreach ($rows as $r):
            $pair = implode(' & ', array_filter([trim((string)$r['groom_name']), trim((string)$r['bride_name'])]));
        ?>
            <article class="inv">
                <div class="inv-main">
                    <a class="inv-title" href="detail.php?id=<?= (int)$r['id'] ?>"><?= e($r['title']) ?></a>
                    <div class="inv-sub"><?= $pair !== '' ? e($pair) . ' &middot; ' : '' ?>Dibuat <?= e(fmt_date($r['created_at'], false)) ?></div>
                    <div class="inv-meta">
                        <span class="badge b-accent"><?= (int)$r['rsvp_count'] ?> RSVP</span>
                        <span class="badge b-muted"><?= (int)$r['wish_count'] ?> ucapan</span>
                    </div>
                </div>
                <div class="inv-actions">
                    <a class="btn btn-sm" href="detail.php?id=<?= (int)$r['id'] ?>"><?= icon('eye') ?> Detail</a>
                    <a class="btn btn-sm" href="edit.php?id=<?= (int)$r['id'] ?>"><?= icon('edit') ?> Edit</a>
                    <form method="post" action="delete.php" class="inline" data-confirm="Hapus undangan ini beserta seluruh RSVP, ucapan, dan file uploadnya? Tindakan ini tidak bisa dibatalkan.">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn-danger btn-sm" type="submit"><?= icon('trash') ?> Hapus</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?= pager($total, $page, $per) ?>
<?php endif; ?>
<?php app_footer();