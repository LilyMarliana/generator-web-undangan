<?php
require_once __DIR__ . '/../../lib/layout.php';

require_user();

$orderId = $_GET['order_id'] ?? '';
$payment = null;

if (is_string($orderId) && $orderId !== '') {
    $stmt = getDB()->prepare(
        "SELECT amount, status, created_at
        FROM payments
        WHERE midtrans_order_id=? AND user_id=?
        LIMIT 1"
    );
    $stmt->execute([$orderId, current_user()['id']]);
    $payment = $stmt->fetch();
}

app_header('Status Pembayaran');

if (!$payment): ?>
    <section class="card">
        <h2 class="card-title">Pembayaran tidak ditemukan</h2>
        <a class="btn-primary" href="dashboard.php">Kembali ke Dashboard</a>
    </section>
<?php elseif (in_array($payment['status'], ['settlement', 'capture'], true)): ?>
    <section class="card">
        <h2 class="card-title">Pembayaran berhasil</h2>
        <p>Paket Pro kamu aktif selama 30 hari.</p>
        <a class="btn-primary" href="dashboard.php">Ke Dashboard</a>
    </section>
<?php else: ?>
    <section class="card">
        <h2 class="card-title">Pembayaran sedang diproses</h2>
        <p class="muted">
            Status: <?= e($payment['status']) ?>.
            Paket Pro aktif setelah Midtrans mengonfirmasi pembayaran.
        </p>
        <a class="btn-primary" href="dashboard.php">Ke Dashboard</a>
    </section>
<?php endif;

app_footer();