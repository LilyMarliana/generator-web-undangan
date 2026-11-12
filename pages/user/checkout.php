<?php
require_once __DIR__ . '/../../lib/layout.php';
require_once __DIR__ . '/../../lib/payment.php';

require_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check($_POST['csrf'] ?? '')) {
    msg_add('error', 'Permintaan pembayaran tidak valid.');
    redirect('dashboard.php');
}

if (has_active_pro()) {
    msg_add('info', 'Paket Pro kamu masih aktif.');
    redirect('dashboard.php');
}

$db = getDB();
$user = current_user();
$orderId = 'PRO-' . $user['id'] . '-' . bin2hex(random_bytes(12));
$paymentCreated = false;

try {
    $stmt = $db->prepare(
        "INSERT INTO payments (user_id, midtrans_order_id, amount, plan)
        VALUES (?, ?, ?, 'pro')"
    );
    $stmt->execute([$user['id'], $orderId, PRO_PRICE]);
    $paymentCreated = true;

    midtrans_configure();

    $snapUrl = \Midtrans\Snap::getSnapUrl([
        'transaction_details' => [
            'order_id' => $orderId,
            'gross_amount' => PRO_PRICE,
        ],
        'item_details' => [[
            'id' => 'pro-30-days',
            'price' => PRO_PRICE,
            'quantity' => 1,
            'name' => 'Paket Pro 30 Hari',
        ]],
        'customer_details' => [
            'first_name' => $user['name'],
            'email' => $user['email'],
        ],
        'callbacks' => [
            'finish' => BASE_URL . '/pages/user/payment.php?order_id=' . rawurlencode($orderId),
        ],
    ]);

    redirect($snapUrl);
} catch (Throwable $e) {
    error_log('checkout: ' . $e->getMessage());

    if ($paymentCreated) {
        $db->prepare("UPDATE payments SET status='failure' WHERE midtrans_order_id=?")
            ->execute([$orderId]);
    }

    msg_add('error', 'Pembayaran belum dapat dibuat. Silakan coba lagi.');
    redirect('dashboard.php');
}