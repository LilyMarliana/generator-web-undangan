<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/rsvp.php';
require_admin();

$back = safe_back($_POST['back'] ?? '', 'feedback.php');
require_post_csrf(url_admin($back));

$db     = getDB();
$id     = (is_string($_POST['id'] ?? null) && ctype_digit($_POST['id'])) ? (int)$_POST['id'] : 0;
$status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
$note   = is_string($_POST['admin_note'] ?? null) ? trim($_POST['admin_note']) : '';

if (!in_array($status, ['baru', 'dibaca', 'ditindaklanjuti'], true)) {
    msg_add('error', 'Status tidak valid.');
    redirect(url_admin($back));
}
if (mb_strlen($note) > 255) {
    msg_add('error', 'Catatan maksimal 255 karakter.');
    redirect(url_admin($back));
}

$chk = $db->prepare("SELECT COUNT(*) FROM feedback WHERE id = ?");
$chk->execute([$id]);
if (!(int)$chk->fetchColumn()) {
    msg_add('error', 'Masukan tidak ditemukan.');
    redirect(url_admin($back));
}

$db->prepare("UPDATE feedback SET status = ?, admin_note = ? WHERE id = ?")
   ->execute([$status, $note !== '' ? $note : null, $id]);
msg_add('success', 'Masukan diperbarui.');
redirect(url_admin($back));