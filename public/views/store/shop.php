<?php
use App\Models\StoreSettings;

$theme = in_array($settings['theme'] ?? '', StoreSettings::THEMES, true) ? $settings['theme'] : 'aurora';
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$page = 'shop';

// Each theme already ships its own pill styling for the category filter on
// its homepage — reuse the same class here so the shop page's filter row
// looks native to the theme instead of generic.
$pillClass = [
    'amara' => 'am-cat-pills', 'blossom' => 'bl-cat-pills', 'luxora' => 'lx-cat-pills',
    'marketly' => 'mk-cat-pills', 'novatrend' => 'nt-cat-pills', 'verdant' => 'vd-cat-pills',
    'wink' => 'wk-cat-pills',
][$theme] ?? '';
$shopHeading = $content['shop_heading'] ?? 'Shop All Products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?> — Shop</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/<?= $theme ?>.css">
</head>
<body class="theme-<?= $theme ?> store-inner-page"<?= \App\Core\ThemePalettes::styleAttr($theme, $content['color_theme'] ?? 'signature') ?>>
<?php require __DIR__ . '/partials/theme-header.php'; ?>

<?php if ($theme === 'aurora'): ?>
<div class="ar-shell">
    <button class="ar-filter-toggle" id="ar-filter-toggle">&#9776; Categories</button>
    <aside class="ar-sidebar" id="ar-sidebar">
        <div class="ar-side-block">
            <h4>Categories</h4>
            <div id="cat-filter-list" data-mode="checkbox"></div>
        </div>
        <div class="ar-side-block">
            <h4>Price Range</h4>
            <div class="ar-price-row">
                <input type="number" id="price-min" placeholder="Min">
                <input type="number" id="price-max" placeholder="Max">
            </div>
            <button class="btn-store" id="filter-apply" style="width:100%; margin-top:10px;">Apply</button>
        </div>
    </aside>
    <main class="ar-main">
        <div class="shop-page-head"><h1><?= htmlspecialchars($shopHeading) ?></h1><span id="result-count" class="text-muted"></span></div>
        <div class="product-grid" id="product-grid"></div>
    </main>
</div>
<script>document.getElementById('ar-filter-toggle').addEventListener('click', () => document.getElementById('ar-sidebar').classList.toggle('open'));</script>

<?php else: ?>
<div class="store-container store-page shop-page">
    <div class="shop-page-head"><h1><?= htmlspecialchars($shopHeading) ?></h1><span id="result-count" class="text-muted"></span></div>
    <div id="cat-filter-list" class="<?= $pillClass ?>" data-mode="pills"></div>
    <div class="price-filter-row">
        <input type="number" id="price-min" placeholder="Min price">
        <input type="number" id="price-max" placeholder="Max price">
        <button class="btn-store outline" id="filter-apply">Apply</button>
    </div>
    <div class="product-grid" id="product-grid"></div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<?php if (in_array($theme, ['verdant', 'blossom'], true)): ?>
<script src="<?= $base ?>/assets/js/themes/<?= $theme ?>-renderer.js"></script>
<?php endif; ?>
<script>StoreApp.renderProductList();</script>
</body>
</html>
