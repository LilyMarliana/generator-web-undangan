<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/auth.php';

require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!csrf_check($_POST['csrf'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Token tidak valid']);
    exit;
}

$title      = trim($_POST['title'] ?? '');
$prompt     = trim($_POST['prompt'] ?? '');
$groomName  = trim($_POST['groom_name'] ?? '');
$brideName  = trim($_POST['bride_name'] ?? '');

if ($title === '' || $prompt === '') {
    echo json_encode(['success' => false, 'error' => 'Judul dan prompt harus diisi']);
    exit;
}

// Validasi panjang input (sesuaikan angka ini dengan definisi kolom di DB kamu)
if (mb_strlen($title) > 255) {
    echo json_encode(['success' => false, 'error' => 'Judul terlalu panjang (maks 255 karakter)']);
    exit;
}
if (mb_strlen($prompt) > 20000) {
    echo json_encode(['success' => false, 'error' => 'Prompt terlalu panjang']);
    exit;
}

$db     = getDB();
$today  = date('Y-m-d');
$user   = current_user();
$userId = $user['id'];
$quota  = 5;

// Pastikan baris quota hari ini ada (aman kalau dipanggil bersamaan / concurrent)
$db->prepare("INSERT IGNORE INTO quota_logs (user_id, date, count) VALUES (?, ?, 0)")
   ->execute([$userId, $today]);

// Cek + increment dalam SATU operasi atomik di level SQL, menghilangkan race condition
// (dua request bersamaan tidak bisa lolos cek quota di waktu yang sama)
$stmt = $db->prepare("UPDATE quota_logs SET count = count + 1 WHERE user_id = ? AND date = ? AND count < ?");
$stmt->execute([$userId, $today, $quota]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'error' => 'Limit harian tercapai (5x per hari). Silakan coba lagi besok.']);
    exit;
}

// Ambil sisa quota terbaru untuk response (opsional, buat ditampilkan di UI)
$countStmt = $db->prepare("SELECT count FROM quota_logs WHERE user_id = ? AND date = ?");
$countStmt->execute([$userId, $today]);
$currentCount = (int) $countStmt->fetchColumn();

$apiKey = bin2hex(random_bytes(16));

// Isi placeholder API key pada prompt hasil generator
// Catatan: placeholder pakai {{API_KEY}} (dobel kurung) supaya TIDAK ikut
// mengganti teks ${API_KEY} yang ada di contoh kode JavaScript di bawahnya.
if (strpos($prompt, '{{API_KEY}}') !== false) {
    $prompt = str_replace('{{API_KEY}}', $apiKey, $prompt);
} else {
    // Fallback: prompt lama tanpa blok API, tambahkan
    $block  = "\n\nAPI Endpoints untuk RSVP dan Ucapan Tamu:\n";
    $block .= "- API Key: {$apiKey}\n";
    $block .= "- Base URL: " . rtrim(BASE_URL, '/') . "/api.php\n\n";
    $block .= "Format Response API:\n";
    $block .= "- Semua response berformat JSON: { \"success\": true/false, \"data\": [...], \"error\": \"pesan error\" }\n";
    $block .= "- Untuk mengecek apakah request berhasil, gunakan: if (response.ok && data.success)\n\n";
    $block .= "Contoh kode JavaScript yang benar (WAJIB ikuti pola ini):\n";
    $block .= "  async function submitRSVP(payload) {\n";
    $block .= "      const response = await fetch(`\${BASE_URL}?action=rsvp&api_key=\${API_KEY}`, {\n";
    $block .= "          method: 'POST',\n";
    $block .= "          headers: { 'Content-Type': 'application/json' },\n";
    $block .= "          body: JSON.stringify(payload)\n";
    $block .= "      });\n";
    $block .= "      const data = await response.json();\n";
    $block .= "      if (!response.ok || !data.success) {\n";
    $block .= "          showToast('Gagal mengirim: ' + (data.error || 'Terjadi kesalahan'), true);\n";
    $block .= "          return;\n";
    $block .= "      }\n";
    $block .= "      showToast('Berhasil dikirim!');\n";
    $block .= "      fetchWishes(); // Refresh list\n";
    $block .= "  }\n\n";
    $block .= "Contoh fetch data (GET):\n";
    $block .= "  async function fetchWishes() {\n";
    $block .= "      const res = await fetch(`\${BASE_URL}?action=wish&api_key=\${API_KEY}`);\n";
    $block .= "      const data = await res.json();\n";
    $block .= "      if (res.ok && data.success && Array.isArray(data.data)) {\n";
    $block .= "          renderWishes(data.data);\n";
    $block .= "      }\n";
    $block .= "  }\n\n";
    $block .= "- Setelah POST RSVP/Wish berhasil, panggil ulang GET untuk refresh data\n";
    $block .= "- Gunakan escapeHtml() pada semua teks dari user (XSS protection)\n";
    $block .= "- Tampilkan toast error (bukan hanya toast sukses) jika gagal\n";
    $block .= "- Jangan gunakan localStorage fallback — semua data harus ke API\n";
    $block .= "- Pastikan API key yang digunakan adalah api_key yang benar (check console untuk debugging)\n\n";
    $block .= "Pastikan website memiliki:\n";
    $block .= "- Form RSVP (input nama, pilihan kehadiran, jumlah tamu, catatan)\n";
    $block .= "- Kolom ucapan (input nama, textarea pesan)\n";
    $block .= "- Tampilan daftar RSVP dan ucapan yang diambil secara real-time dari GET endpoint\n";
    $block .= "- Semua teks dari user harus di-escape sebelum ditampilkan\n";
    $block .= "- Form submission harus pakai preventDefault() dan async/await";
    $prompt .= $block;
}

$stmt = $db->prepare("INSERT INTO prompts (api_key, user_id, title, groom_name, bride_name, prompt) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$apiKey, $userId, $title, $groomName ?: null, $brideName ?: null, $prompt]);

echo json_encode([
    'success'   => true,
    'id'        => $db->lastInsertId(),
    'api_key'   => $apiKey,
    'remaining' => $quota - $currentCount,
]);
exit;