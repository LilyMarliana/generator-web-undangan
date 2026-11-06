<?php
require_once __DIR__ . '/../../lib/layout.php';
require_admin();

$db = getDB();
$one = fn($sql) => (int)$db->query($sql)->fetchColumn();

/* Statistik utama */
$totalUsers   = $one("SELECT COUNT(*) FROM users");
$activeUsers  = $one("SELECT COUNT(*) FROM users WHERE is_active = 1");
$adminCount   = $one("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$totalPrompts = $one("SELECT COUNT(*) FROM prompts");
$monthPrompts = $one("SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
$totalRsvp    = $one("SELECT COUNT(*) FROM rsvps");
$totalWishes  = $one("SELECT COUNT(*) FROM wishes");
$guestsYes    = $one("SELECT COALESCE(SUM(guests),0) FROM rsvps WHERE attending='hadir'");
$newUsers7    = $one("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
$quotaToday   = $one("SELECT COALESCE(SUM(`count`),0) FROM quota_logs WHERE `date`=CURDATE()");
$hiddenWishes = $one("SELECT COUNT(*) FROM wishes WHERE is_hidden=1");
$noRsvp       = $one("SELECT COUNT(*) FROM prompts p WHERE NOT EXISTS (SELECT 1 FROM rsvps r WHERE r.prompt_id=p.id)");
$inactiveUsers = $totalUsers - $activeUsers;

/* Deret 7 hari terakhir (tabel hanya konstanta internal) */
$today = $db->query("SELECT CURDATE()")->fetchColumn();
function series7($db, $table, $today) {
    $rows = $db->query("SELECT DATE(created_at) d, COUNT(*) c FROM $table WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)")->fetchAll();
    $m = array_column($rows, 'c', 'd');
    $out = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime($today . " -$i day"));
        $out[] = ['label' => date('d/m', strtotime($d)), 'count' => (int)($m[$d] ?? 0)];
    }
    return $out;
}
function bars7($days, $unit) {
    $max = max(1, max(array_column($days, 'count')));
    $h = '<div class="bars">';
    foreach ($days as $d) {
        $h .= '<div class="bar" title="' . e($d['label']) . ': ' . $d['count'] . ' ' . $unit . '"><em>' . ($d['count'] ?: '') . '</em><b style="height:' . max(3, round($d['count'] / $max * 70)) . 'px"></b><small>' . e($d['label']) . '</small></div>';
    }
    return $h . '</div>';
}
$daysInv   = series7($db, 'prompts', $today);
$daysUsers = series7($db, 'users', $today);

/* Komposisi RSVP */
$rs = array_column($db->query("SELECT attending, COUNT(*) c FROM rsvps GROUP BY attending")->fetchAll(), 'c', 'attending');
$rsYes = (int)($rs['hadir'] ?? 0); $rsMaybe = (int)($rs['belum_pasti'] ?? 0); $rsNo = (int)($rs['tidak_hadir'] ?? 0);
$rsTot = max(1, $rsYes + $rsMaybe + $rsNo);

/* Daftar */
$recentUsers = $db->query("SELECT id, name, email, role, is_active, created_at FROM users ORDER BY id DESC LIMIT 5")->fetchAll();
$recentPrompts = $db->query(
    "SELECT p.id, p.title, p.created_at, u.name AS owner,
            (SELECT COUNT(*) FROM rsvps WHERE prompt_id = p.id) AS rsvp_count
     FROM prompts p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 5"
)->fetchAll();
$topUsers = $db->query(
    "SELECT u.name, u.email, COUNT(p.id) AS n,
            (SELECT COUNT(*) FROM rsvps r JOIN prompts p2 ON p2.id=r.prompt_id WHERE p2.user_id=u.id) AS rs
     FROM users u JOIN prompts p ON p.user_id=u.id GROUP BY u.id ORDER BY n DESC, rs DESC LIMIT 5"
)->fetchAll();
$recentWishes = $db->query(
    "SELECT w.name, w.message, w.is_hidden, w.created_at, p.title
     FROM wishes w JOIN prompts p ON p.id=w.prompt_id ORDER BY w.id DESC LIMIT 5"
)->fetchAll();

$userTotalSafe = max(1, $totalUsers);
$firstName = explode(' ', trim($_SESSION['user']['name']))[0];

app_header('Ringkasan', 'dashboard', [
    'heading'  => 'Halo, ' . $firstName,
    'subtitle' => 'Ringkasan aktivitas seluruh pengguna di platform.',
    'actions'  => '<a class="btn" href="users.php">' . icon('users') . ' Kelola Pengguna</a>',
]);
?>
<section class="stats">
    <div class="stat"><span class="stat-ico"><?= icon('users') ?></span><div><div class="stat-val"><?= $totalUsers ?></div><div class="stat-label">Total pengguna (+<?= $newUsers7 ?> minggu ini)</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('list') ?></span><div><div class="stat-val"><?= $totalPrompts ?></div><div class="stat-label">Undangan dibuat</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('check') ?></span><div><div class="stat-val"><?= $totalRsvp ?></div><div class="stat-label">RSVP masuk (<?= $guestsYes ?> tamu hadir)</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('chat') ?></span><div><div class="stat-val"><?= $totalWishes ?></div><div class="stat-label">Ucapan tamu</div></div></div>
</section>

<div class="grid-2 mb">
    <section class="card">
        <h2 class="card-title">Undangan dibuat</h2>
        <p class="small muted"><?= $monthPrompts ?> undangan bulan ini · <?= $quotaToday ?> generate terpakai hari ini</p>
        <h3 class="card-sub">7 hari terakhir</h3>
        <?= bars7($daysInv, 'undangan') ?>
    </section>
    <section class="card">
        <h2 class="card-title">Pengguna baru</h2>
        <p class="small muted"><?= $newUsers7 ?> pendaftar dalam 7 hari terakhir</p>
        <h3 class="card-sub">7 hari terakhir</h3>
        <?= bars7($daysUsers, 'pengguna') ?>
    </section>
</div>

<div class="grid-2 mb">
    <section class="card">
        <h2 class="card-title">Status akun</h2>
        <div class="stack" role="img" aria-label="Komposisi akun aktif dan nonaktif">
            <span class="seg-ok" style="width:<?= round($activeUsers / $userTotalSafe * 100, 1) ?>%"></span>
            <span class="seg-no" style="width:<?= round($inactiveUsers / $userTotalSafe * 100, 1) ?>%"></span>
        </div>
        <div class="legend">
            <span><i class="seg-ok"></i>Aktif <?= $activeUsers ?></span>
            <span><i class="seg-no"></i>Nonaktif <?= $inactiveUsers ?></span>
            <span><i class="seg-warn"></i>Admin <?= $adminCount ?></span>
        </div>

        <h2 class="card-title" style="margin-top:22px">Komposisi RSVP</h2>
        <div class="stack" role="img" aria-label="Komposisi status kehadiran">
            <span class="seg-ok" style="width:<?= round($rsYes / $rsTot * 100, 1) ?>%"></span>
            <span class="seg-warn" style="width:<?= round($rsMaybe / $rsTot * 100, 1) ?>%"></span>
            <span class="seg-no" style="width:<?= round($rsNo / $rsTot * 100, 1) ?>%"></span>
        </div>
        <div class="legend">
            <span><i class="seg-ok"></i>Hadir <?= $rsYes ?></span>
            <span><i class="seg-warn"></i>Belum pasti <?= $rsMaybe ?></span>
            <span><i class="seg-no"></i>Tidak hadir <?= $rsNo ?></span>
        </div>
    </section>

    <section class="card">
        <h2 class="card-title">Perlu perhatian</h2>
        <div class="list-row"><span class="list-main">Akun nonaktif<small>Tinjau apakah perlu diaktifkan kembali</small></span><a class="badge <?= $inactiveUsers ? 'b-warn' : 'b-ok' ?> nowrap" href="users.php"><?= $inactiveUsers ?></a></div>
        <div class="list-row"><span class="list-main">Ucapan disembunyikan<small>Sudah dimoderasi oleh pemilik undangan</small></span><span class="badge b-muted nowrap"><?= $hiddenWishes ?></span></div>
        <div class="list-row"><span class="list-main">Undangan tanpa RSVP<small>Mungkin belum dipasang di website</small></span><a class="badge <?= $noRsvp ? 'b-warn' : 'b-ok' ?> nowrap" href="prompts.php"><?= $noRsvp ?></a></div>

        <h2 class="card-title" style="margin-top:22px">Pengguna terbaru <a href="users.php">Lihat semua</a></h2>
        <?php if (!$recentUsers): ?><p class="muted">Belum ada pengguna.</p><?php endif; ?>
        <?php foreach ($recentUsers as $u): ?>
            <div class="list-row">
                <span class="list-main"><?= e($u['name']) ?><small><?= e($u['email']) ?></small></span>
                <span class="badge <?= $u['role'] === 'admin' ? 'b-warn' : 'b-muted' ?> nowrap"><?= $u['role'] === 'admin' ? 'Admin' : 'User' ?></span>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<div class="grid-2 mb">
    <section class="card">
        <h2 class="card-title">Pengguna paling aktif</h2>
        <?php if (!$topUsers): ?><p class="muted">Belum ada data.</p><?php endif; ?>
        <?php foreach ($topUsers as $i => $t): ?>
            <div class="list-row">
                <span class="list-main"><?= ($i + 1) . '. ' . e($t['name']) ?><small><?= e($t['email']) ?> · <?= (int)$t['rs'] ?> RSVP</small></span>
                <span class="badge b-ok nowrap"><?= (int)$t['n'] ?> undangan</span>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="card">
        <h2 class="card-title">Ucapan terbaru</h2>
        <?php if (!$recentWishes): ?><p class="muted">Belum ada ucapan masuk.</p><?php endif; ?>
        <?php foreach ($recentWishes as $w): ?>
            <div class="list-row">
                <span class="list-main"><?= e($w['name']) ?> <small>di “<?= e($w['title']) ?>”</small><small><?= e(mb_strimwidth($w['message'], 0, 80, '…')) ?></small></span>
                <?php if ($w['is_hidden']): ?><span class="badge b-muted nowrap">Disembunyikan</span><?php else: ?><span class="small muted nowrap"><?= e(fmt_date($w['created_at'], false)) ?></span><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<section class="card">
    <h2 class="card-title">Undangan terbaru <a href="prompts.php">Lihat semua</a></h2>
    <?php if (!$recentPrompts): ?>
        <p class="muted">Belum ada undangan yang dibuat.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr><th>Judul</th><th>Pemilik</th><th class="num">RSVP</th><th>Dibuat</th></tr></thead>
                <tbody>
                <?php foreach ($recentPrompts as $r): ?>
                    <tr>
                        <td class="cell-title"><?= e($r['title']) ?></td>
                        <td class="cell-sub"><?= e($r['owner']) ?></td>
                        <td class="num"><?= (int)$r['rsvp_count'] ?></td>
                        <td class="cell-sub nowrap"><?= e(fmt_date($r['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php app_footer();