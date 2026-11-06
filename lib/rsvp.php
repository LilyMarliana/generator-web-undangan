<?php
/**
 * Bantuan untuk halaman RSVP Tamu: filter dipakai bersama oleh halaman daftar dan export CSV,
 * supaya yang tampil di layar dan yang diexport selalu sama.
 */

function rsvp_status_options() {
    return ['hadir' => 'Hadir', 'tidak_hadir' => 'Tidak hadir', 'belum_pasti' => 'Belum pasti'];
}

/** Baca filter dari URL dan validasi ketat. $myIds = id undangan milik user yang login. */
function rsvp_filters_from_get(array $myIds) {
    $pid = 0;
    if (is_string($_GET['prompt_id'] ?? null) && ctype_digit($_GET['prompt_id'])) {
        $pid = (int)$_GET['prompt_id'];
    }
    if (!in_array($pid, $myIds, true)) {
        $pid = 0; // bukan undangan miliknya: abaikan
    }
    $s      = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
    $status = isset(rsvp_status_options()[$s]) ? $s : '';
    $q      = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    return ['prompt_id' => $pid, 'status' => $status, 'q' => $q];
}

/** Susun WHERE + parameter. Selalu dibatasi ke undangan milik $uid. */
function rsvp_where($uid, array $f) {
    $where  = "p.user_id = ?";
    $params = [(int)$uid];
    if ($f['prompt_id']) {
        $where   .= " AND r.prompt_id = ?";
        $params[] = $f['prompt_id'];
    }
    if ($f['status'] !== '') {
        $where   .= " AND r.attending = ?";
        $params[] = $f['status'];
    }
    if ($f['q'] !== '') {
        $like     = '%' . addcslashes($f['q'], '%_\\') . '%';
        $where   .= " AND (r.name LIKE ? OR r.message LIKE ?)";
        array_push($params, $like, $like);
    }
    return [$where, $params];
}

/**
 * Alamat kembali setelah sebuah aksi. Hanya menerima halaman $page (mis. 'rsvp.php')
 * dengan query biasa, supaya tidak bisa dipakai mengarahkan pengguna ke situs lain.
 */
function safe_back($back, $page) {
    if (is_string($back) && preg_match('~^' . preg_quote($page, '~') . '(\?[A-Za-z0-9_=&%.+\-]*)?$~', $back)) {
        return $back;
    }
    return $page;
}