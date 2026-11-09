<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/auth.php';

$base   = rtrim(BASE_URL, '/');
$in     = is_logged_in();
$dash   = $base . ($in ? (($_SESSION['user']['role'] ?? '') === 'admin' ? '/pages/admin/dashboard.php' : '/pages/user/dashboard.php') : '/pages/login.php');
$harga  = 'Rp' . number_format(defined('PRO_PRICE') ? PRO_PRICE : 18000, 0, ',', '.');
$hari   = defined('PRO_DURATION_DAYS') ? PRO_DURATION_DAYS : 30;
$repo   = 'https://github.com/LilyMarliana/generator-web-undangan';
$apiUrl = $base . '/api.php';
$upd    = 'Terakhir diperbarui: 8 November 2026';

$P = [];
$P['tentang'] = ['Tentang kami', <<<HTML
<p>Invitia membantu pasangan membuat website undangan pernikahan tanpa menulis kode dan tanpa menulis prompt dari nol. Kamu mengisi formulir lima langkah, kami menyusun prompt lengkap, lalu kamu menempelkannya ke AI favoritmu (ChatGPT, Claude, Cursor, dan lainnya).</p>
<div class="cards"><div><b>Wizard 5 langkah</b>Data pernikahan, tema, elemen, cerita, lalu review.</div><div><b>Prompt siap pakai</b>Instruksi desain dan teknis yang rinci untuk AI.</div><div><b>RSVP &amp; ucapan</b>API dengan kunci unik per undangan, hasilnya tampil di dashboard.</div></div>
<p>Invitia dikembangkan oleh tim mahasiswa sebagai proyek SaaS. Kritik dan saran sangat kami hargai, lihat halaman <a href="?p=kontak">Hubungi kami</a>.</p>
HTML];
$P['cara-penggunaan'] = ['Cara penggunaan', <<<HTML
<ol class="steps">
<li><b>Daftar atau masuk.</b> Bisa dengan email atau akun Google.</li>
<li><b>Buka Buat Undangan</b> dan isi lima langkah: Data Pernikahan, Tema &amp; Gaya Visual, Elemen Website &amp; Upload, Cerita &amp; Ucapan, lalu Review &amp; Generate. Contoh: pasangan <i>Marliana &amp; Choi Seungcheol</i>.</li>
<li><b>Salin prompt</b> yang dihasilkan dan tempel ke AI developer pilihanmu.</li>
<li><b>Pasang website</b> hasil AI. Form RSVP dan ucapan sudah terhubung ke API milik undanganmu.</li>
<li><b>Pantau di dashboard.</b> Lihat RSVP, ekspor daftar tamu, dan sembunyikan ucapan yang tidak pantas.</li>
</ol>
<p class="note">Tips: tulis cerita dan ucapan secara garis besar saja. AI yang akan merapikan bahasanya. Foto maksimal 2 MB dan musik maksimal 5 MB.</p>
HTML];
$P['paket'] = ['Paket Free & Pro', <<<HTML
<table><thead><tr><th></th><th>Free</th><th>Pro</th></tr></thead><tbody>
<tr><th>Harga</th><td>Rp0</td><td>{$harga} / {$hari} hari</td></tr>
<tr><th>Prompt per hari</th><td>5</td><td>Tanpa batas</td></tr>
<tr><th>Wizard, tema, elemen website</th><td>✓</td><td>✓</td></tr>
<tr><th>API RSVP &amp; ucapan, ekspor RSVP</th><td>✓</td><td>✓</td></tr>
</tbody></table>
<h3>Cara upgrade</h3>
<p>Masuk ke dashboard, pilih upgrade ke Pro, lalu bayar lewat Midtrans. Paket aktif otomatis setelah pembayaran lunas dan berlaku {$hari} hari. Membayar lagi sebelum masa aktif habis akan menambah {$hari} hari dari sisa masa aktifmu. Pro tidak diperpanjang otomatis.</p>
HTML];
$P['faq'] = ['Pertanyaan umum', <<<HTML
<details open><summary>Apakah hasilnya langsung berupa website?</summary><p>Invitia menghasilkan prompt. Prompt itu kamu berikan ke AI yang kemudian menulis kode website undangannya.</p></details>
<details><summary>Saya tidak bisa coding, apakah tetap bisa?</summary><p>Bisa. Kamu hanya mengisi formulir dan menyalin hasilnya.</p></details>
<details><summary>Berapa kuota harian?</summary><p>Free mendapat 5 prompt per hari dan reset setiap hari. Pro tidak dibatasi per hari.</p></details>
<details><summary>Bagaimana RSVP dan ucapan tamu tersimpan?</summary><p>Setiap undangan punya API key unik. Website undangan mengirim data ke API, lalu muncul di dashboard-mu.</p></details>
<details><summary>Apakah Pro diperpanjang otomatis?</summary><p>Tidak. Setiap pembayaran menambah {$hari} hari masa aktif.</p></details>
<details><summary>Boleh mengedit setelah prompt dibuat?</summary><p>Boleh. Prompt tersimpan di dashboard dan bisa diedit, dihapus, atau di-generate ulang.</p></details>
HTML];
$P['api'] = ['Panduan integrasi API', '<p>Setiap undangan memiliki API key unik di halaman detailnya. Semua respons berformat JSON dengan kolom <code>success</code>. Bila gagal, ada kolom <code>error</code>.</p>'
 . '<p>Base URL: <code>' . e($apiUrl) . '</code></p>'
 . '<table><thead><tr><th>Endpoint</th><th>Fungsi</th></tr></thead><tbody>'
 . '<tr><td><code>GET ?action=info&amp;api_key=KEY</code></td><td>Nama mempelai</td></tr>'
 . '<tr><td><code>GET ?action=rsvp&amp;api_key=KEY</code></td><td>Daftar RSVP</td></tr>'
 . '<tr><td><code>POST ?action=rsvp&amp;api_key=KEY</code></td><td>Kirim RSVP</td></tr>'
 . '<tr><td><code>GET ?action=wish&amp;api_key=KEY</code></td><td>Daftar ucapan (yang disembunyikan tidak tampil)</td></tr>'
 . '<tr><td><code>POST ?action=wish&amp;api_key=KEY</code></td><td>Kirim ucapan</td></tr></tbody></table>'
 . '<pre>POST ?action=rsvp&amp;api_key=KEY
{ "name": "Choi Seungcheol", "attending": "hadir", "guests": 2, "message": "Selamat!" }

POST ?action=wish&amp;api_key=KEY
{ "name": "Marliana", "message": "Semoga bahagia selalu" }</pre>'
 . '<p class="note"><code>attending</code> berisi <code>hadir</code>, <code>tidak_hadir</code>, atau <code>belum_pasti</code>. Batas: nama 100 karakter, pesan 1000 karakter, tamu 1 sampai 50, dan 30 kiriman per menit per undangan.</p>'];
$P['syarat'] = ['Syarat & ketentuan', <<<HTML
<ul><li>Invitia adalah alat untuk menyusun prompt undangan pernikahan digital.</li>
<li>Kamu bertanggung jawab atas data, foto, dan musik yang kamu unggah. Jangan mengunggah konten yang melanggar hukum atau hak pihak lain.</li>
<li>Kuota harian mengikuti paket yang aktif.</li>
<li>Akun yang menyalahgunakan layanan, misalnya spam lewat API, dapat dinonaktifkan oleh admin.</li>
<li>Kami dapat memperbarui ketentuan ini dari waktu ke waktu. Versi terbaru selalu ada di halaman ini.</li></ul>
<p class="note">{$upd}</p>
HTML];
$P['privasi'] = ['Kebijakan privasi', <<<HTML
<ul><li><b>Data yang kami simpan:</b> nama, email, data undangan, serta RSVP dan ucapan yang dikirim tamu.</li>
<li><b>Login:</b> dikelola lewat Supabase Auth (email atau Google). Kami tidak melihat kata sandi Google-mu.</li>
<li><b>Foto dan musik:</b> disimpan di server aplikasi dan dihapus saat prompt terkait dihapus.</li>
<li><b>Pembayaran:</b> diproses Midtrans. Kami hanya menyimpan nomor pesanan, nominal, status, dan notifikasi transaksi, bukan data kartu atau rekeningmu.</li>
<li><b>Cookie:</b> hanya cookie sesi untuk menjaga login dan keamanan formulir.</li>
<li>Kami tidak menjual data pengguna.</li></ul>
<p class="note">{$upd}</p>
HTML];
$P['pembayaran'] = ['Kebijakan pembayaran', <<<HTML
<ul><li>Pro berharga {$harga} untuk {$hari} hari, dibayar sekali tanpa langganan otomatis.</li>
<li>Pembayaran diproses oleh Midtrans dengan berbagai metode yang tersedia di halaman bayar.</li>
<li>Paket aktif otomatis setelah pembayaran lunas. Jika belum aktif setelah beberapa menit, muat ulang dashboard lalu hubungi kami.</li>
<li>Tagihan yang tidak dibayar akan kedaluwarsa dan tidak dikenakan biaya.</li>
<li>Permintaan pengembalian dana ditinjau satu per satu. Ajukan lewat halaman <a href="?p=kontak">Hubungi kami</a> dengan menyertakan nomor pesanan.</li></ul>
<p class="note">{$upd}</p>
HTML];
$P['kontak'] = ['Hubungi kami', '<p>Punya pertanyaan, kendala, atau saran? Sampaikan lewat halaman Issues di GitHub kami.</p>'
 . '<p><a class="btn" href="' . e($repo) . '/issues" target="_blank" rel="noopener">Buka GitHub Issues</a></p>'
 . '<p class="note">Untuk masalah pembayaran, sertakan nomor pesanan dan jangan menulis kunci atau kata sandi apa pun di sana.</p>'];

$p = (isset($_GET['p']) && is_string($_GET['p']) && isset($P[$_GET['p']])) ? $_GET['p'] : 'tentang';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($P[$p][0]) ?> · Invitia</title>
<link rel="icon" type="image/png" href="<?= e($base) ?>/assets/favicon.png">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Work+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root{--ink:#2b241d;--bg:#f6f1e9;--cream:#fbf3e5;--surface:#fffdf9;--accent:#a8503a;--soft:#f1ded6;--border:#e5dcca;--muted:#8a8072}
*{margin:0;padding:0;box-sizing:border-box}html{scroll-behavior:smooth}
body{font:16px/1.7 'Work Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink)}
a{color:var(--accent)}
.nav{display:flex;align-items:center;justify-content:space-between;padding:16px clamp(20px,5vw,44px);background:rgba(246,241,233,.9);backdrop-filter:blur(10px);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:20}
.nav img{height:30px;width:auto}.nav div{display:flex;gap:20px;font-size:14px}.nav a{text-decoration:none;color:var(--muted)}.nav a:hover{color:var(--accent)}
.hero{text-align:center;padding:56px 20px 30px}
.hero h1{font:700 clamp(30px,5vw,46px)/1.15 'Fraunces',Georgia,serif;animation:up .7s both}
.hero p{color:var(--muted);margin-top:8px;animation:up .7s .1s both}
@keyframes up{from{opacity:0;transform:translateY(16px)}}
.wrap{max-width:1040px;margin:0 auto;padding:10px clamp(20px,5vw,44px) 20px;display:grid;grid-template-columns:230px 1fr;gap:32px;align-items:start}
.side{position:sticky;top:84px;display:grid;gap:4px}
.side a{display:block;padding:10px 14px;border-radius:12px;text-decoration:none;color:var(--ink);transition:.25s;border:1px solid transparent}
.side a:hover{background:var(--soft);transform:translateX(4px)}
.side a.on{background:var(--accent);color:#fff}
.card{background:var(--surface);border:1px solid var(--border);border-radius:22px;padding:clamp(22px,4vw,40px);animation:up .6s .15s both;min-height:320px}
.card h2{font:700 28px 'Fraunces',Georgia,serif;margin-bottom:16px}.card h3{font:600 20px 'Fraunces',serif;margin:22px 0 8px}
.card p,.card ul,.card ol,.card table,.card pre{margin-bottom:14px}.card ul,.card ol{padding-left:22px}.card li{margin-bottom:6px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin:18px 0}
.cards div{background:var(--cream);border:1px solid var(--border);border-radius:16px;padding:16px;font-size:14px;transition:.3s}
.cards div:hover{transform:translateY(-5px);box-shadow:0 14px 28px rgba(43,36,29,.1)}.cards b{display:block;font:600 16px 'Fraunces',serif;margin-bottom:4px}
.steps li::marker{color:var(--accent);font-weight:700}
.note{background:var(--cream);border-left:3px solid var(--accent);padding:12px 16px;border-radius:0 12px 12px 0;font-size:14px;color:#5b5146}
table{width:100%;border-collapse:collapse;font-size:14.5px;display:block;overflow-x:auto}
th,td{padding:12px 14px;border-bottom:1px solid var(--border);text-align:left}thead th{background:var(--cream)}
code{background:var(--soft);padding:2px 6px;border-radius:6px;font-size:13px;word-break:break-all}
pre{background:#2b241d;color:#f1ded6;border-radius:14px;padding:16px;overflow-x:auto;font-size:13px;line-height:1.7}
details{border-bottom:1px solid var(--border)}summary{cursor:pointer;padding:16px 28px 16px 0;font-weight:600;list-style:none;position:relative}
summary::-webkit-details-marker{display:none}summary:after{content:"+";position:absolute;right:4px;font-size:22px;color:var(--accent);transition:.3s}
details[open] summary:after{transform:rotate(45deg)}details p{color:#5b5146;animation:up .4s both}
.btn{display:inline-block;background:var(--accent);color:#fff;padding:12px 24px;border-radius:99px;text-decoration:none;font-weight:600;transition:.25s}
.btn:hover{transform:translateY(-3px);box-shadow:0 12px 24px rgba(168,80,58,.3)}
@media(max-width:800px){.wrap{grid-template-columns:1fr}.side{position:static;display:flex;overflow-x:auto;gap:8px;padding-bottom:6px}.side a{white-space:nowrap}}
@media(prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
</style>
</head>
<body>
<nav class="nav">
    <a href="<?= e($base) ?>/index.php"><img src="<?= e($base) ?>/assets/logo-wordmark.png" alt="Invitia"></a>
    <div><a href="<?= e($base) ?>/index.php">Beranda</a><a href="<?= e($dash) ?>"><?= $in ? 'Dashboard' : 'Masuk' ?></a></div>
</nav>
<header class="hero"><h1>Pusat Bantuan</h1><p>Panduan, kebijakan, dan jawaban untuk pengguna Invitia.</p></header>
<div class="wrap">
    <aside class="side" aria-label="Menu bantuan">
        <?php foreach ($P as $k => $v): ?><a href="?p=<?= e($k) ?>" class="<?= $k === $p ? 'on' : '' ?>"><?= e($v[0]) ?></a><?php endforeach; ?>
    </aside>
    <article class="card"><h2><?= e($P[$p][0]) ?></h2><?= $P[$p][1] ?></article>
</div>
<?php require __DIR__ . '/../lib/site_footer.php'; ?>
</body>
</html>