<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/rsvp.php';
require_user();

$back = safe_back($_POST['back'] ?? '', 'wishes.php');
require_post_csrf(url_user($back));

$db  = getDB();
$uid = (int)$_SESSION['user']['id'];
$do  = is_string($_POST['do'] ?? null) ? $_POST['do'] : '';
$id  = (is_string($_POST['id'] ?? null) && ctype_digit($_POST['id'])) ? (int)$_POST['id'] : 0;

/* Pastikan ucapan ini milik undangan user yang login */
$own = $db->prepare("SELECT w.id FROM wishes w JOIN prompts p ON p.id=w.prompt_id WHERE w.id=? AND p.user_id=?");
$own->execute([$id, $uid]);
if (!$own->fetch()) {
    msg_add('error', 'Ucapan tidak ditemukan atau bukan milikmu.');
    redirect(url_user($back));
}

switch ($do) {
    case 'hide':
    case 'show':
        $db->prepare("UPDATE wishes SET is_hidden=? WHERE id=?")->execute([$do === 'hide' ? 1 : 0, $id]);
        msg_add('success', $do === 'hide'
            ? 'Ucapan disembunyikan dan tidak tampil lagi di website undangan.'
            : 'Ucapan ditampilkan kembali di website undangan.');
        break;

    case 'delete':
        $db->prepare("DELETE FROM wishes WHERE id=?")->execute([$id]);
        msg_add('success', 'Ucapan berhasil dihapus.');
        break;

    default:
        msg_add('error', 'Aksi tidak dikenal.');
}

redirect(url_user($back));