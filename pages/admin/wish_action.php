<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/rsvp.php';
require_admin();

$back = safe_back($_POST['back'] ?? '', 'wishes.php');
require_post_csrf(url_admin($back));

$db = getDB();
$do = is_string($_POST['do'] ?? null) ? $_POST['do'] : '';
$id = (is_string($_POST['id'] ?? null) && ctype_digit($_POST['id'])) ? (int)$_POST['id'] : 0;

$st = $db->prepare("SELECT id FROM wishes WHERE id = ?");
$st->execute([$id]);
if (!$st->fetch()) {
    msg_add('error', 'Ucapan tidak ditemukan (mungkin sudah dihapus).');
    redirect(url_admin($back));
}

switch ($do) {
    case 'hide':
    case 'show':
        $db->prepare("UPDATE wishes SET is_hidden = ? WHERE id = ?")->execute([$do === 'hide' ? 1 : 0, $id]);
        msg_add('success', $do === 'hide' ? 'Ucapan disembunyikan dari website undangan.' : 'Ucapan ditampilkan kembali.');
        break;
    case 'delete':
        $db->prepare("DELETE FROM wishes WHERE id = ?")->execute([$id]);
        msg_add('success', 'Ucapan dihapus.');
        break;
    default:
        msg_add('error', 'Aksi tidak dikenal.');
}
redirect(url_admin($back));