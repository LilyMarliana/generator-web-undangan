<?php
require_once __DIR__ . '/../../lib/layout.php';
require_user();
require_post_csrf(url_user('undangan.php'));

$id = (int)($_POST['id'] ?? 0);
$p  = owned_prompt($id);
if (!$p) {
    msg_add('error', 'Undangan tidak ditemukan atau bukan milikmu.');
    redirect(url_user('undangan.php'));
}

$newKey = bin2hex(random_bytes(16));
$text   = str_replace($p['api_key'], $newKey, $p['prompt']); // key lama di dalam prompt ikut diganti

getDB()->prepare("UPDATE prompts SET api_key=?, prompt=? WHERE id=? AND user_id=?")
       ->execute([$newKey, $text, $id, (int)$_SESSION['user']['id']]);

if (strpos($p['prompt'], $p['api_key']) === false) {
    msg_add('info', 'API key baru sudah dibuat, tetapi key lama tidak ditemukan di dalam prompt. Masukkan key baru ke prompt secara manual.');
} else {
    msg_add('success', 'API key baru berhasil dibuat dan prompt sudah diperbarui. Key lama tidak berlaku lagi.');
}
redirect(url_user('detail.php?id=' . $id));