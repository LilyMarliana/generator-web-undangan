<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

if (current_user()['role'] !== 'user') {
    redirect(BASE_URL . '/pages/user/dashboard.php');
}

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM prompts WHERE id=? AND user_id=?");
$stmt->execute([$id, $_SESSION['user']['id']]);
$prompt = $stmt->fetch();

if (!$prompt) {
    redirect(BASE_URL . '/pages/user/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Token tidak valid.';
    } else {
        $title  = trim($_POST['title'] ?? '');
        $text   = trim($_POST['prompt'] ?? '');
        if ($title === '' || $text === '') {
            $error = 'Judul dan isi prompt tidak boleh kosong.';
        } else {
            $upd = $db->prepare("UPDATE prompts SET title=?, prompt=? WHERE id=? AND user_id=?");
            $upd->execute([$title, $text, $id, $_SESSION['user']['id']]);
            flash('save_success', 'Prompt berhasil diperbarui.');
            redirect(BASE_URL . '/pages/user/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Prompt - Wedding Website Generator</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Edit Prompt </h1>
            <p>Ubah prompt undangan pernikahan</p>
        </div>
        <div class="content">
            <div class="dash-nav">
                <a href="dashboard.php">Dashboard</a>
            </div>
            <?php if ($error): ?><div class="error-msg"><?= e($error) ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label for="title">Judul:</label>
                    <input type="text" id="title" name="title" value="<?= e($prompt['title']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="prompt">Isi Prompt:</label>
                    <textarea id="prompt" name="prompt" style="min-height: 400px;" required><?= e($prompt['prompt']) ?></textarea>
                </div>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="dashboard.php" class="btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</body>
</html>