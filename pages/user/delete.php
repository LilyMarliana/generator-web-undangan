<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

if (current_user()['role'] !== 'user') {
    redirect(BASE_URL . '/pages/user/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Token tidak valid.';
    } else {
        $db = getDB();
        $id = (int)($_POST['id'] ?? 0);

        // Fetch prompt dulu untuk hapus assets-nya
        $fetch = $db->prepare("SELECT prompt FROM prompts WHERE id=? AND user_id=?");
        $fetch->execute([$id, $_SESSION['user']['id']]);
        $row = $fetch->fetch();

        if ($row) {
            // Extract asset paths dari teks prompt
            preg_match_all('~/assets/[a-z0-9]+\.[a-z]+~i', $row['prompt'], $matches);
            if (!empty($matches[0])) {
                $assetDir = __DIR__ . '/../../';
                foreach ($matches[0] as $url) {
                    $file = $assetDir . ltrim($url, '/');
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }

            $del = $db->prepare("DELETE FROM prompts WHERE id=? AND user_id=?");
            $del->execute([$id, $_SESSION['user']['id']]);
            flash('delete_success', 'Prompt berhasil dihapus.');
        } else {
            $error = 'Prompt tidak ditemukan atau bukan milikmu.';
        }
    }
}

if ($error) {
    flash('error', $error);
}
redirect(BASE_URL . '/pages/user/dashboard.php');