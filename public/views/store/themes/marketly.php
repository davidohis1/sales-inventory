<?php
use App\Core\StockImages;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 700, 700);
$banner1 = StockImages::url($storeType, 4, 700, 500);
$banner2 = StockImages::url($storeType, 5, 700, 500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/marketly.css">
</head>
<body class="theme-marketly">
<div class="mk-announce">&#127881; <?= $h('announcement', 'Mega Sale is Live! Get up to 60% off') ?> &rarr;</div>
<nav class="mk-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="mk-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="mk-logo-img"><?php else: ?>&#128717;<?php endif; ?>
        <?= htmlspecialchars($tenant['business_name']) ?>
    </a>
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="mk-cart">&#128722; <span class="cart-count" id="cart-count">0</span></a>
</nav>
<div class="mk-search-bar"><input id="store-search" placeholder="Search for products and more..."><button>&#128269;</button></div>

<div class="mk-cat-strip" id="cat-filter-list" data-mode="pills"></div>

<section class="mk-hero">
    <div class="mk-hero-text">
        <span class="mk-eyebrow">PREMIUM QUALITY. PREMIUM YOU.</span>
        <h1><?= $h('hero_heading', 'Everything You Need, All in One Place') ?></h1>
        <p><?= $h('hero_subheading', 'Discover great products from a store you can trust. Best prices, premium quality & unbeatable service.') ?></p>
        <div class="mk-hero-actions"><a href="#products" class="btn-store">Shop Now &rarr;</a><a href="#products" class="btn-store outline">Explore Deals</a></div>
    </div>
    <div class="mk-hero-image"><img src="<?= htmlspecialchars($heroImg) ?>" alt=""></div>
</section>

<section class="mk-deal">
    <div><strong>&#9889; Flash Deal</strong><span>Limited Time Offer — check today's featured picks below</span></div>
    <div class="mk-countdown" id="mk-countdown">--:--:--</div>
</section>

<section class="mk-banners">
    <div class="mk-banner" style="background-image:url('<?= htmlspecialchars($banner1) ?>')"><div><strong>Summer Collection</strong><span>Up to 50% Off</span></div></div>
    <div class="mk-banner" style="background-image:url('<?= htmlspecialchars($banner2) ?>')"><div><strong>Essentials for Better Living</strong><span>Up to 40% Off</span></div></div>
</section>

<section class="mk-products" id="products">
    <div class="mk-section-head"><h2>Shop All Products</h2><span id="result-count" class="text-muted"></span></div>
    <div class="product-grid" id="product-grid"></div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<nav class="mk-bottom-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="active">&#127968;<span>Home</span></a>
    <a href="#products">&#128203;<span>Categories</span></a>
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart">&#128722;<span>Cart</span></a>
</nav>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>
StoreApp.renderProductList();
(function countdown() {
    let seconds = 2 * 86400 + 12 * 3600 + 45 * 60 + 30;
    const el = document.getElementById('mk-countdown');
    setInterval(() => {
        seconds = Math.max(0, seconds - 1);
        const d = Math.floor(seconds / 86400), h = Math.floor((seconds % 86400) / 3600), m = Math.floor((seconds % 3600) / 60), s = seconds % 60;
        el.textContent = `${d}d ${h}h ${m}m ${s}s`;
    }, 1000);
})();
</script>
</body>
</html>
