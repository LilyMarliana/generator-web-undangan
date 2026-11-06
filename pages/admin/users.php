<?php
require_once __DIR__ . '/../../lib/layout.php';
require_admin();

$db = getDB();
$me = (int)$_SESSION['user']['id'];

$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$where  = '1=1';
$params = [];
if ($q !== '') {
    $like   = '%' . addcslashes($q, '%_\\') . '%';
    $where .= " AND (u.name LIKE ? OR u.email LIKE ?)";
    array_push($params, $like, $like);
}

$st = $db->prepare("SELECT COUNT(*) FROM users u WHERE $where");
$st->execute($params);
$total = (int)$st->fetchColumn();

[$page, $per] = page_params(10);
$page   = min($page, max(1, (int)ceil($total / $per)));
$offset = ($page - 1) * $per;

$st = $db->prepare(
    "SELECT u.id, u.name, u.email, u.role, u.is_active, u.created_at,
            (SELECT COUNT(*) FROM prompts WHERE user_id = u.id) AS prompt_count
     FROM users u WHERE $where ORDER BY u.id DESC LIMIT $per OFFSET $offset"
);
$st->execute($params);
$users = $st->fetchAll();

app_header('Pengguna', 'users', [
    'subtitle' => 'Kelola peran dan status akun pengguna (' . $total . ' total).',
]);
?>
<form class="toolbar" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama atau email" maxlength="100">
    <button class="btn-outline" type="submit">Cari</button>
    <?php if ($q !== ''): ?><a class="btn-outline" href="users.php">Reset</a><?php endif; ?>
</form>

<?php if (!$users): ?>
    <div class="card empty"><p>Tidak ada pengguna ditemukan.</p></div>
<?php else: ?>
    <div class="card">
        <div class="table-wrap">
            <table class="tbl">
                <thead>
                    <tr><th>Nama</th><th>Peran</th><th>Status</th><th class="num">Undangan</th><th>Daftar</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u):
                    $isMe    = ((int)$u['id'] === $me);
                    $active  = (int)$u['is_active'] === 1;
                    $isAdmin = $u['role'] === 'admin';
                ?>
                    <tr>
                        <td class="cell-title"><?= e($u['name']) ?><?= $isMe ? ' <span class="muted">(kamu)</span>' : '' ?><br><small class="muted"><?= e($u['email']) ?></small></td>
                        <td><span class="badge <?= $isAdmin ? 'b-warn' : 'b-muted' ?>"><?= $isAdmin ? 'Admin' : 'User' ?></span></td>
                        <td><span class="badge <?= $active ? 'b-ok' : 'b-no' ?>"><?= $active ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td class="num"><?= (int)$u['prompt_count'] ?></td>
                        <td class="cell-sub nowrap"><?= e(fmt_date($u['created_at'], false)) ?></td>
                        <td>
                            <?php if ($isMe): ?>
                                <span class="muted">&mdash;</span>
                            <?php else: ?>
                                <div class="row-actions">
                                    <form method="post" action="user_action.php" class="inline"
                                          data-confirm="<?= $isAdmin ? 'Ubah peran pengguna ini menjadi User?' : 'Jadikan pengguna ini Admin? Dia akan punya akses penuh ke halaman admin.' ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="do" value="set_role">
                                        <input type="hidden" name="role" value="<?= $isAdmin ? 'user' : 'admin' ?>">
                                        <button class="btn btn-sm" type="submit"><?= $isAdmin ? 'Jadikan User' : 'Jadikan Admin' ?></button>
                                    </form>
                                    <form method="post" action="user_action.php" class="inline"
                                          data-confirm="<?= $active ? 'Nonaktifkan akun ini? Pengguna tidak bisa login sampai diaktifkan lagi.' : 'Aktifkan kembali akun ini?' ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="do" value="toggle_active">
                                        <button class="<?= $active ? 'btn-danger' : 'btn' ?> btn-sm" type="submit"><?= $active ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                                    </form>
                                </div>
                            <?php endif; ?>
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