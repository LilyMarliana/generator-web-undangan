<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/storage.php';
require_user();
require_post_csrf(url_user('undangan.php'));

$db = getDB();
$id = (int)($_POST['id'] ?? 0);
$p  = owned_prompt($id);

if (!$p) {
    msg_add('error', 'Undangan tidak ditemukan atau bukan milikmu.');
    redirect(url_user('undangan.php'));
}

/* Catat dulu file upload yang disebut di prompt ini (hanya yang polanya cocok dengan hasil upload.php). */
$refs = storage_refs_in($p['prompt']);

/* Hapus undangan (RSVP dan ucapan ikut terhapus lewat ON DELETE CASCADE). */
$db->prepare("DELETE FROM prompts WHERE id=? AND user_id=?")
   ->execute([$id, (int)$_SESSION['user']['id']]);

/* Hapus file, tetapi HANYA yang tidak dipakai undangan lain. Ini mencegah file milik pengguna lain
   ikut terhapus walau namanya sengaja ditulis di teks prompt. */
$used = $db->prepare("SELECT COUNT(*) FROM prompts WHERE prompt LIKE ?");
foreach ($refs as $ref) {
    $used->execute(['%' . $ref . '%']);
    if ((int)$used->fetchColumn() === 0) {
        storage_delete($ref);
    }
}

msg_add('success', 'Undangan berhasil dihapus.');
redirect(url_user('undangan.php'));