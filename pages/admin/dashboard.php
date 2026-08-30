<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/auth.php';

require_admin();

$db = getDB();
$totalUsers  = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPrompts = $db->query("SELECT COUNT(*) FROM prompts")->fetchColumn();
$totalPromptsThisMonth = $db->query(
    "SELECT COUNT(*) FROM prompts WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
)->fetchColumn();

$recentPrompt = $db->query(
    "SELECT p.id, p.title, p.created_at, u.name FROM prompts p JOIN users u ON u.id=p.user_id ORDER BY p.created_at DESC LIMIT 5"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Wedding Website Generator</title>
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Dashboard Admin </h1>
            <p>Selamat datang, <?= e($_SESSION['user']['name']) ?></p>
        </div>
        <div class="content">
            <div class="dash-nav">
                <a href="dashboard.php" class="active">Ringkasan</a>
                <a href="users.php">Data Pengguna</a>
                <a href="prompts.php">Data Penggunaan Prompt</a>
                <a href="<?= BASE_URL ?>/index.php?logout=1">Logout</a>
            </div>

            <div class="dash-stats">
                <div class="stat-card">
                    <h3><?= $totalUsers ?></h3>
                    <p>Total Pengguna</p>
                </div>
                <div class="stat-card">
                    <h3><?= $totalPrompts ?></h3>
                    <p>Total Prompt</p>
                </div>
                <div class="stat-card">
                    <h3><?= $totalPromptsThisMonth ?></h3>
                    <p>Prompt Bulan Ini</p>
                </div>
            </div>

            <h3 style="margin-bottom: 15px; color:#333;">Prompt Terbaru</h3>
            <?php if (!$recentPrompt): ?>
                <div class="empty-state"><p>Belum ada prompt.</p></div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Judul</th><th>Pengguna</th><th>Tanggal</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPrompt as $r): ?>
                            <tr>
                                <td><?= e($r['title']) ?></td>
                                <td><?= e($r['name']) ?></td>
                                <td><?= e(date('d M Y H:i', strtotime($r['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>