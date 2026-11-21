<?php
require_once '/etc/invitia/config.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/storage.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

header('Content-Type: application/json');

$imageExts = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
// Musik: format umum diterima, lalu semuanya dikonversi ke MP3 yang lebih kecil
$audioExts = ['mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'aac' => 'audio/aac'];

const MUSIC_MAX_INPUT_BYTES  = 20 * 1024 * 1024; // file asli dari pengguna
const MUSIC_MAX_OUTPUT_BYTES = 5 * 1024 * 1024;  // hasil setelah konversi
const MUSIC_MAX_SECONDS      = 300;              // durasi maksimal 5 menit
const MUSIC_BITRATE          = '96k';

$errors = [];

/*
 * Kalau total kiriman melebihi post_max_size, PHP mengosongkan $_FILES dan $_POST
 * tanpa pesan error. Tanpa pengecekan ini, upload tampak "sukses" padahal tidak ada file yang tersimpan.
 */
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if (empty($_FILES) && $contentLength > 0) {
    echo json_encode([
        'heroImage' => '', 'groomPhoto' => '', 'bridePhoto' => '', 'gallery' => [], 'music' => '',
        'errors' => ['upload' => 'Total ukuran semua file terlalu besar untuk server (' . round($contentLength / 1024 / 1024, 1) . 'MB). Kurangi ukuran atau jumlah file.'],
    ]);
    exit;
}

/**
 * Konversi audio apa pun ke MP3 stereo 96 kbps, maksimal 5 menit, tanpa metadata.
 * Hasilnya menimpa file sementara upload (isi $tmpPath diganti), sehingga storage_put()
 * tetap bisa dipakai apa adanya untuk S3 maupun penyimpanan lokal.
 * Mengembalikan null kalau berhasil, atau pesan error.
 */
function convertMusic($tmpPath) {
    if (!function_exists('exec')) {
        return 'Server tidak bisa memproses musik (exec nonaktif)';
    }
    @set_time_limit(120);

    $out = $tmpPath . '.conv.mp3';
    $cmd = 'timeout 90 ffmpeg -nostdin -y -loglevel error'
         . ' -i ' . escapeshellarg($tmpPath)
         . ' -vn -map_metadata -1'
         . ' -t ' . MUSIC_MAX_SECONDS
         . ' -ac 2 -ar 44100 -b:a ' . MUSIC_BITRATE
         . ' -f mp3 ' . escapeshellarg($out) . ' 2>&1';

    $output = [];
    $code = 0;
    exec($cmd, $output, $code);

    if ($code !== 0 || !is_file($out) || filesize($out) === 0) {
        error_log('INVITIA ffmpeg gagal (code ' . $code . '): ' . implode(' | ', array_slice($output, -3)));
        @unlink($out);
        return 'File musik tidak valid atau gagal diproses. Coba file MP3/M4A lain.';
    }
    if (filesize($out) > MUSIC_MAX_OUTPUT_BYTES) {
        @unlink($out);
        return 'Musik masih terlalu besar setelah dikompres. Pakai lagu yang lebih pendek (maks 5 menit).';
    }
    if (!copy($out, $tmpPath)) {
        @unlink($out);
        return 'Gagal menyimpan hasil konversi musik';
    }
    @unlink($out);
    return null;
}

/**
 * Validasi satu file upload lalu simpan lewat lib/storage.php (lokal / cloud).
 * Mengembalikan URL file, atau '' kalau tidak ada file / gagal (alasannya masuk $errors).
 * $isMusic = true untuk file musik (dikonversi ke MP3 sebelum disimpan).
 */
function saveUpload($field, $allowed, $maxBytes, &$errors, $isMusic = false) {
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

    // Untuk musik: konversi ke MP3 kecil (sekaligus memastikan isinya benar-benar audio)
    if ($isMusic) {
        $err = convertMusic($file['tmp_name']);
        if ($err !== null) {
            $errors[$field] = $err;
            return '';
        }
        $ext = 'mp3'; // hasil konversi selalu MP3
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
    'music' => saveUpload('musicUpload', $audioExts, MUSIC_MAX_INPUT_BYTES, $errors, true),
];
for ($i = 1; $i <= 6; $i++) {
    $result['gallery'][] = saveUpload('galleryUpload' . $i, $imageExts, 2 * 1024 * 1024, $errors);
}

$result['errors'] = $errors;

echo json_encode($result);
exit;
