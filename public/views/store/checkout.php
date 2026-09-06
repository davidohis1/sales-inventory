<?php
use App\Models\StoreSettings;

$theme = in_array($settings['theme'] ?? '', StoreSettings::THEMES, true) ? $settings['theme'] : 'aurora';
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$page = 'checkout';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?> — Checkout</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/<?= $theme ?>.css">
</head>
<body class="theme-<?= $theme ?> store-inner-page"<?= \App\Core\ThemePalettes::styleAttr($theme, $content['color_theme'] ?? 'signature') ?>>
<?php require __DIR__ . '/partials/theme-header.php'; ?>
<div class="store-container store-page">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="back-link">&larr; Back to cart</a>
    <h2>Checkout</h2>
    <div id="checkout-root"></div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
<div class="toast-container" id="toast-container"></div>
<script>
  window.APP_BASE = <?= json_encode($base) ?>;
  window.TENANT_SLUG = <?= json_encode($slug) ?>;
  window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;
  // How this store wants orders routed after checkout: 'email' | 'whatsapp' | 'bank_transfer'
  window.STORE_CHECKOUT_CONFIG = {
    orderChannel: <?= json_encode($content['order_channel'] ?? 'email') ?>,
    whatsappNumber: <?= json_encode($content['whatsapp_number'] ?? null) ?>,
    bankName: <?= json_encode($content['bank_name'] ?? null) ?>,
    bankAccountName: <?= json_encode($content['bank_account_name'] ?? null) ?>,
    bankAccountNumber: <?= json_encode($content['bank_account_number'] ?? null) ?>,
  };
</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>StoreApp.renderCheckoutPage();</script>
</body>
</html>
