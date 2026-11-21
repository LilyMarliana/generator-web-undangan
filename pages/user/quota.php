<?php
require_once __DIR__ . '/../../lib/layout.php';
require_login();

header('Content-Type: application/json');
header('Cache-Control: no-store');

$quota = daily_quota();
if ($quota === null) {
    echo json_encode(['unlimited' => true, 'limit' => null, 'used' => 0, 'remaining' => null]);
    exit;
}

$st = getDB()->prepare("SELECT `count` FROM quota_logs WHERE user_id = ? AND `date` = ?");
$st->execute([(int)$_SESSION['user']['id'], date('Y-m-d')]);
$used = (int)($st->fetchColumn() ?: 0);

echo json_encode([
    'unlimited' => false,
    'limit'     => $quota,
    'used'      => $used,
    'remaining' => max(0, $quota - $used),
]);
