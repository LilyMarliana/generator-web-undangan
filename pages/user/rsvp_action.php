<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/rsvp.php';
require_user();

$back = safe_back($_POST['back'] ?? '', 'rsvp.php');
require_post_csrf(url_user($back));

$db  = getDB();
$uid = (int)$_SESSION['user']['id'];
$do  = is_string($_POST['do'] ?? null) ? $_POST['do'] : '';
if (isset($_POST['delete_id'])) {
    $do = 'delete_one';
}

/** Ambil dan validasi isian RSVP dari form. Return [data, pesanError]. */
function read_rsvp_input() {
    $name    = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $status  = is_string($_POST['attending'] ?? null) ? $_POST['attending'] : '';
    $message = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
    $guests  = filter_var($_POST['guests'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]);

    if ($name === '' || mb_strlen($name) > 100) {
        return [null, 'Nama wajib diisi (maksimal 100 karakter).'];
    }
    if (!isset(rsvp_status_options()[$status])) {
        return [null, 'Status kehadiran tidak valid.'];
    }
    if ($guests === false) {
        return [null, 'Jumlah tamu harus berupa angka 1 sampai 50.'];
    }
    if (mb_strlen($message) > 1000) {
        return [null, 'Pesan maksimal 1000 karakter.'];
    }
    return [['name' => $name, 'attending' => $status, 'guests' => $guests, 'message' => $message], null];
}

switch ($do) {
    case 'add':
        [$data, $err] = read_rsvp_input();
        $pid = (is_string($_POST['prompt_id'] ?? null) && ctype_digit($_POST['prompt_id'])) ? (int)$_POST['prompt_id'] : 0;
        $own = $db->prepare("SELECT id FROM prompts WHERE id=? AND user_id=?");
        $own->execute([$pid, $uid]);
        if (!$own->fetch()) {
            $err = 'Pilih undangan milikmu untuk RSVP ini.';
        }
        if ($err) {
            msg_add('error', $err);
            break;
        }
        $db->prepare("INSERT INTO rsvps (prompt_id, name, attending, guests, message) VALUES (?,?,?,?,?)")
           ->execute([$pid, $data['name'], $data['attending'], $data['guests'], $data['message']]);
        msg_add('success', 'RSVP berhasil ditambahkan.');
        break;

    case 'edit':
        [$data, $err] = read_rsvp_input();
        $id  = (is_string($_POST['id'] ?? null) && ctype_digit($_POST['id'])) ? (int)$_POST['id'] : 0;
        $own = $db->prepare("SELECT r.id FROM rsvps r JOIN prompts p ON p.id=r.prompt_id WHERE r.id=? AND p.user_id=?");
        $own->execute([$id, $uid]);
        if (!$own->fetch()) {
            $err = 'RSVP tidak ditemukan atau bukan milikmu.';
        }
        if ($err) {
            msg_add('error', $err);
            break;
        }
        $db->prepare("UPDATE rsvps SET name=?, attending=?, guests=?, message=? WHERE id=?")
           ->execute([$data['name'], $data['attending'], $data['guests'], $data['message'], $id]);
        msg_add('success', 'RSVP berhasil diperbarui.');
        break;

    case 'delete_one':
        $id = (is_string($_POST['delete_id'] ?? null) && ctype_digit($_POST['delete_id'])) ? (int)$_POST['delete_id'] : 0;
        $st = $db->prepare("DELETE r FROM rsvps r JOIN prompts p ON p.id=r.prompt_id WHERE r.id=? AND p.user_id=?");
        $st->execute([$id, $uid]);
        if ($st->rowCount() > 0) {
            msg_add('success', 'RSVP berhasil dihapus.');
        } else {
            msg_add('error', 'RSVP tidak ditemukan atau bukan milikmu.');
        }
        break;

    case 'bulk_delete':
        $ids = [];
        foreach ((array)($_POST['ids'] ?? []) as $v) {
            if (is_string($v) && ctype_digit($v)) {
                $ids[] = (int)$v;
            }
        }
        $ids = array_slice(array_unique($ids), 0, 200);
        if (!$ids) {
            msg_add('error', 'Pilih minimal satu RSVP untuk dihapus.');
            break;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = $db->prepare("DELETE r FROM rsvps r JOIN prompts p ON p.id=r.prompt_id WHERE p.user_id=? AND r.id IN ($in)");
        $st->execute(array_merge([$uid], $ids));
        msg_add('success', $st->rowCount() . ' RSVP berhasil dihapus.');
        break;

    default:
        msg_add('error', 'Aksi tidak dikenal.');
}

redirect(url_user($back));