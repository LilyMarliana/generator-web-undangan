<?php
require_once __DIR__ . '/../../lib/layout.php';
require_admin();

$back = url_admin('users.php');
require_post_csrf($back);

$db = getDB();
$me = (int)$_SESSION['user']['id'];
$do = is_string($_POST['do'] ?? null) ? $_POST['do'] : '';
$id = (is_string($_POST['id'] ?? null) && ctype_digit($_POST['id'])) ? (int)$_POST['id'] : 0;

if ($id === $me) {
    msg_add('error', 'Kamu tidak bisa mengubah peran atau status akunmu sendiri.');
    redirect($back);
}

$st = $db->prepare("SELECT id, name, role, is_active FROM users WHERE id = ?");
$st->execute([$id]);
$target = $st->fetch();
if (!$target) {
    msg_add('error', 'Pengguna tidak ditemukan.');
    redirect($back);
}

switch ($do) {
    case 'set_role':
        $role = is_string($_POST['role'] ?? null) ? $_POST['role'] : '';
        if (!in_array($role, ['user', 'admin'], true)) {
            msg_add('error', 'Peran tidak valid.');
            break;
        }
        $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $id]);
        msg_add('success', $target['name'] . ' sekarang berperan ' . ($role === 'admin' ? 'Admin.' : 'User.'));
        break;

    case 'toggle_active':
        $new = (int)$target['is_active'] === 1 ? 0 : 1;
        $db->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$new, $id]);
        msg_add('success', 'Akun ' . $target['name'] . ($new ? ' diaktifkan kembali.' : ' dinonaktifkan.'));
        break;

    default:
        msg_add('error', 'Aksi tidak dikenal.');
}
redirect($back);