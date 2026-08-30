<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

if (current_user()['role'] !== 'user') {
    redirect(BASE_URL . '/pages/user/dashboard.php');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM prompts WHERE user_id=? ORDER BY id DESC");
$stmt->execute([$_SESSION['user']['id']]);
$prompts = $stmt->fetchAll();

$q = $db->prepare("SELECT count FROM quota_logs WHERE user_id=? AND date=?");
$q->execute([$_SESSION['user']['id'], date('Y-m-d')]);
$usedToday = (int)($q->fetch()['count'] ?? 0);
$quota = 5;
$remaining = max(0, $quota - $usedToday);

$session_flash = [];
foreach (['save_success', 'delete_success', 'error'] as $k) {
    if (flash($k)) $session_flash[] = flash($k);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - Wedding Website Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="header-eyebrow">Wedding Website Generator</span>
            <h1>Dashboard Pengguna</h1>
            <p>Selamat datang, <?= e($_SESSION['user']['name']) ?></p>
        </div>
        <div class="content">
            <div class="dash-nav">
                <a href="generator.php">Generator Baru</a>
                <a href="dashboard.php" class="active">Prompts Saya</a>
                <a href="<?= BASE_URL ?>/index.php?logout=1">Logout</a>
            </div>

            <?php if ($session_flash): ?>
                <?php foreach ($session_flash as $msg): ?>
                    <div class="success-msg"><?= e($msg) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="dash-header">
                <h2>Prompt Saya (<?= count($prompts) ?>)</h2>
                <span class="quota-badge <?= $remaining > 0 ? 'ok' : 'empty' ?>">
                    Sisa kuota hari ini: <?= $remaining ?>/<?= $quota ?>
                </span>
            </div>

            <?php if (!$prompts): ?>
                <div class="empty-state">
                    <p>Belum ada prompt. Klik "Generator Baru" untuk membuat yang pertama.</p>
                </div>
            <?php else: ?>
                <?php foreach ($prompts as $p): ?>
                    <div class="prompt-card">
                        <h4><?= e($p['title']) ?></h4>
                        <?php if ($p['api_key']): ?>
                            <div class="api-key-box">
                                <strong>🔑 API Key (untuk RSVP & Ucapan)</strong>
                                <code><?= e($p['api_key']) ?></code>
                                <button class="btn-success" data-key="<?= e($p['api_key']) ?>" onclick="navigator.clipboard.writeText(this.dataset.key).then(()=>{this.textContent='Tersalin!';setTimeout(()=>{this.textContent='Copy API Key'},1500)})">Copy API Key</button>
                                <p>Pakai API key ini di form RSVP & ucapan website.</p>
                            </div>
                        <?php endif; ?>
                        <pre><?= e($p['prompt']) ?></pre>
                        <div class="meta">
                            Dibuat: <?= e(date('d M Y H:i', strtotime($p['created_at']))) ?>
                            | Diupdate: <?= e(date('d M Y H:i', strtotime($p['updated_at']))) ?>
                        </div>
                        <div class="actions">
                            <button class="btn-success" onclick="navigator.clipboard.writeText(this.dataset.text).then(()=>{this.textContent='Tersalin!';setTimeout(()=>{this.textContent='Copy'},1500)})" data-text="<?= e($p['prompt']) ?>">Copy Prompt</button>
                            <a href="edit.php?id=<?= (int)$p['id'] ?>" class="btn-secondary">Edit</a>
                            <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Hapus prompt ini?')">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="btn-danger">Hapus</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>