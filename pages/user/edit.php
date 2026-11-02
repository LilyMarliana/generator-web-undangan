<?php
require_once __DIR__ . '/../../lib/layout.php';
require_user();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p  = owned_prompt($id);
if (!$p) {
    msg_add('error', 'Undangan tidak ditemukan atau bukan milikmu.');
    redirect(url_user('undangan.php'));
}

$error = '';
$f = [
    'title'      => $p['title'],
    'groom_name' => $p['groom_name'] ?? '',
    'bride_name' => $p['bride_name'] ?? '',
    'prompt'     => $p['prompt'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Token tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        foreach ($f as $k => $_) {
            $v = $_POST[$k] ?? '';
            $f[$k] = is_string($v) ? trim($v) : '';
        }
        $f['prompt'] = str_replace("\r\n", "\n", $f['prompt']); // browser mengirim baris baru gaya Windows

        if ($f['title'] === '' || $f['prompt'] === '') {
            $error = 'Judul dan isi prompt tidak boleh kosong.';
        } elseif (mb_strlen($f['title']) > 150) {
            $error = 'Judul terlalu panjang (maks 150 karakter).';
        } elseif (mb_strlen($f['groom_name']) > 100 || mb_strlen($f['bride_name']) > 100) {
            $error = 'Nama pengantin maksimal 100 karakter.';
        } elseif (strlen($f['prompt']) > 60000) {
            $error = 'Prompt terlalu panjang (maks sekitar 60.000 karakter).';
        } else {
            getDB()->prepare("UPDATE prompts SET title=?, groom_name=?, bride_name=?, prompt=? WHERE id=? AND user_id=?")
                   ->execute([$f['title'], $f['groom_name'] ?: null, $f['bride_name'] ?: null, $f['prompt'], $id, (int)$_SESSION['user']['id']]);

            if (strpos($f['prompt'], $p['api_key']) === false) {
                msg_add('info', 'Perhatian: API key tidak ada lagi di dalam prompt, jadi website hasil prompt ini tidak akan terhubung ke RSVP dan ucapan. Salin API key dari kartu "API Key" di halaman ini, lalu masukkan kembali ke prompt lewat tombol Edit.');
            }
            msg_add('success', 'Undangan berhasil diperbarui.');
            redirect(url_user('detail.php?id=' . $id));
        }
    }
}

app_header('Edit Undangan', 'undangan', ['subtitle' => 'Ubah judul, nama pengantin, atau isi prompt.']);
?>
<?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="card">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <div class="field">
        <label for="title">Judul</label>
        <input type="text" id="title" name="title" value="<?= e($f['title']) ?>" maxlength="150" required>
    </div>
    <div class="row">
        <div class="field">
            <label for="groom_name">Nama pengantin pria</label>
            <input type="text" id="groom_name" name="groom_name" value="<?= e($f['groom_name']) ?>" maxlength="100">
        </div>
        <div class="field">
            <label for="bride_name">Nama pengantin wanita</label>
            <input type="text" id="bride_name" name="bride_name" value="<?= e($f['bride_name']) ?>" maxlength="100">
        </div>
    </div>
    <div class="field">
        <label for="prompt">Isi prompt</label>
        <textarea id="prompt" name="prompt" class="mono tall" required><?= e($f['prompt']) ?></textarea>
        <p class="hint">Jangan hapus API key di dalam prompt agar RSVP &amp; ucapan tetap tersambung.</p>
    </div>
    <div class="form-actions">
        <button class="btn-primary" type="submit">Simpan perubahan</button>
        <a class="btn" href="detail.php?id=<?= $id ?>">Batal</a>
    </div>
</form>
<?php app_footer();