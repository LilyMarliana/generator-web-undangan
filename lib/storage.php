<?php
/**
 * Lapisan penyimpanan file upload (foto & musik undangan).
 *
 * Semua kode lain (upload.php, delete.php) HANYA memakai fungsi di file ini, jadi
 * pindah dari folder lokal ke cloud (mis. Amazon S3) nanti cukup menambah satu
 * "driver" di sini tanpa mengubah halaman lain.
 *
 * Driver diatur lewat konstanta STORAGE_DRIVER di config.php (opsional).
 * Kalau tidak ada, dipakai 'local' (folder assets/ di server).
 */
require_once __DIR__ . '/../config.php';

/** Nama driver aktif. */
function storage_driver() {
    return defined('STORAGE_DRIVER') ? STORAGE_DRIVER : 'local';
}

/** Folder fisik untuk driver local. */
function storage_local_dir() {
    return __DIR__ . '/../assets/';
}

/**
 * Pola nama file hasil upload (uniqid dengan more_entropy), contoh:
 *   6a9197535ed830.54212178.jpeg   (14 karakter hex + titik + 8 angka + ekstensi)
 * Pola ketat ini mencegah file lain di assets/ (style.css, app.css, dll)
 * ikut terhapus walau namanya disebut di teks prompt.
 */
function storage_name_pattern() {
    return '[a-f0-9]{13,15}\.[0-9]{8}\.(?:jpe?g|png|mp3)';
}

/**
 * Simpan file hasil upload dan kembalikan URL publiknya (null kalau gagal).
 * $tmpPath = $_FILES[...]['tmp_name'], $ext = ekstensi file huruf kecil tanpa titik.
 */
function storage_put($tmpPath, $ext) {
    $name = uniqid('', true) . '.' . $ext;

    switch (storage_driver()) {
        case 'local':
        default:
            $dir = storage_local_dir();
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (!move_uploaded_file($tmpPath, $dir . $name)) {
                return null;
            }
            return rtrim(BASE_URL, '/') . '/assets/' . $name;
    }
}

/**
 * Cari semua file upload milik aplikasi yang disebut di dalam sebuah teks (prompt).
 * Mengembalikan daftar "ref" unik (untuk driver local = nama file).
 */
function storage_refs_in($text) {
    if (!preg_match_all('~/assets/(' . storage_name_pattern() . ')~i', (string)$text, $m)) {
        return [];
    }
    return array_values(array_unique($m[1]));
}

/** Hapus satu file berdasarkan ref. Return true kalau file benar-benar terhapus. */
function storage_delete($ref) {
    if (!preg_match('~^' . storage_name_pattern() . '$~i', (string)$ref)) {
        return false; // bukan file upload milik aplikasi
    }

    switch (storage_driver()) {
        case 'local':
        default:
            $dir  = realpath(storage_local_dir());
            $file = $dir ? realpath($dir . DIRECTORY_SEPARATOR . $ref) : false;
            // pastikan benar-benar berada di dalam folder assets/
            if ($file && strpos($file, $dir . DIRECTORY_SEPARATOR) === 0 && is_file($file)) {
                return @unlink($file);
            }
            return false;
    }
}