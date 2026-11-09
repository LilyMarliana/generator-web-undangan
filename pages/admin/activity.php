<?php
require_once __DIR__ . '/../../lib/layout.php';
require_admin();

$db  = getDB();
$one = fn($sql) => (int)$db->query($sql)->fetchColumn();

/* Batas harian paket Free. Pengguna Pro (kalau fitur pembayaran sudah aktif) tanpa batas.
   Sengaja tidak memakai daily_quota(): fungsi itu bergantung pada pengguna yang sedang login. */
$limit = 5;
$hasPlan = (bool)$db->query("SHOW COLUMNS FROM users LIKE 'plan'")->fetch()
        && (bool)$db->query("SHOW COLUMNS FROM users LIKE 'subscription_expires_at'")->fetch();
$proSql  = $hasPlan ? "(u.plan = 'pro' AND u.subscription_expires_at IS NOT NULL AND u.subscription_expires_at > NOW())" : "(0 = 1)";

/* Ringkasan pemakaian */
$usedToday   = $one("SELECT COALESCE(SUM(`count`),0) FROM quota_logs WHERE `date` = CURDATE()");
$activeToday = $one("SELECT COUNT(DISTINCT user_id) FROM quota_logs WHERE `date` = CURDATE() AND `count` > 0");
$total14     = $one("SELECT COALESCE(SUM(`count`),0) FROM quota_logs WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)");
$avg14       = round($total14 / 14, 1);
$atLimit     = $one("SELECT COUNT(*) FROM quota_logs q JOIN users u ON u.id = q.user_id WHERE q.`date` = CURDATE() AND q.`count` >= $limit AND NOT $proSql");
$proActive   = $hasPlan ? $one("SELECT COUNT(*) FROM users u WHERE u.role = 'user' AND $proSql") : 0;

/* Deret 14 hari */
$today = $db->query("SELECT CURDATE()")->fetchColumn();
$map   = array_column($db->query(
    "SELECT `date` d, SUM(`count`) c FROM quota_logs WHERE `date` >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY `date`"
)->fetchAll(), 'c', 'd');
$days = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime($today . " -$i day"));
    $days[] = ['label' => date('d/m', strtotime($d)), 'count' => (int)($map[$d] ?? 0)];
}
$max = max(1, max(array_column($days, 'count')));

/* Corong aktivasi: daftar -> buat undangan -> dapat RSVP */
$nUsers   = $one("SELECT COUNT(*) FROM users WHERE role = 'user'");
$nCreated = $one("SELECT COUNT(DISTINCT p.user_id) FROM prompts p JOIN users u ON u.id = p.user_id WHERE u.role = 'user'");
$nRsvp    = $one("SELECT COUNT(DISTINCT p.user_id) FROM prompts p JOIN rsvps r ON r.prompt_id = p.id JOIN users u ON u.id = p.user_id WHERE u.role = 'user'");
$pct = fn($a, $b) => $b > 0 ? round($a / $b * 100) : 0;

/* Pemakaian per pengguna */
$rows = $db->query(
    "SELECT u.id, u.name, u.email,
            COALESCE(SUM(CASE WHEN q.`date` = CURDATE() THEN q.`count` END), 0) AS today,
            COALESCE(SUM(CASE WHEN q.`date` >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN q.`count` END), 0) AS week,
            MAX(q.`date`) AS last_date,
            $proSql AS is_pro
     FROM users u LEFT JOIN quota_logs q ON q.user_id = u.id
     WHERE u.role = 'user'
     GROUP BY u.id, u.name, u.email, is_pro
     ORDER BY week DESC, today DESC, u.id DESC LIMIT 15"
)->fetchAll();

app_header('Aktivitas & Kuota', 'activity', [
    'subtitle' => 'Seberapa aktif pengguna memakai generator. Batas harian paket Free ' . $limit . ' undangan' . ($hasPlan ? ', paket Pro tanpa batas.' : ' per pengguna.'),
]);
?>
<div class="stats">
    <div class="stat"><span class="stat-ico"><?= icon('plus') ?></span><div><div class="stat-val"><?= $usedToday ?></div><div class="stat-label">Generate hari ini</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('users') ?></span><div><div class="stat-val"><?= $activeToday ?></div><div class="stat-label">Pengguna aktif hari ini</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('chart') ?></span><div><div class="stat-val"><?= e((string)$avg14) ?></div><div class="stat-label">Rata-rata generate per hari (14 hari)</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('shield') ?></span><div><div class="stat-val"><?= $atLimit ?></div><div class="stat-label">Pengguna Free mencapai batas hari ini</div></div></div>
</div>

<div class="grid-2">
    <div class="card">
        <h2 class="card-title">Generate 14 hari terakhir</h2>
        <div class="bars bars-14">
            <?php foreach ($days as $d): ?>
                <div class="bar" title="<?= e($d['label']) ?>: <?= $d['count'] ?> generate">
                    <em><?= $d['count'] ?: '' ?></em>
                    <b style="height:<?= max(3, round($d['count'] / $max * 70)) ?>px"></b>
                    <small><?= e($d['label']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <h2 class="card-title">Corong aktivasi pengguna</h2>
        <p class="muted" style="margin-bottom:14px;font-size:.88rem">Dari yang mendaftar, berapa yang benar-benar memakai produk.</p>
        <?php foreach ([['Mendaftar', $nUsers, 100], ['Membuat undangan', $nCreated, $pct($nCreated, $nUsers)], ['Undangan menerima RSVP', $nRsvp, $pct($nRsvp, $nUsers)]] as [$label, $n, $p]): ?>
            <div style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;font-size:.9rem;margin-bottom:5px"><span><?= e($label) ?></span><strong><?= $n ?> <span class="muted">(<?= $p ?>%)</span></strong></div>
                <div class="meter"><b style="width:<?= (int)$p ?>%"></b></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card flush" style="margin-top:20px">
    <h2 class="card-title" style="padding:18px 18px 0">Pemakaian per pengguna</h2>
    <?php if (!$rows): ?>
        <p class="empty" style="padding:24px">Belum ada pengguna.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table class="tbl">
            <thead><tr><th>Pengguna</th><th>Hari ini</th><th class="num">7 hari</th><th>Terakhir aktif</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r):
                $today_n = (int)$r['today'];
                $isPro   = (int)$r['is_pro'] === 1;
                $w = min(100, round($today_n / $limit * 100));
            ?>
                <tr>
                    <td class="cell-title"><?= e($r['name']) ?><br><small class="muted"><?= e($r['email']) ?></small></td>
                    <td style="min-width:150px">
                        <?php if ($isPro): ?>
                            <span class="badge b-accent">Pro</span> <span class="nowrap"><?= $today_n ?> hari ini &middot; tanpa batas</span>
                        <?php else: ?>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="meter<?= $today_n >= $limit ? ' full' : '' ?>" style="flex:1"><b style="width:<?= (int)$w ?>%"></b></div>
                            <span class="nowrap"><?= $today_n ?>/<?= $limit ?></span>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= (int)$r['week'] ?></td>
                    <td class="cell-sub nowrap"><?= $r['last_date'] ? e(fmt_date($r['last_date'], false)) : '&mdash;' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php app_footer();