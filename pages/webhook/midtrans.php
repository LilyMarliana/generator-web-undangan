<?php
require_once __DIR__ . '/../../lib/payment.php';

function respond(int $status, string $message): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $status < 400, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, 'Method not allowed');
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    respond(400, 'Invalid JSON');
}

$orderId = $data['order_id'] ?? '';
$statusCode = $data['status_code'] ?? '';
$grossAmount = $data['gross_amount'] ?? '';
$signature = $data['signature_key'] ?? '';
$transactionStatus = $data['transaction_status'] ?? '';
$transactionId = $data['transaction_id'] ?? null;

if (
    !is_string($orderId) || !is_scalar($statusCode) || !is_scalar($grossAmount) ||
    !is_string($signature) || !is_string($transactionStatus)
) {
    respond(400, 'Missing notification fields');
}

$expectedSignature = hash(
    'sha512',
    $orderId . (string)$statusCode . (string)$grossAmount . MIDTRANS_SERVER_KEY
);

if (!hash_equals($expectedSignature, $signature)) {
    error_log('Midtrans webhook: invalid signature for ' . $orderId);
    respond(403, 'Invalid signature');
}

$knownStatuses = ['pending', 'settlement', 'capture', 'deny', 'cancel', 'expire', 'failure'];
$isSuccessful = $transactionStatus === 'settlement'
    || ($transactionStatus === 'capture' && ($data['fraud_status'] ?? '') === 'accept');

/*
* A challenged card capture is not paid yet. Keep it pending until Midtrans
* sends an accepted capture or settlement notification.
*/
$statusToStore = $transactionStatus === 'capture' && !$isSuccessful
    ? 'pending'
    : (in_array($transactionStatus, $knownStatuses, true) ? $transactionStatus : null);

try {
    $db = getDB();
    $db->beginTransaction();

    $stmt = $db->prepare(
        "SELECT id, user_id, amount, plan, status
        FROM payments
        WHERE midtrans_order_id=?
        FOR UPDATE"
    );
    $stmt->execute([$orderId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        $db->rollBack();
        respond(404, 'Payment not found');
    }

    if (
        $payment['plan'] !== 'pro' ||
        !is_numeric($grossAmount) ||
        (int)(float)$grossAmount !== (int)$payment['amount']
    ) {
        $db->rollBack();
        error_log('Midtrans webhook: payment mismatch for ' . $orderId);
        respond(400, 'Payment mismatch');
    }

    $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($statusToStore !== null) {
        $stmt = $db->prepare(
            "UPDATE payments
            SET status = CASE
                    WHEN status IN ('settlement', 'capture') THEN status
                    ELSE ?
                END,
                midtrans_transaction_id=?,
                payload=CAST(? AS JSON)
            WHERE id=?"
        );
        $stmt->execute([$statusToStore, $transactionId, $payload, $payment['id']]);
    }

    $alreadyPaid = in_array($payment['status'], ['settlement', 'capture'], true);

    if ($isSuccessful && !$alreadyPaid) {
        $stmt = $db->prepare(
            "UPDATE users
            SET plan='pro',
                subscription_status='active',
                subscription_expires_at=DATE_ADD(
                    CASE
                        WHEN subscription_expires_at > NOW() THEN subscription_expires_at
                        ELSE NOW()
                    END,
                    INTERVAL " . PRO_DURATION_DAYS . " DAY
                )
            WHERE id=?"
        );
        $stmt->execute([$payment['user_id']]);
    }

    $db->commit();
    respond(200, 'Notification received');
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Midtrans webhook: ' . $e->getMessage());
    respond(500, 'Server error');
}