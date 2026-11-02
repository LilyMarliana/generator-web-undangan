<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/storage.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

header('Content-Type: application/json');

$imageExts = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
$audioExts = ['mp3' => 'audio/mpeg'];

$errors = [];

/**
 * Validasi satu file upload lalu simpan lewat lib/storage.php (lokal / cloud).
 * Mengembalikan URL file, atau '' kalau tidak ada file / gagal (alasannya masuk $errors).
 */
function saveUpload($field, $allowed, $maxBytes, &$errors) {
    if (empty($_FILES[$field])) {
        return '';
    }
    $file = $_FILES[$field];

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
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

    // Untuk gambar: pastikan isinya benar-benar gambar (bukan file lain yang hanya diganti ekstensinya)
    if (strpos($allowed[$ext], 'image/') === 0) {
        $info = @getimagesize($file['tmp_name']);
        if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png'], true)) {
            $errors[$field] = 'File bukan gambar JPG/PNG yang valid';
            return '';
        }
    }

    $url = storage_put($file['tmp_name'], $ext);
    if ($url === null) {
        $errors[$field] = 'Gagal menyimpan file (cek permission folder assets atau pengaturan storage)';
        return '';
    }
    return $url;
}

$result = [
    'heroImage'  => saveUpload('heroImageUpload', $imageExts, 2 * 1024 * 1024, $errors),
    'groomPhoto' => saveUpload('groomPhotoUpload', $imageExts, 2 * 1024 * 1024, $errors),
    'bridePhoto' => saveUpload('bridePhotoUpload', $imageExts, 2 * 1024 * 1024, $errors),
    'gallery' => [],
    'music' => saveUpload('musicUpload', $audioExts, 5 * 1024 * 1024, $errors),
];
for ($i = 1; $i <= 6; $i++) {
    $result['gallery'][] = saveUpload('galleryUpload' . $i, $imageExts, 2 * 1024 * 1024, $errors);
}

$result['errors'] = $errors;

echo json_encode($result);
exit;