<?php
require_once __DIR__ . '/../../lib/layout.php';
require_user();

$db  = getDB();
$uid = (int)$_SESSION['user']['id'];
$self = url_user('feedback.php');

$cats = ['saran' => 'Saran fitur', 'bug' => 'Ada yang error', 'tampilan' => 'Tampilan / desain', 'lainnya' => 'Lainnya'];

/* Tabel feedback berasal dari migration_feedback.sql */
if (!$db->query("SHOW TABLES LIKE 'feedback'")->fetch()) {
    app_header('Beri Masukan', 'feedback', ['subtitle' => 'Bantu kami memperbaiki Invitia.']);
    echo '<div class="alert alert-error">Tabel <code>feedback</code> belum ada. Minta admin menjalankan <code>migration_feedback.sql</code> di database.</div>';
    app_footer();
    exit;
}

$errors = [];
$old = ['rating' => '', 'category' => 'saran', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Permintaan tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        $old['rating']   = is_string($_POST['rating'] ?? null) ? $_POST['rating'] : '';
        $old['category'] = is_string($_POST['category'] ?? null) ? $_POST['category'] : '';
        $old['message']  = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';

        if (!in_array($old['rating'], ['1', '2', '3', '4', '5'], true)) { $errors[] = 'Pilih penilaian 1 sampai 5.'; }
        if (!isset($cats[$old['category']])) { $errors[] = 'Kategori tidak valid.'; }
        $len = mb_strlen($old['message']);
        if ($len < 10)   { $errors[] = 'Masukan minimal 10 karakter supaya bisa kami pahami.'; }
        if ($len > 1000) { $errors[] = 'Masukan maksimal 1000 karakter.'; }

        if (!$errors) {
            $st = $db->prepare("SELECT COUNT(*) FROM feedback WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $st->execute([$uid]);
            if ((int)$st->fetchColumn() >= 5) {
                $errors[] = 'Kamu sudah mengirim 5 masukan dalam 24 jam terakhir. Coba lagi besok ya.';
            }
        }
        if (!$errors) {
            $db->prepare("INSERT INTO feedback (user_id, rating, category, message) VALUES (?, ?, ?, ?)")
               ->execute([$uid, (int)$old['rating'], $old['category'], $old['message']]);
            msg_add('success', 'Terima kasih! Masukanmu sudah kami terima.');
            redirect($self);
        }
    }
}

$st = $db->prepare("SELECT rating, category, message, status, admin_note, created_at FROM feedback WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$st->execute([$uid]);
$mine = $st->fetchAll();
$statusLabel = ['baru' => 'Terkirim', 'dibaca' => 'Sudah dibaca', 'ditindaklanjuti' => 'Ditindaklanjuti'];

app_header('Beri Masukan', 'feedback', ['subtitle' => 'Ceritakan pengalamanmu memakai Invitia: apa yang enak, apa yang membingungkan, atau apa yang error.']);
?>
<?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>

<div class="card">
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Seberapa puas kamu?</label>
            <div class="rate">
                <?php foreach ([1 => '1 · Kurang', 2 => '2', 3 => '3 · Cukup', 4 => '4', 5 => '5 · Sangat puas'] as $v => $lab): ?>
                    <label><input type="radio" name="rating" value="<?= $v ?>"<?= $old['rating'] === (string)$v ? ' checked' : '' ?>> <?= e($lab) ?></label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="form-group">
            <label for="category">Jenis masukan</label>
            <select id="category" name="category">
                <?php foreach ($cats as $k => $v): ?><option value="<?= e($k) ?>"<?= $old['category'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="message">Masukanmu</label>
            <textarea id="message" name="message" maxlength="1000" placeholder="Contoh: Di langkah 3, tombol upload foto kurang jelas. Aku bingung harus klik di mana."><?= e($old['message']) ?></textarea>
            <div class="hint">10 sampai 1000 karakter.</div>
        </div>
        <button class="btn-primary" type="submit">Kirim Masukan</button>
    </form>
</div>

<?php if ($mine): ?>
    <div class="card">
        <h2 class="card-title">Masukan yang pernah kamu kirim</h2>
        <?php foreach ($mine as $f): ?>
            <div class="list-row" style="align-items:flex-start">
                <div class="list-main">
                    <span class="stars" aria-label="Penilaian <?= (int)$f['rating'] ?> dari 5"><?= str_repeat('★', (int)$f['rating']) ?><span class="off"><?= str_repeat('★', 5 - (int)$f['rating']) ?></span></span>
                    <span class="muted"> · <?= e($cats[$f['category']] ?? $f['category']) ?> · <?= e(fmt_date($f['created_at'])) ?></span>
                    <div class="quote"><?= e($f['message']) ?></div>
                    <?php if (!empty($f['admin_note'])): ?><small><strong>Tanggapan admin:</strong> <?= e($f['admin_note']) ?></small><?php endif; ?>
                </div>
                <span class="badge <?= $f['status'] === 'ditindaklanjuti' ? 'b-ok' : ($f['status'] === 'dibaca' ? 'b-warn' : 'b-muted') ?>"><?= e($statusLabel[$f['status']] ?? $f['status']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php app_footer();