<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/auth.php';

require_admin();

$db = getDB();
$users = $db->query(
    "SELECT u.*, (SELECT COUNT(*) FROM prompts WHERE user_id=u.id) AS prompt_count
     FROM users u WHERE u.role='user' ORDER BY u.created_at DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengguna - Admin</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Data Pengguna </h1>
            <p>Seluruh pengguna terdaftar</p>
        </div>
        <div class="content">
            <div class="dash-nav">
                <a href="dashboard.php">Ringkasan</a>
                <a href="users.php" class="active">Data Pengguna</a>
                <a href="prompts.php">Data Penggunaan Prompt</a>
                <a href="<?= BASE_URL ?>/index.php?logout=1">Logout</a>
            </div>

            <?php if (!$users): ?>
                <div class="empty-state"><p>Belum ada pengguna.</p></div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Nama</th><th>Email</th><th>Jumlah Prompt</th><th>Tanggal Daftar</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= e($u['name']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><?= (int)$u['prompt_count'] ?></td>
                                <td><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>