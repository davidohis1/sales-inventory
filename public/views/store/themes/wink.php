<?php
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/wink.css">
</head>
<body class="theme-wink">
<nav class="wk-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="wk-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="wk-logo-img"><?php endif; ?>
        <?= htmlspecialchars($tenant['business_name']) ?>
    </a>
    <div class="wk-search-wrap"><input id="store-search" class="wk-search" placeholder="Search…"><span class="wk-search-icon">&#128269;</span></div>
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="wk-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
</nav>

<div class="wk-promo"><?= $h('announcement', $h('hero_subheading', 'New arrivals every week — shop the collection now')) ?></div>

<div class="wk-crumb">Home &gt; <?= $h('collection_title', 'Our Collection') ?></div>

<div class="wk-shell">
    <button class="wk-filter-toggle" id="wk-filter-toggle">&#9776; Filter</button>
    <aside class="wk-sidebar" id="wk-sidebar">
        <div class="wk-side-block">
            <div class="wk-side-head">Category <span>Reset</span></div>
            <div id="cat-filter-list" data-mode="checkbox"></div>
        </div>
        <div class="wk-side-block">
            <div class="wk-side-head">Price</div>
            <div class="wk-price-row">
                <input type="number" id="price-min" placeholder="Min">
                <input type="number" id="price-max" placeholder="Max">
            </div>
            <button class="btn-store" id="filter-apply" style="width:100%; margin-top:10px;">Apply</button>
        </div>
    </aside>

    <main class="wk-main">
        <div class="wk-title-row">
            <div>
                <h1><?= $h('hero_heading', 'Explore The Various Collection') ?></h1>
                <p class="text-muted"><?= $h('hero_subheading', "Don't miss out on shopping with us.") ?></p>
            </div>
            <span id="result-count" class="text-muted"></span>
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
document.getElementById('wk-filter-toggle').addEventListener('click', () => document.getElementById('wk-sidebar').classList.toggle('open'));
</script>
</body>
</html>
