<?php
$accents = ['aurora' => '#1a3fa0', 'wink' => '#f4622d', 'luxora' => '#8a6d3b', 'marketly' => '#5b21b6', 'novatrend' => '#ff5722', 'verdant' => '#0d6d5c', 'blossom' => '#d6357a', 'amara' => '#a0492c'];
$theme = in_array($settings['theme'] ?? '', \App\Models\StoreSettings::THEMES, true) ? $settings['theme'] : 'aurora';
$accent = $accents[$theme] ?? '#0f5c56';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?> — Cart</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/<?= $theme ?>.css">
<style>:root { --store-primary: <?= $accent ?>; }</style>
</head>
<body class="theme-<?= $theme ?> store-chrome-page"<?= \App\Core\ThemePalettes::styleAttr($theme, ($settings['content']['color_theme'] ?? 'signature')) ?>>
<header class="store-header">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="brand"><?= htmlspecialchars($tenant['business_name']) ?></a>
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="cart-link">&#128722; Cart <span class="cart-count" id="cart-count">0</span></a>
</header>
<div class="store-container">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="back-link">&larr; Back to store</a>
    <h2>Your Cart</h2>
    <div id="cart-root"></div>
</div>
<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>StoreApp.renderCartPage();</script>
</body>
</html>
