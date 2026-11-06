<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/* Batas masukan dari website undangan (API key tertanam di website, jadi semua masukan harus dibatasi) */
const MAX_NAME       = 100;
const MAX_MESSAGE    = 1000;
const MAX_GUESTS     = 50;
const MAX_PER_MINUTE = 30;   // maksimal kiriman baru per undangan per menit (mencegah spam membanjiri database)

function respond($code, array $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function input_json() {
    $raw = file_get_contents('php://input');
    if ($raw !== false && $raw !== '') {
        $d = json_decode($raw, true);
        return is_array($d) ? $d : [];
    }
    return $_POST;
}

/** Ambil isian teks; nilai bukan teks dianggap kosong. */
function text_field(array $d, $key) {
    return (isset($d[$key]) && is_string($d[$key])) ? trim($d[$key]) : '';
}

$action = is_string($_GET['action'] ?? null) ? $_GET['action'] : '';
$apiKey = is_string($_GET['api_key'] ?? null) ? trim($_GET['api_key']) : '';

$allowedActions = ['info', 'rsvp', 'wish'];
if (!in_array($action, $allowedActions, true)) {
    respond(404, ['success' => false, 'error' => 'Action tidak valid']);
}

if ($apiKey === '') {
    respond(401, ['success' => false, 'error' => 'api_key wajib diisi']);
}

$db = getDB();

try {
    // Cari prompt by api_key
    $stmt = $db->prepare("SELECT id, groom_name, bride_name FROM prompts WHERE api_key=? LIMIT 1");
    $stmt->execute([$apiKey]);
    $prompt = $stmt->fetch();

    if (!$prompt) {
        respond(401, ['success' => false, 'error' => 'api_key tidak valid']);
    }

    /** Sudah terlalu banyak kiriman dalam 1 menit terakhir untuk undangan ini? ($table dari daftar tetap) */
    $tooMany = function ($table) use ($db, $prompt) {
        $st = $db->prepare("SELECT COUNT(*) FROM $table WHERE prompt_id=? AND created_at >= (NOW() - INTERVAL 1 MINUTE)");
        $st->execute([$prompt['id']]);
        return (int)$st->fetchColumn() >= MAX_PER_MINUTE;
    };

    switch ($action) {
        case 'info':
            respond(200, [
                'success' => true,
                'groom'   => $prompt['groom_name'],
                'bride'   => $prompt['bride_name'],
            ]);

        case 'rsvp':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $rows = $db->prepare("SELECT name, attending, guests, message, created_at FROM rsvps WHERE prompt_id=? ORDER BY id ASC");
                $rows->execute([$prompt['id']]);
                respond(200, ['success' => true, 'data' => $rows->fetchAll()]);
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respond(405, ['success' => false, 'error' => 'Method tidak diizinkan']);
            }

            $d       = input_json();
            $name    = text_field($d, 'name');
            $attend  = (isset($d['attending']) && is_string($d['attending']) && in_array($d['attending'], ['hadir', 'tidak_hadir', 'belum_pasti'], true)) ? $d['attending'] : 'belum_pasti';
            $guests  = min(MAX_GUESTS, max(1, (int)($d['guests'] ?? 1)));
            $message = text_field($d, 'message');
            if ($name === '') {
                respond(422, ['success' => false, 'error' => 'name wajib diisi']);
            }
            if (mb_strlen($name) > MAX_NAME) {
                respond(422, ['success' => false, 'error' => 'name maksimal ' . MAX_NAME . ' karakter']);
            }
            if (mb_strlen($message) > MAX_MESSAGE) {
                respond(422, ['success' => false, 'error' => 'message maksimal ' . MAX_MESSAGE . ' karakter']);
            }

            // Kiriman ganda (mis. tombol diklik dua kali) dalam 1 menit tidak disimpan dua kali
            $dup = $db->prepare("SELECT id FROM rsvps WHERE prompt_id=? AND name=? AND attending=? AND guests=? AND IFNULL(message,'')=? AND created_at >= (NOW() - INTERVAL 1 MINUTE) LIMIT 1");
            $dup->execute([$prompt['id'], $name, $attend, $guests, $message]);
            if ($existing = $dup->fetchColumn()) {
                respond(200, ['success' => true, 'id' => $existing, 'duplicate' => true]);
            }
            if ($tooMany('rsvps')) {
                respond(429, ['success' => false, 'error' => 'Terlalu banyak permintaan, coba lagi sebentar lagi']);
            }

            $ins = $db->prepare("INSERT INTO rsvps (prompt_id, name, attending, guests, message) VALUES (?,?,?,?,?)");
            $ins->execute([$prompt['id'], $name, $attend, $guests, $message]);
            respond(200, ['success' => true, 'id' => $db->lastInsertId()]);

        case 'wish':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                // Ucapan yang disembunyikan pemilik undangan (is_hidden=1) tidak ikut tampil
                $rows = $db->prepare("SELECT id, prompt_id, name, message, created_at FROM wishes WHERE prompt_id=? AND is_hidden=0 ORDER BY id DESC");
                $rows->execute([$prompt['id']]);
                respond(200, ['success' => true, 'data' => $rows->fetchAll()]);
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                respond(405, ['success' => false, 'error' => 'Method tidak diizinkan']);
            }

            $d       = input_json();
            $name    = text_field($d, 'name');
            $message = text_field($d, 'message');
            if ($name === '' || $message === '') {
                respond(422, ['success' => false, 'error' => 'name dan message wajib diisi']);
            }
            if (mb_strlen($name) > MAX_NAME) {
                respond(422, ['success' => false, 'error' => 'name maksimal ' . MAX_NAME . ' karakter']);
            }
            if (mb_strlen($message) > MAX_MESSAGE) {
                respond(422, ['success' => false, 'error' => 'message maksimal ' . MAX_MESSAGE . ' karakter']);
            }

            $dup = $db->prepare("SELECT id FROM wishes WHERE prompt_id=? AND name=? AND message=? AND created_at >= (NOW() - INTERVAL 1 MINUTE) LIMIT 1");
            $dup->execute([$prompt['id'], $name, $message]);
            if ($existing = $dup->fetchColumn()) {
                respond(200, ['success' => true, 'id' => $existing, 'duplicate' => true]);
            }
            if ($tooMany('wishes')) {
                respond(429, ['success' => false, 'error' => 'Terlalu banyak permintaan, coba lagi sebentar lagi']);
            }

            $ins = $db->prepare("INSERT INTO wishes (prompt_id, name, message) VALUES (?,?,?)");
            $ins->execute([$prompt['id'], $name, $message]);
            respond(200, ['success' => true, 'id' => $db->lastInsertId()]);
    }
} catch (PDOException $e) {
    // Jangan bocorkan detail database ke pengunjung
    error_log('api.php: ' . $e->getMessage());
    respond(500, ['success' => false, 'error' => 'Terjadi kesalahan pada server']);
}