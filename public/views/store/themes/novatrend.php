<?php
use App\Core\StockImages;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 800, 1000);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/novatrend.css">
</head>
<body class="theme-novatrend">
<div class="nt-topbar">Free Worldwide Shipping Over <?= htmlspecialchars($tenant['currency']) ?>50 &middot; <?= $h('announcement', 'Limited Time Flash Deals') ?></div>
<nav class="nt-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="nt-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="nt-logo-img"><?php endif; ?>
        <?= htmlspecialchars($tenant['business_name']) ?>
    </a>
    <div class="nt-search-wrap"><input id="store-search" placeholder="Search products..."></div>
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="nt-cart">&#128722; <span class="cart-count" id="cart-count">0</span></a>
</nav>

<section class="nt-hero">
    <div class="nt-hero-text">
        <span class="nt-eyebrow"><?= $h('eyebrow', 'TRENDING NOW') ?></span>
        <h1><?= $h('hero_heading', "Discover Products You'll Love") ?></h1>
        <p><?= $h('hero_subheading', 'Shop the latest trending products curated for your lifestyle.') ?></p>
        <a href="#products" class="btn-store">Shop Now &rarr;</a>
    </div>
    <div class="nt-hero-image">
        <img src="<?= htmlspecialchars($heroImg) ?>" alt="">
    </div>
</section>

<section class="nt-features">
    <div><strong>Free Shipping</strong><span>On orders over 50</span></div>
    <div><strong>Secure Payments</strong><span>100% protected</span></div>
    <div><strong>Easy Returns</strong><span>30-day policy</span></div>
    <div><strong>24/7 Support</strong><span>Always here to help</span></div>
</section>

<section class="nt-categories">
    <div class="nt-section-head"><h2>Shop by Category</h2></div>
    <div class="nt-cat-row" id="cat-filter-list" data-mode="pills"></div>
</section>

<section class="nt-products" id="products">
    <div class="nt-section-head"><h2>All Products</h2><span id="result-count" class="text-muted"></span></div>
    <div class="product-grid" id="product-grid"></div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>StoreApp.renderProductList();</script>
</body>
</html>
