<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/storage.php';
require_admin();

$db = getDB();

/* Kumpulkan hasil pemeriksaan: [kelompok, nama, status(ok|warn|bad), keterangan] */
$checks = [];
$add = function ($group, $name, $status, $detail) use (&$checks) { $checks[] = [$group, $name, $status, $detail]; };

/* --- Server --- */
$add('Server', 'Versi PHP', version_compare(PHP_VERSION, '8.0.0', '>=') ? 'ok' : 'bad', PHP_VERSION . ' (butuh 8.0 atau lebih baru)');
foreach (['pdo_mysql' => 'PDO MySQL', 'mbstring' => 'mbstring', 'curl' => 'cURL', 'fileinfo' => 'fileinfo', 'gd' => 'GD (cek gambar upload)'] as $ext => $label) {
    $add('Server', 'Ekstensi ' . $label, extension_loaded($ext) ? 'ok' : ($ext === 'pdo_mysql' || $ext === 'mbstring' ? 'bad' : 'warn'), extension_loaded($ext) ? 'Aktif' : 'Tidak aktif');
}
$add('Server', 'Batas upload', 'ok', 'upload_max_filesize ' . ini_get('upload_max_filesize') . ', post_max_size ' . ini_get('post_max_size'));
$disp = filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOLEAN);
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '::1'], true);
$add('Server', 'Tampilkan error ke pengunjung', $disp && !$isLocal ? 'warn' : 'ok', $disp ? 'display_errors aktif' . ($isLocal ? ' (wajar di lokal)' : ', sebaiknya dimatikan di server online') : 'display_errors mati');
$add('Server', 'Koneksi HTTPS', $https || $isLocal ? 'ok' : 'warn', $https ? 'Aman (HTTPS)' : ($isLocal ? 'HTTP (wajar di lokal)' : 'HTTP (gunakan HTTPS di server online)'));
$add('Server', 'BASE_URL', 'ok', BASE_URL);

/* --- Database --- */
$ver = (string)$db->query("SELECT VERSION()")->fetchColumn();
$add('Database', 'Koneksi', 'ok', 'Terhubung ke ' . DB_NAME . ' (server ' . $ver . ')');
$tables = ['users', 'prompts', 'rsvps', 'wishes', 'quota_logs', 'feedback'];
foreach ($tables as $t) {
    $exists = (bool)$db->query("SHOW TABLES LIKE " . $db->quote($t))->fetch();
    if ($exists) {
        $n = (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        $add('Database', 'Tabel ' . $t, 'ok', number_format($n, 0, ',', '.') . ' baris');
    } else {
        $add('Database', 'Tabel ' . $t, $t === 'feedback' ? 'warn' : 'bad', $t === 'feedback' ? 'Belum ada, jalankan migration_feedback.sql' : 'Tidak ditemukan, jalankan schema.sql');
    }
}
foreach ([['users', 'is_active', 'migration_users.sql / schema.sql'], ['wishes', 'is_hidden', 'migration_dashboard.sql']] as [$t, $col, $fix]) {
    $has = (bool)$db->query("SHOW COLUMNS FROM `$t` LIKE " . $db->quote($col))->fetch();
    $add('Database', "Kolom $t.$col", $has ? 'ok' : 'bad', $has ? 'Ada' : 'Tidak ada, jalankan ' . $fix);
}

/* --- Penyimpanan upload --- */
$dir = storage_local_dir();
$add('Penyimpanan', 'Folder upload', is_dir($dir) && is_writable($dir) ? 'ok' : 'bad', is_dir($dir) ? (is_writable($dir) ? 'Bisa ditulis' : 'Tidak bisa ditulis, periksa izin folder assets/') : 'Folder tidak ada');
$cnt = 0; $bytes = 0;
if (is_dir($dir)) {
    foreach (scandir($dir) ?: [] as $f) {
        if (preg_match('/^' . storage_name_pattern() . '$/', $f)) { $cnt++; $bytes += (int)@filesize($dir . $f); }
    }
}
$add('Penyimpanan', 'File upload tersimpan', 'ok', $cnt . ' file, ' . number_format($bytes / 1048576, 1, ',', '.') . ' MB');

/* --- Layanan login --- */
$sbUrl = defined('SUPABASE_URL') ? (string)SUPABASE_URL : '';
$sbKey = defined('SUPABASE_KEY') ? (string)SUPABASE_KEY : '';
$sbOk  = $sbUrl !== '' && $sbKey !== '' && strpos($sbUrl, 'your-project') === false && strpos($sbKey, 'your-supabase') === false;
$add('Login', 'Supabase Auth', $sbOk ? 'ok' : 'bad', $sbOk ? 'Terkonfigurasi (kunci tidak ditampilkan)' : 'Belum dikonfigurasi di config.php');

$counts = ['ok' => 0, 'warn' => 0, 'bad' => 0];
foreach ($checks as $c) { $counts[$c[2]]++; }
$labels = ['ok' => ['st-ok', 'Baik'], 'warn' => ['st-warn', 'Perhatian'], 'bad' => ['st-bad', 'Bermasalah']];

app_header('Status Sistem', 'system', ['subtitle' => 'Pemeriksaan cepat kesiapan aplikasi. Berguna sebelum demo atau saat deploy ke server online.']);
?>
<div class="stats">
    <div class="stat"><span class="stat-ico"><?= icon('check') ?></span><div><div class="stat-val"><?= $counts['ok'] ?></div><div class="stat-label">Pemeriksaan baik</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('shield') ?></span><div><div class="stat-val"><?= $counts['warn'] ?></div><div class="stat-label">Perlu perhatian</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('server') ?></span><div><div class="stat-val"><?= $counts['bad'] ?></div><div class="stat-label">Bermasalah</div></div></div>
</div>

<?php if ($counts['bad'] > 0): ?>
    <div class="alert alert-error">Ada pemeriksaan yang bermasalah. Perbaiki yang bertanda merah sebelum demo.</div>
<?php elseif ($counts['warn'] > 0): ?>
    <div class="alert alert-info">Semua fungsi utama siap. Ada beberapa hal yang sebaiknya dicek (bertanda kuning).</div>
<?php else: ?>
    <div class="alert alert-success">Semua pemeriksaan baik.</div>
<?php endif; ?>

<?php
$grouped = [];
foreach ($checks as $c) { $grouped[$c[0]][] = $c; }
foreach ($grouped as $group => $items): ?>
    <div class="card flush">
        <h2 class="card-title" style="padding:18px 18px 0"><?= e($group) ?></h2>
        <div class="table-wrap">
            <table class="tbl">
                <thead><tr><th style="width:34%">Pemeriksaan</th><th style="width:16%">Status</th><th>Keterangan</th></tr></thead>
                <tbody>
                <?php foreach ($items as [, $name, $status, $detail]): ?>
                    <tr>
                        <td class="cell-title"><?= e($name) ?></td>
                        <td class="<?= e($labels[$status][0]) ?>"><?= e($labels[$status][1]) ?></td>
                        <td class="cell-sub"><?= e($detail) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
<?php app_footer();