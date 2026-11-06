<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/rsvp.php';
require_user();

$db  = getDB();
$uid = (int)$_SESSION['user']['id'];

$st = $db->prepare("SELECT id, title FROM prompts WHERE user_id=?");
$st->execute([$uid]);
$mine = array_column($st->fetchAll(), 'title', 'id');

$f = rsvp_filters_from_get(array_map('intval', array_keys($mine)));
[$where, $params] = rsvp_where($uid, $f);

$st = $db->prepare(
    "SELECT r.name, r.attending, r.guests, r.message, r.created_at, p.title
     FROM rsvps r JOIN prompts p ON p.id=r.prompt_id
     WHERE $where ORDER BY r.id ASC"
);
$st->execute($params);

/* Format: koma (umum) atau titik koma (Excel dengan pengaturan regional Indonesia) */
$delim = (($_GET['fmt'] ?? '') === 'excel') ? ';' : ',';

/* Cegah "CSV injection": sel yang diawali = + - @ dianggap rumus oleh Excel */
function csv_safe($v) {
    $v = (string)$v;
    return ($v !== '' && preg_match('/^[=+\-@\t\r]/', $v)) ? "'" . $v : $v;
}

$slug = $f['prompt_id'] ? strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $mine[$f['prompt_id']]), '-')) : 'semua-undangan';
$slug = substr($slug !== '' ? $slug : 'undangan', 0, 40);
$file = 'rsvp-' . $slug . '-' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('X-Content-Type-Options: nosniff');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca huruf UTF-8 dengan benar
fputcsv($out, ['Nama', 'Status', 'Jumlah Tamu', 'Pesan', 'Undangan', 'Waktu RSVP'], $delim, '"', '');
while ($r = $st->fetch()) {
    fputcsv($out, [
        csv_safe($r['name']),
        attending_label($r['attending']),
        (int)$r['guests'],
        csv_safe($r['message']),
        csv_safe($r['title']),
        $r['created_at'],
    ], $delim, '"', '');
}
fclose($out);
exit;