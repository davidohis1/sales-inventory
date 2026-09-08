<?php
$base = $GLOBALS['base'];
$txRef = (string) ($_GET['tx_ref'] ?? '');
$transactionId = (string) ($_GET['transaction_id'] ?? '');
$flwStatus = (string) ($_GET['status'] ?? '');

$ok = false;
$message = 'We could not confirm this payment.';
$redirectUrl = $base . '/';
$redirectLabel = 'Return home';

if ($flwStatus === 'cancelled') {
    $message = 'Payment was cancelled.';
} elseif ($txRef === '') {
    $message = 'Missing payment reference.';
} elseif (str_starts_with($txRef, 'SUB-')) {
    $result = \App\Controllers\Api\PaymentController::verifySubscriptionPayment($txRef, $transactionId);
    $ok = $result['ok'];
    $message = $result['message'];
    if ($ok && !empty($result['tenant_id'])) {
        $tenant = \App\Models\Tenant::findById((int) $result['tenant_id']);
        if ($tenant) {
            $redirectUrl = $base . '/' . $tenant['slug'] . 'portal';
            $redirectLabel = 'Go to your dashboard';
        }
    }
} elseif (str_starts_with($txRef, 'ORD-')) {
    $result = \App\Controllers\Api\OrderController::verifyOrderPayment($txRef, $transactionId);
    $ok = $result['ok'];
    $message = $result['message'];
    if ($ok && !empty($result['tenant_slug'])) {
        $redirectUrl = $base . '/' . $result['tenant_slug'];
        $redirectLabel = 'Back to the store';
    }
} elseif (str_starts_with($txRef, 'DPP-')) {
    $result = \App\Controllers\Api\DigitalProductPublicController::verifyPurchase($txRef, $transactionId);
    $ok = $result['ok'];
    $message = $result['message'];
    if ($ok && !empty($result['download_token'])) {
        $redirectUrl = $base . '/api/digital-products/download/' . $result['download_token'];
        $redirectLabel = 'Download your product';
    }
} else {
    $message = 'Unrecognized payment reference.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment <?= $ok ? 'Confirmed' : 'Status' ?> — Oripio</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="pub-body">
<div class="login-page">
    <div class="login-card" style="text-align:center;">
        <div style="font-size:44px; margin-bottom: 6px;"><?= $ok ? '&#9989;' : '&#9888;&#65039;' ?></div>
        <h2><?= $ok ? 'Payment Confirmed' : 'Payment Status' ?></h2>
        <p class="text-muted"><?= htmlspecialchars($message) ?></p>
        <a href="<?= htmlspecialchars($redirectUrl) ?>" class="btn" style="width:100%; justify-content:center; margin-top:10px;"><?= htmlspecialchars($redirectLabel) ?></a>
    </div>
</div>
</body>
</html>
