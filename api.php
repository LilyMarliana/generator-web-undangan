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

$db     = getDB();
$action = $_GET['action'] ?? '';
$apiKey = $_GET['api_key'] ?? '';

$allowedActions = ['info', 'rsvp', 'wish'];
if (!in_array($action, $allowedActions, true)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Action tidak valid']);
    exit;
}

if ($apiKey === '') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'api_key wajib diisi']);
    exit;
}

// Cari prompt by api_key
$stmt = $db->prepare("SELECT id, groom_name, bride_name FROM prompts WHERE api_key=? LIMIT 1");
$stmt->execute([$apiKey]);
$prompt = $stmt->fetch();

if (!$prompt) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'api_key tidak valid']);
    exit;
}

function input_json() {
    $raw = file_get_contents('php://input');
    return $raw ? json_decode($raw, true) : $_POST;
}

switch ($action) {
    case 'info':
        echo json_encode([
            'success' => true,
            'groom'   => $prompt['groom_name'],
            'bride'   => $prompt['bride_name'],
        ]);
        exit;

    case 'rsvp':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $rows = $db->prepare("SELECT name, attending, guests, message, created_at FROM rsvps WHERE prompt_id=? ORDER BY id ASC");
            $rows->execute([$prompt['id']]);
            echo json_encode(['success' => true, 'data' => $rows->fetchAll()]);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $d   = input_json();
            $name    = trim($d['name'] ?? '');
            $attend  = in_array($d['attending'] ?? '', ['hadir', 'tidak_hadir', 'belum_pasti']) ? $d['attending'] : 'belum_pasti';
            $guests  = max(1, (int)($d['guests'] ?? 1));
            $message = trim($d['message'] ?? '');
            if ($name === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'name wajib diisi']);
                exit;
            }
            $ins = $db->prepare("INSERT INTO rsvps (prompt_id, name, attending, guests, message) VALUES (?,?,?,?,?)");
            $ins->execute([$prompt['id'], $name, $attend, $guests, $message]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method tidak diizinkan']);
        }
        exit;

    case 'wish':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $rows = $db->prepare("SELECT * FROM wishes WHERE prompt_id=? ORDER BY id DESC");
            $rows->execute([$prompt['id']]);
            echo json_encode(['success' => true, 'data' => $rows->fetchAll()]);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $d       = input_json();
            $name    = trim($d['name'] ?? '');
            $message = trim($d['message'] ?? '');
            if ($name === '' || $message === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => 'name dan message wajib diisi']);
                exit;
            }
            $ins = $db->prepare("INSERT INTO wishes (prompt_id, name, message) VALUES (?,?,?)");
            $ins->execute([$prompt['id'], $name, $message]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
        } else {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method tidak diizinkan']);
        }
        exit;
}