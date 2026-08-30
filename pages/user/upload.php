<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

header('Content-Type: application/json');

$dir = __DIR__ . '/../../assets/';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$imageExts = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
$audioExts = ['mp3' => 'audio/mpeg'];

$errors = [];

function saveUpload($field, $dir, $allowed, $base, $maxBytes, &$errors) {
    if (empty($_FILES[$field])) {
        return '';
    }
    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE => 'File melebihi batas server (upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'File melebihi batas form (MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ada di server',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
        ];
        $errors[$field] = $map[$file['error']] ?? ('Upload error code: ' . $file['error']);
        return '';
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        $errors[$field] = "Format .$ext tidak didukung (harus: " . implode(', ', array_keys($allowed)) . ")";
        return '';
    }
    if ($file['size'] > $maxBytes) {
        $errors[$field] = "Ukuran file " . round($file['size'] / 1024 / 1024, 2) . "MB melebihi batas " . round($maxBytes / 1024 / 1024, 2) . "MB";
        return '';
    }

    // more_entropy=true supaya nama file tidak bertabrakan saat beberapa file
    // diupload nyaris bersamaan (ini penyebab bug foto saling menimpa sebelumnya)
    $name = uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        $errors[$field] = 'Gagal memindahkan file ke folder assets (cek permission folder)';
        return '';
    }
    return rtrim($base, '/') . '/assets/' . $name;
}

$result = [
    'heroImage'  => saveUpload('heroImageUpload', $dir, $imageExts, BASE_URL, 2 * 1024 * 1024, $errors),
    'groomPhoto' => saveUpload('groomPhotoUpload', $dir, $imageExts, BASE_URL, 2 * 1024 * 1024, $errors),
    'bridePhoto' => saveUpload('bridePhotoUpload', $dir, $imageExts, BASE_URL, 2 * 1024 * 1024, $errors),
    'gallery' => [],
    'music' => saveUpload('musicUpload', $dir, $audioExts, BASE_URL, 5 * 1024 * 1024, $errors),
];
for ($i = 1; $i <= 6; $i++) {
    $result['gallery'][] = saveUpload('galleryUpload' . $i, $dir, $imageExts, BASE_URL, 2 * 1024 * 1024, $errors);
}

$result['errors'] = $errors;

echo json_encode($result);
exit;