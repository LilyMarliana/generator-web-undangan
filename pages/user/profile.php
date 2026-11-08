<?php
require_once __DIR__ . '/../../lib/layout.php';
require_user();

$db  = getDB();
$uid = (int)$_SESSION['user']['id'];
$self = url_user('profile.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf($self);
    $do = is_string($_POST['do'] ?? null) ? $_POST['do'] : '';

    if ($do === 'name') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            msg_add('error', 'Nama wajib diisi (maksimal 100 karakter).');
        } else {
            $db->prepare("UPDATE users SET name=? WHERE id=?")->execute([$name, $uid]);
            $_SESSION['user']['name'] = $name;
            msg_add('success', 'Profil berhasil diperbarui.');
        }
    } elseif ($do === 'pass') {
        $st = $db->prepare("SELECT password FROM users WHERE id=?");
        $st->execute([$uid]);
        $hash = (string)$st->fetchColumn();
        $old  = (string)($_POST['old'] ?? '');
        $new  = (string)($_POST['new'] ?? '');
        if ($hash === '') {
            msg_add('info', 'Akunmu masuk lewat Supabase/Google, jadi password dikelola di sana.');
        } elseif (!password_verify($old, $hash)) {
            msg_add('error', 'Password lama tidak sesuai.');
        } elseif (strlen($new) < 8) {
            msg_add('error', 'Password baru minimal 8 karakter.');
        } else {
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            msg_add('success', 'Password berhasil diganti.');
        }
    }
    redirect($self);
}

$st = $db->prepare("SELECT name, email, role, created_at, password FROM users WHERE id=?");
$st->execute([$uid]);
$me = $st->fetch();
$hasPass = $me['password'] !== '';

$q = function ($sql) use ($db, $uid) { $s = $db->prepare($sql); $s->execute([$uid]); return (int)$s->fetchColumn(); };
$nInv    = $q("SELECT COUNT(*) FROM prompts WHERE user_id=?");
$nRsvp   = $q("SELECT COUNT(*) FROM rsvps r JOIN prompts p ON p.id=r.prompt_id WHERE p.user_id=?");
$nGuests = $q("SELECT COALESCE(SUM(r.guests),0) FROM rsvps r JOIN prompts p ON p.id=r.prompt_id WHERE p.user_id=? AND r.attending='hadir'");
$nWish   = $q("SELECT COUNT(*) FROM wishes w JOIN prompts p ON p.id=w.prompt_id WHERE p.user_id=?");
$used    = $q("SELECT COALESCE(SUM(`count`),0) FROM quota_logs WHERE user_id=? AND `date`=CURDATE()");

// daily_quota() bernilai angka (paket Free) atau null (paket Pro = tanpa batas harian)
$quota     = daily_quota();
$unlimited = ($quota === null);
$pct       = $unlimited ? 0 : min(100, round($used / max(1, $quota) * 100));

// Informasi paket (aman dipanggil sebelum/sesudah fitur Pro tersedia)
$isPro    = function_exists('has_active_pro') && has_active_pro();
$proUntil = $_SESSION['user']['subscription_expires_at'] ?? null;

app_header('Profil', 'profile', ['subtitle' => 'Kelola data akun dan lihat ringkasan penggunaanmu.']);
?>
<style>
.pf-hero{display:flex;align-items:center;gap:18px;margin-bottom:22px}
.pf-av{width:68px;height:68px;border-radius:50%;background:var(--accent);color:#fff;display:grid;place-items:center;font:700 28px var(--font-display)}
.pf-hero h2{font-family:var(--font-display);font-size:22px}
.pf label{display:block;font-size:13px;font-weight:600;color:var(--ink-soft);margin:14px 0 6px}
.pf input{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--radius-sm);font:inherit;background:var(--surface);color:var(--ink)}
.pf input:focus{outline:2px solid var(--accent-soft);border-color:var(--accent)}
.pf input[disabled]{background:var(--bg);color:var(--muted)}
.pf .btn{margin-top:18px}
.qbar{height:10px;border-radius:99px;background:var(--line);overflow:hidden;margin:10px 0 6px}
.qbar i{display:block;height:100%;background:var(--accent);border-radius:99px;width:<?= (int)$pct ?>%}
</style>

<section class="card pf-hero">
    <span class="pf-av"><?= e(strtoupper(mb_substr($me['name'], 0, 1))) ?></span>
    <div>
        <h2><?= e($me['name']) ?><?= $isPro ? ' <span class="badge b-ok">Pro</span>' : '' ?></h2>
        <p class="small muted"><?= e($me['email']) ?> · Bergabung <?= e(fmt_date($me['created_at'], false)) ?></p>
        <?php if ($isPro && is_string($proUntil)): ?>
            <p class="small muted">Paket Pro aktif sampai <?= e(fmt_date($proUntil, false)) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="stats">
    <div class="stat"><span class="stat-ico"><?= icon('list') ?></span><div><div class="stat-val"><?= $nInv ?></div><div class="stat-label">Undangan</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('check') ?></span><div><div class="stat-val"><?= $nRsvp ?></div><div class="stat-label">RSVP masuk</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('users') ?></span><div><div class="stat-val"><?= $nGuests ?></div><div class="stat-label">Tamu hadir</div></div></div>
    <div class="stat"><span class="stat-ico"><?= icon('chat') ?></span><div><div class="stat-val"><?= $nWish ?></div><div class="stat-label">Ucapan</div></div></div>
</section>

<div class="grid-2 mb pf">
    <section class="card">
        <h2 class="card-title">Data akun</h2>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="do" value="name">
            <label for="nm">Nama lengkap</label>
            <input id="nm" name="name" value="<?= e($me['name']) ?>" maxlength="100" required>
            <label for="em">Email</label>
            <input id="em" value="<?= e($me['email']) ?>" disabled>
            <button class="btn" type="submit">Simpan perubahan</button>
        </form>
    </section>

    <section class="card">
        <h2 class="card-title">Kuota harian</h2>
        <?php if ($unlimited): ?>
            <p class="small muted">Paket Pro: generate tanpa batas harian.</p>
            <p class="small muted">Sudah dipakai hari ini: <?= $used ?> generate</p>
        <?php else: ?>
            <p class="small muted">Terpakai <?= $used ?> dari <?= (int)$quota ?> generate hari ini. Kuota kembali penuh setiap pergantian hari.</p>
            <div class="qbar"><i></i></div>
            <p class="small muted"><?= max(0, (int)$quota - $used) ?> generate tersisa</p>
        <?php endif; ?>

        <h2 class="card-title" style="margin-top:26px">Keamanan</h2>
        <?php if ($hasPass): ?>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="do" value="pass">
            <label for="po">Password lama</label>
            <input id="po" type="password" name="old" autocomplete="current-password" required>
            <label for="pn">Password baru (minimal 8 karakter)</label>
            <input id="pn" type="password" name="new" autocomplete="new-password" minlength="8" required>
            <button class="btn" type="submit">Ganti password</button>
        </form>
        <?php else: ?>
            <p class="small muted">Akunmu masuk lewat Supabase atau Google, jadi password dikelola di penyedia login tersebut.</p>
        <?php endif; ?>
    </section>
</div>
<?php app_footer();