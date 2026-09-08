<?php
use App\Models\StoreSettings;

$theme = in_array($settings['theme'] ?? '', StoreSettings::THEMES, true) ? $settings['theme'] : 'aurora';
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$page = 'cart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?> — Cart</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/<?= $theme ?>.css">
</head>
<body class="theme-<?= $theme ?> store-inner-page"<?= \App\Core\ThemePalettes::styleAttr($theme, $content['color_theme'] ?? 'signature') ?>>
<?php require __DIR__ . '/partials/theme-header.php'; ?>
<div class="store-container store-page">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/shop" class="back-link">&larr; Continue shopping</a>
    <h2>Your Cart</h2>
    <div id="cart-root"></div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>StoreApp.renderCartPage();</script>
</body>
</html>
