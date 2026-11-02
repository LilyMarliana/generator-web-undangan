<?php
require_once __DIR__ . '/../../lib/layout.php';
require_user();

$id = (int)($_GET['id'] ?? 0);
$p  = owned_prompt($id);
if (!$p) {
    msg_add('error', 'Undangan tidak ditemukan atau bukan milikmu.');
    redirect(url_user('undangan.php'));
}

$db = getDB();
$st = $db->prepare(
    "SELECT COUNT(*) total,
            COALESCE(SUM(attending='hadir'),0) hadir,
            COALESCE(SUM(CASE WHEN attending='hadir' THEN guests ELSE 0 END),0) tamu
     FROM rsvps WHERE prompt_id=?"
);
$st->execute([$id]);
$rs = $st->fetch();

$st = $db->prepare("SELECT COUNT(*) FROM wishes WHERE prompt_id=?");
$st->execute([$id]);
$wishCount = (int)$st->fetchColumn();

$apiBase = rtrim(BASE_URL, '/') . '/api.php';
$pair    = implode(' & ', array_filter([trim((string)$p['groom_name']), trim((string)$p['bride_name'])]));

app_header($p['title'], 'undangan', [
    'heading'  => $p['title'],
    'subtitle' => $pair !== '' ? $pair : 'Detail undangan',
    'actions'  => '<a class="btn" href="undangan.php">&larr; Kembali</a><a class="btn-primary" href="edit.php?id=' . $id . '">' . icon('edit') . ' Edit</a>',
]);
?>
<section class="stats">
    <div class="stat"><span class="stat-ico"><?= icon('check') ?></span><div><div class="stat-val"><?= (int)$rs['total'] ?></div><div class="stat-label">RSVP masuk</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('users') ?></span><div><div class="stat-val"><?= (int)$rs['tamu'] ?></div><div class="stat-label">Tamu akan hadir</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('chat') ?></span><div><div class="stat-val"><?= $wishCount ?></div><div class="stat-label">Ucapan tamu</div></div></div>
</section>

<div class="grid-2 mb">
    <section class="card">
        <h2 class="card-title">Informasi</h2>
        <dl class="kv">
            <div><dt>Mempelai pria</dt><dd><?= e($p['groom_name'] ?: '-') ?></dd></div>
            <div><dt>Mempelai wanita</dt><dd><?= e($p['bride_name'] ?: '-') ?></dd></div>
            <div><dt>Dibuat</dt><dd><?= e(fmt_date($p['created_at'])) ?></dd></div>
            <div><dt>Diperbarui</dt><dd><?= e(fmt_date($p['updated_at'])) ?></dd></div>
        </dl>
        <?php if (has_page('rsvp.php') || has_page('wishes.php')): ?>
            <div style="margin-top:18px;display:flex;gap:8px;flex-wrap:wrap;">
                <?php if (has_page('rsvp.php')): ?><a class="btn btn-sm" href="rsvp.php?prompt_id=<?= $id ?>">Lihat RSVP</a><?php endif; ?>
                <?php if (has_page('wishes.php')): ?><a class="btn btn-sm" href="wishes.php?prompt_id=<?= $id ?>">Lihat Ucapan</a><?php endif; ?>
                <?php if (has_page('rsvp_export.php')): ?><a class="btn btn-sm" href="rsvp_export.php?prompt_id=<?= $id ?>"><?= icon('download') ?> Export RSVP (CSV)</a><?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2 class="card-title">API Key RSVP &amp; Ucapan</h2>
        <div class="keybox">
            <code><?= e($p['api_key']) ?></code>
            <button type="button" class="btn-success btn-sm" data-copy="<?= e($p['api_key']) ?>" data-label="Salin">Salin</button>
        </div>
        <p class="small muted" style="margin:10px 0;">Endpoint: <code><?= e($apiBase) ?>?action=rsvp&amp;api_key=...</code></p>
        <form method="post" action="regenerate_key.php" data-confirm="Buat API key baru? Website undangan yang sudah dipublikasikan dengan key lama harus diperbarui ke key baru.">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
            <button class="btn-outline btn-sm" type="submit"><?= icon('key') ?> Buat ulang API key</button>
        </form>
    </section>
</div>

<section class="card">
    <h2 class="card-title">Prompt
        <span style="display:flex;gap:8px;">
            <button type="button" class="btn-outline btn-sm" id="togglePrompt">Tampilkan penuh</button>
            <button type="button" class="btn-success btn-sm" data-copy="<?= e($p['prompt']) ?>" data-label="Salin prompt"><?= icon('copy') ?> Salin prompt</button>
        </span>
    </h2>
    <div class="promptbox" id="promptBox"><?= e($p['prompt']) ?></div>
</section>

<section class="card danger-zone">
    <h2 class="card-title">Zona berbahaya</h2>
    <p class="small muted" style="margin-bottom:12px;">Menghapus undangan juga menghapus seluruh RSVP, ucapan, dan file foto/musik yang diupload untuk undangan ini.</p>
    <form method="post" action="delete.php" data-confirm="Hapus undangan ini beserta seluruh RSVP, ucapan, dan file uploadnya? Tindakan ini tidak bisa dibatalkan.">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
        <button class="btn-danger" type="submit"><?= icon('trash') ?> Hapus undangan</button>
    </form>
</section>
<script>
document.getElementById('togglePrompt').addEventListener('click', function () {
    var b = document.getElementById('promptBox'), open = b.classList.toggle('open');
    this.textContent = open ? 'Ciutkan' : 'Tampilkan penuh';
});
</script>
<?php app_footer();