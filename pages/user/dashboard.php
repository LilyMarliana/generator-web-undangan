<?php
require_once __DIR__ . '/../../lib/layout.php';
require_user();

$db  = getDB();
$uid = (int)$_SESSION['user']['id'];

/* Pesan flash gaya lama (dipakai edit.php/delete.php versi lama) dipindah ke sistem pesan baru */
foreach (['save_success' => 'success', 'delete_success' => 'success', 'error' => 'error'] as $k => $type) {
    if ($m = flash($k)) {
        msg_add($type, $m);
        unset($_SESSION['flash'][$k]);
    }
}

/* Tanggal "hari ini" menurut database, supaya grafik 7 hari selaras dengan created_at */
$today = $db->query("SELECT CURDATE()")->fetchColumn();

/* Statistik utama */
$st = $db->prepare("SELECT COUNT(*) FROM prompts WHERE user_id=?");
$st->execute([$uid]);
$totalUndangan = (int)$st->fetchColumn();

$st = $db->prepare(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(r.attending='hadir'),0)       AS hadir,
            COALESCE(SUM(r.attending='tidak_hadir'),0) AS tidak,
            COALESCE(SUM(r.attending='belum_pasti'),0) AS belum,
            COALESCE(SUM(CASE WHEN r.attending='hadir' THEN r.guests ELSE 0 END),0) AS tamu_hadir
     FROM rsvps r JOIN prompts p ON p.id=r.prompt_id WHERE p.user_id=?"
);
$st->execute([$uid]);
$rs = $st->fetch();

$st = $db->prepare("SELECT COUNT(*) FROM wishes w JOIN prompts p ON p.id=w.prompt_id WHERE p.user_id=?");
$st->execute([$uid]);
$totalWishes = (int)$st->fetchColumn();

/* Kuota harian */
$st = $db->prepare("SELECT count FROM quota_logs WHERE user_id=? AND date=?");
$st->execute([$uid, date('Y-m-d')]);
$used      = (int)($st->fetchColumn() ?: 0);
$quota     = max(1, daily_quota());
$remaining = max(0, $quota - $used);

/* RSVP 7 hari terakhir */
$st = $db->prepare(
    "SELECT DATE(r.created_at) d, COUNT(*) c
     FROM rsvps r JOIN prompts p ON p.id=r.prompt_id
     WHERE p.user_id=? AND r.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(r.created_at)"
);
$st->execute([$uid]);
$perDay = array_column($st->fetchAll(), 'c', 'd');
$days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime($today . " -$i day"));
    $days[] = ['label' => date('d/m', strtotime($d)), 'count' => (int)($perDay[$d] ?? 0)];
}
$maxDay = max(1, max(array_column($days, 'count')));

/* Undangan & RSVP terbaru */
$st = $db->prepare(
    "SELECT p.id, p.title, p.created_at,
            (SELECT COUNT(*) FROM rsvps WHERE prompt_id=p.id) AS rsvp_count
     FROM prompts p WHERE p.user_id=? ORDER BY p.id DESC LIMIT 5"
);
$st->execute([$uid]);
$recent = $st->fetchAll();

$st = $db->prepare(
    "SELECT r.name, r.attending, r.guests, r.created_at, p.title
     FROM rsvps r JOIN prompts p ON p.id=r.prompt_id WHERE p.user_id=? ORDER BY r.id DESC LIMIT 5"
);
$st->execute([$uid]);
$recentRsvp = $st->fetchAll();

$respTotal = max(1, (int)$rs['total']);
$firstName = explode(' ', trim($_SESSION['user']['name']))[0];

app_header('Ringkasan', 'dashboard', [
    'heading'  => 'Halo, ' . $firstName,
    'subtitle' => 'Ringkasan undangan, RSVP, dan ucapan dari tamu kamu.',
    'actions'  => '<a class="btn-primary" href="generator.php">' . icon('plus') . ' Buat Undangan</a>',
]);
?>
<section class="stats">
    <div class="stat"><span class="stat-ico"><?= icon('list') ?></span><div><div class="stat-val"><?= $totalUndangan ?></div><div class="stat-label">Undangan dibuat</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('check') ?></span><div><div class="stat-val"><?= (int)$rs['total'] ?></div><div class="stat-label">RSVP masuk</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('users') ?></span><div><div class="stat-val"><?= (int)$rs['tamu_hadir'] ?></div><div class="stat-label">Tamu akan hadir</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('chat') ?></span><div><div class="stat-val"><?= $totalWishes ?></div><div class="stat-label">Ucapan tamu</div></div></div>
</section>

<div class="grid-2 mb">
    <section class="card">
        <h2 class="card-title">Kehadiran tamu</h2>
        <?php if ((int)$rs['total'] === 0): ?>
            <p class="muted">Belum ada RSVP. Begitu tamu mengisi RSVP di website undangan, ringkasannya muncul di sini.</p>
        <?php else: ?>
            <div class="stack" role="img" aria-label="Komposisi kehadiran">
                <span class="seg-ok"   style="width:<?= round($rs['hadir'] / $respTotal * 100, 1) ?>%"></span>
                <span class="seg-warn" style="width:<?= round($rs['belum'] / $respTotal * 100, 1) ?>%"></span>
                <span class="seg-no"   style="width:<?= round($rs['tidak'] / $respTotal * 100, 1) ?>%"></span>
            </div>
            <div class="legend">
                <span><i class="seg-ok"></i>Hadir <?= (int)$rs['hadir'] ?></span>
                <span><i class="seg-warn"></i>Belum pasti <?= (int)$rs['belum'] ?></span>
                <span><i class="seg-no"></i>Tidak hadir <?= (int)$rs['tidak'] ?></span>
            </div>
        <?php endif; ?>
        <h3 class="card-sub">RSVP 7 hari terakhir</h3>
        <div class="bars">
            <?php foreach ($days as $d): ?>
                <div class="bar" title="<?= e($d['label']) ?>: <?= $d['count'] ?> RSVP">
                    <em><?= $d['count'] ?: '' ?></em>
                    <b style="height:<?= max(3, round($d['count'] / $maxDay * 70)) ?>px"></b>
                    <small><?= e($d['label']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card">
        <h2 class="card-title">Kuota pembuatan hari ini</h2>
        <p><strong><?= $remaining ?></strong> dari <?= $quota ?> kuota tersisa</p>
        <div class="meter" role="progressbar" aria-valuenow="<?= $used ?>" aria-valuemin="0" aria-valuemax="<?= $quota ?>"><span style="width:<?= min(100, round($used / $quota * 100)) ?>%"></span></div>
        <p class="small muted">Kuota direset otomatis setiap hari.</p>

        <h3 class="card-title" style="margin-top:22px;">Undangan terbaru
            <?php if (has_page('undangan.php')): ?><a href="undangan.php">Lihat semua</a><?php endif; ?>
        </h3>
        <?php if (!$recent): ?>
            <p class="muted">Belum ada undangan. <a href="generator.php">Buat yang pertama</a>.</p>
        <?php else: ?>
            <?php foreach ($recent as $r): ?>
                <div class="list-row">
                    <?php if (has_page('detail.php')): ?>
                        <a class="list-main" href="detail.php?id=<?= (int)$r['id'] ?>"><?= e($r['title']) ?><small><?= e(fmt_date($r['created_at'])) ?></small></a>
                    <?php else: ?>
                        <span class="list-main"><?= e($r['title']) ?><small><?= e(fmt_date($r['created_at'])) ?></small></span>
                    <?php endif; ?>
                    <span class="badge b-accent nowrap"><?= (int)$r['rsvp_count'] ?> RSVP</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<section class="card">
    <h2 class="card-title">RSVP terbaru
        <?php if (has_page('rsvp.php')): ?><a href="rsvp.php">Kelola RSVP</a><?php endif; ?>
    </h2>
    <?php if (!$recentRsvp): ?>
        <p class="muted">Belum ada RSVP masuk.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr><th>Nama</th><th>Status</th><th class="num">Tamu</th><th>Undangan</th><th>Waktu</th></tr></thead>
                <tbody>
                <?php foreach ($recentRsvp as $r): ?>
                    <tr>
                        <td class="cell-title"><?= e($r['name']) ?></td>
                        <td><?= attending_badge($r['attending']) ?></td>
                        <td class="num"><?= (int)$r['guests'] ?></td>
                        <td class="cell-sub"><?= e($r['title']) ?></td>
                        <td class="cell-sub nowrap"><?= e(fmt_date($r['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php app_footer();