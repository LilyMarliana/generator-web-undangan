<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/rsvp.php';
require_user();

$db  = getDB();
$uid = (int)$_SESSION['user']['id'];

/* Undangan milik user (untuk filter dan form tambah) */
$st = $db->prepare("SELECT id, title FROM prompts WHERE user_id=? ORDER BY id DESC");
$st->execute([$uid]);
$myPrompts = $st->fetchAll();
$myIds     = array_map('intval', array_column($myPrompts, 'id'));
$statuses  = rsvp_status_options();

$f = rsvp_filters_from_get($myIds);
[$where, $params] = rsvp_where($uid, $f);

/* Ringkasan (mengikuti pilihan undangan, bukan filter status/cari) */
$sumWhere  = "p.user_id = ?" . ($f['prompt_id'] ? " AND r.prompt_id = ?" : "");
$sumParams = $f['prompt_id'] ? [$uid, $f['prompt_id']] : [$uid];
$st = $db->prepare(
    "SELECT COUNT(*) total,
            COALESCE(SUM(r.attending='hadir'),0) hadir,
            COALESCE(SUM(r.attending='tidak_hadir'),0) tidak,
            COALESCE(SUM(r.attending='belum_pasti'),0) belum,
            COALESCE(SUM(CASE WHEN r.attending='hadir' THEN r.guests ELSE 0 END),0) tamu
     FROM rsvps r JOIN prompts p ON p.id=r.prompt_id WHERE $sumWhere"
);
$st->execute($sumParams);
$sum = $st->fetch();

/* Daftar + pagination */
$st = $db->prepare("SELECT COUNT(*) FROM rsvps r JOIN prompts p ON p.id=r.prompt_id WHERE $where");
$st->execute($params);
$total = (int)$st->fetchColumn();

[$page, $per] = page_params(10);
$pages  = max(1, (int)ceil($total / $per));
$page   = min($page, $pages);
$offset = ($page - 1) * $per;

$st = $db->prepare(
    "SELECT r.id, r.prompt_id, r.name, r.attending, r.guests, r.message, r.created_at, p.title
     FROM rsvps r JOIN prompts p ON p.id=r.prompt_id
     WHERE $where ORDER BY r.id DESC LIMIT $per OFFSET $offset"
);
$st->execute($params);
$rows = $st->fetchAll();

/* Alamat kembali (mempertahankan filter dan halaman) dan link export */
$backQs = $_GET;
$back   = 'rsvp.php' . ($backQs ? '?' . http_build_query($backQs) : '');
$exQs   = array_filter(['prompt_id' => $f['prompt_id'] ?: null, 'status' => $f['status'] ?: null, 'q' => $f['q'] !== '' ? $f['q'] : null]);
$exUrl  = 'rsvp_export.php?' . http_build_query($exQs);
$sep    = $exQs ? '&' : '';

$actions = '<button type="button" class="btn-primary" id="addBtn">' . icon('plus') . ' Tambah RSVP</button>'
         . '<a class="btn" href="' . e($exUrl . $sep . 'fmt=csv') . '">' . icon('download') . ' Export CSV</a>'
         . '<a class="btn" href="' . e($exUrl . $sep . 'fmt=excel') . '" title="Untuk Excel dengan pengaturan regional Indonesia (pemisah titik koma)">Export Excel</a>';

app_header('RSVP Tamu', 'rsvp', [
    'subtitle' => 'Pantau konfirmasi kehadiran tamu, tambah manual, ubah, atau hapus.',
    'actions'  => $myPrompts ? $actions : '',
]);
?>
<?php if (!$myPrompts): ?>
    <div class="card empty">
        <h3>Belum ada undangan</h3>
        <p>RSVP masuk dari website undangan yang kamu buat. Buat undangan dulu.</p>
        <a class="btn-primary" href="generator.php">Buat Undangan</a>
    </div>
<?php else: ?>

<section class="stats">
    <div class="stat"><span class="stat-ico"><?= icon('list') ?></span><div><div class="stat-val"><?= (int)$sum['total'] ?></div><div class="stat-label">Total RSVP</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('check') ?></span><div><div class="stat-val"><?= (int)$sum['hadir'] ?></div><div class="stat-label">Hadir</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('users') ?></span><div><div class="stat-val"><?= (int)$sum['tamu'] ?></div><div class="stat-label">Total tamu hadir</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('chat') ?></span><div><div class="stat-val"><?= (int)$sum['tidak'] ?> / <?= (int)$sum['belum'] ?></div><div class="stat-label">Tidak hadir / belum pasti</div></div></div>
</section>

<form class="toolbar" method="get">
    <select name="prompt_id" aria-label="Undangan" onchange="this.form.submit()">
        <option value="0">Semua undangan</option>
        <?php foreach ($myPrompts as $mp): ?>
            <option value="<?= (int)$mp['id'] ?>" <?= (int)$mp['id'] === $f['prompt_id'] ? 'selected' : '' ?>><?= e($mp['title']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" aria-label="Status" onchange="this.form.submit()">
        <option value="">Semua status</option>
        <?php foreach ($statuses as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $k === $f['status'] ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="search">
        <?= icon('search') ?>
        <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Cari nama atau pesan..." aria-label="Cari">
    </div>
    <button class="btn-outline" type="submit">Cari</button>
    <?php if ($f['prompt_id'] || $f['status'] !== '' || $f['q'] !== ''): ?><a class="btn-outline" href="rsvp.php">Reset</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
    <div class="card empty">
        <?php if ($f['prompt_id'] || $f['status'] !== '' || $f['q'] !== ''): ?>
            <h3>Tidak ditemukan</h3><p>Tidak ada RSVP yang cocok dengan filter ini.</p>
        <?php else: ?>
            <h3>Belum ada RSVP</h3><p>Begitu tamu mengisi RSVP di website undangan, datanya muncul di sini. Kamu juga bisa menambahkannya manual.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <form method="post" action="rsvp_action.php" id="bulkForm">
        <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>">
        <div class="bulkbar">
            <label class="check"><input type="checkbox" id="checkAll"> Pilih semua di halaman ini</label>
            <span class="small muted" id="selCount"></span>
            <button class="btn-danger btn-sm" type="submit" name="do" value="bulk_delete" id="bulkBtn"><?= icon('trash') ?> Hapus terpilih</button>
            <span class="small muted" style="margin-left:auto;"><?= $total ?> RSVP</span>
        </div>
        <div class="table-wrap">
            <table class="tbl stack-sm">
                <thead><tr><th style="width:36px;"></th><th>Nama</th><th>Status</th><th class="num">Tamu</th><th>Pesan</th><th>Undangan</th><th>Waktu</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td data-label="Pilih"><input type="checkbox" class="rowcheck" name="ids[]" value="<?= (int)$r['id'] ?>" aria-label="Pilih <?= e($r['name']) ?>"></td>
                        <td class="cell-title" data-label="Nama"><?= e($r['name']) ?></td>
                        <td data-label="Status"><?= attending_badge($r['attending']) ?></td>
                        <td class="num" data-label="Tamu"><?= (int)$r['guests'] ?></td>
                        <td class="msg" data-label="Pesan"><?= $r['message'] !== null && $r['message'] !== '' ? e($r['message']) : '<span class="muted">-</span>' ?></td>
                        <td class="cell-sub" data-label="Undangan"><?= e($r['title']) ?></td>
                        <td class="cell-sub nowrap" data-label="Waktu"><?= e(fmt_date($r['created_at'])) ?></td>
                        <td class="cell-actions">
                            <div class="row-actions">
                                <button type="button" class="btn btn-sm editBtn"
                                    data-id="<?= (int)$r['id'] ?>" data-name="<?= e($r['name']) ?>" data-status="<?= e($r['attending']) ?>"
                                    data-guests="<?= (int)$r['guests'] ?>" data-message="<?= e((string)$r['message']) ?>"><?= icon('edit') ?> Edit</button>
                                <button type="submit" class="btn-danger btn-sm" name="delete_id" value="<?= (int)$r['id'] ?>"
                                    onclick="return confirm('Hapus RSVP ini?')"><?= icon('trash') ?> Hapus</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
    <?= pager($total, $page, $per) ?>
<?php endif; ?>

<!-- Dialog tambah -->
<dialog id="addDlg">
    <h3>Tambah RSVP</h3>
    <form method="post" action="rsvp_action.php">
        <?= csrf_field() ?><input type="hidden" name="do" value="add"><input type="hidden" name="back" value="<?= e($back) ?>">
        <div class="field">
            <label for="add_prompt">Undangan</label>
            <select id="add_prompt" name="prompt_id" style="width:100%;" required>
                <?php foreach ($myPrompts as $mp): ?>
                    <option value="<?= (int)$mp['id'] ?>" <?= (int)$mp['id'] === $f['prompt_id'] ? 'selected' : '' ?>><?= e($mp['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field"><label for="add_name">Nama tamu</label><input type="text" id="add_name" name="name" maxlength="100" required></div>
        <div class="row">
            <div class="field"><label for="add_status">Status</label>
                <select id="add_status" name="attending" style="width:100%;">
                    <?php foreach ($statuses as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
                </select></div>
            <div class="field"><label for="add_guests">Jumlah tamu</label><input type="number" id="add_guests" name="guests" min="1" max="50" value="1" required></div>
        </div>
        <div class="field"><label for="add_message">Pesan (opsional)</label><textarea id="add_message" name="message" maxlength="1000" rows="3"></textarea></div>
        <div class="form-actions"><button class="btn-primary" type="submit">Simpan</button><button class="btn" type="button" data-close>Batal</button></div>
    </form>
</dialog>

<!-- Dialog ubah -->
<dialog id="editDlg">
    <h3>Ubah RSVP</h3>
    <form method="post" action="rsvp_action.php">
        <?= csrf_field() ?><input type="hidden" name="do" value="edit"><input type="hidden" name="back" value="<?= e($back) ?>">
        <input type="hidden" name="id" id="edit_id">
        <div class="field"><label for="edit_name">Nama tamu</label><input type="text" id="edit_name" name="name" maxlength="100" required></div>
        <div class="row">
            <div class="field"><label for="edit_status">Status</label>
                <select id="edit_status" name="attending" style="width:100%;">
                    <?php foreach ($statuses as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
                </select></div>
            <div class="field"><label for="edit_guests">Jumlah tamu</label><input type="number" id="edit_guests" name="guests" min="1" max="50" required></div>
        </div>
        <div class="field"><label for="edit_message">Pesan</label><textarea id="edit_message" name="message" maxlength="1000" rows="3"></textarea></div>
        <div class="form-actions"><button class="btn-primary" type="submit">Simpan perubahan</button><button class="btn" type="button" data-close>Batal</button></div>
    </form>
</dialog>

<script>
(function () {
    var addDlg = document.getElementById('addDlg'), editDlg = document.getElementById('editDlg');
    var addBtn = document.getElementById('addBtn');
    if (addBtn) addBtn.addEventListener('click', function () { addDlg.showModal(); document.getElementById('add_name').focus(); });
    document.querySelectorAll('[data-close]').forEach(function (b) {
        b.addEventListener('click', function () { b.closest('dialog').close(); });
    });
    [addDlg, editDlg].forEach(function (d) {
        d.addEventListener('click', function (ev) { if (ev.target === d) d.close(); });   // klik di luar kotak menutup
    });
    document.querySelectorAll('.editBtn').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('edit_id').value      = b.dataset.id;
            document.getElementById('edit_name').value    = b.dataset.name;
            document.getElementById('edit_status').value  = b.dataset.status;
            document.getElementById('edit_guests').value  = b.dataset.guests;
            document.getElementById('edit_message').value = b.dataset.message;
            editDlg.showModal();
        });
    });
    var all = document.getElementById('checkAll'), rows = document.querySelectorAll('.rowcheck');
    var cnt = document.getElementById('selCount'), bulk = document.getElementById('bulkBtn');
    function refresh() {
        var n = document.querySelectorAll('.rowcheck:checked').length;
        if (cnt) cnt.textContent = n ? n + ' dipilih' : '';
        if (all) all.checked = rows.length > 0 && n === rows.length;
    }
    if (all) all.addEventListener('change', function () { rows.forEach(function (c) { c.checked = all.checked; }); refresh(); });
    rows.forEach(function (c) { c.addEventListener('change', refresh); });
    if (bulk) bulk.addEventListener('click', function (ev) {
        var n = document.querySelectorAll('.rowcheck:checked').length;
        if (n === 0) { ev.preventDefault(); alert('Pilih minimal satu RSVP dulu.'); return; }
        if (!confirm('Hapus ' + n + ' RSVP terpilih? Tindakan ini tidak bisa dibatalkan.')) ev.preventDefault();
    });
})();
</script>
<?php endif; ?>
<?php app_footer();