<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/auth.php';

require_admin();

$db = getDB();
$prompts = $db->query(
    "SELECT p.*, u.name as username FROM prompts p JOIN users u ON u.id=p.user_id ORDER BY p.created_at DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penggunaan Prompt - Admin</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Data Penggunaan Prompt </h1>
            <p>Seluruh prompt yang dibuat pengguna</p>
        </div>
        <div class="content">
            <div class="dash-nav">
                <a href="dashboard.php">Ringkasan</a>
                <a href="users.php">Data Pengguna</a>
                <a href="prompts.php" class="active">Data Penggunaan Prompt</a>
                <a href="<?= BASE_URL ?>/index.php?logout=1">Logout</a>
            </div>

            <?php if (!$prompts): ?>
                <div class="empty-state"><p>Belum ada prompt.</p></div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Pengguna</th><th>Judul</th><th>Dibuat</th><th>Diupdate</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prompts as $p): ?>
                            <tr>
                                <td><?= e($p['username']) ?></td>
                                <td><?= e($p['title']) ?></td>
                                <td><?= e(date('d M Y H:i', strtotime($p['created_at']))) ?></td>
                                <td><?= e(date('d M Y H:i', strtotime($p['updated_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>