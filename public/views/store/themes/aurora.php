<?php
/** @var array $tenant */ /** @var array $settings */ /** @var string $slug */ /** @var string $base */
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : \App\Core\StockImages::url($storeType, 0, 900, 1100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/aurora.css">
</head>
<body class="theme-aurora">
<header class="ar-header">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="ar-logo">
        <?php if (!empty($content['logo_path'])): ?>
            <img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="ar-logo-img">
        <?php else: ?>
            <span class="ar-logo-mark">&#9650;</span>
        <?php endif; ?>
        <?= htmlspecialchars($tenant['business_name']) ?>
    </a>
    <div class="ar-search-wrap"><input id="store-search" class="ar-search" placeholder="Search among our products…"></div>
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="ar-cart">&#128722; Cart <span class="cart-count" id="cart-count">0</span></a>
</header>

<div class="ar-shell">
    <button class="ar-filter-toggle" id="ar-filter-toggle">&#9776; Filters</button>
    <aside class="ar-sidebar" id="ar-sidebar">
        <div class="ar-side-block">
            <h4>Category</h4>
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
        <div class="ar-hero" style="background-image:linear-gradient(120deg, rgba(238,240,255,0.88), rgba(247,248,255,0.88)), url('<?= htmlspecialchars($heroImg) ?>');">
            <h1><?= $h('hero_heading', 'Shop the Collection') ?></h1>
            <p><?= $h('hero_subheading', 'Curated products, fair prices, fast delivery.') ?></p>
        </div>
        <div class="ar-toolbar">
            <span id="result-count" class="text-muted">Loading…</span>
        </div>
        <div class="product-grid" id="product-grid"></div>
    </main>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>
StoreApp.renderProductList();
document.getElementById('ar-filter-toggle').addEventListener('click', () => document.getElementById('ar-sidebar').classList.toggle('open'));
</script>
</body>
</html>
