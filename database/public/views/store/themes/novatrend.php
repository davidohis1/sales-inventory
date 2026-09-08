<?php
use App\Core\StockImages;
use App\Models\Category;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 800, 1000);
$categories = Category::allForTenant((int) $tenant['id']);
$promo1Img = !empty($content['promo1_path']) ? $base . $content['promo1_path'] : StockImages::url($storeType, 4, 700, 500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/novatrend.css">
</head>
<body class="theme-novatrend"<?= \App\Core\ThemePalettes::styleAttr('novatrend', $content['color_theme'] ?? 'signature') ?>>
<div class="nt-topbar">
    <span>&#128666; Free Worldwide Shipping Over <?= htmlspecialchars($tenant['currency']) ?>50</span>
    <span>&#127991; <?= $h('announcement', 'Summer Sale Up to 70% Off') ?></span>
    <span>&#9889; Limited Time Flash Deals</span>
</div>
<nav class="nt-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="nt-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="nt-logo-img"><?php endif; ?>
        <?= htmlspecialchars($tenant['business_name']) ?>
    </a>
    <div class="nt-links"><a href="#" class="active">Home</a><a href="#shop">Shop</a><a href="#categories">Categories</a><a href="#bestsellers">Best Sellers</a></div>
    <div class="nt-icons">
        <span id="store-search-toggle">&#128269;</span>
        <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="nt-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>
<div class="nt-search-wrap"><input id="store-search" placeholder="Search products..."></div>

<section class="nt-hero">
    <div class="nt-hero-text">
        <span class="nt-eyebrow"><?= $h('eyebrow', 'TRENDING NOW') ?></span>
        <h1><?= $h('hero_heading', "Discover Products You'll Love") ?></h1>
        <p><?= $h('hero_subheading', 'Shop the latest trending products curated for modern lifestyles.') ?></p>
        <div class="nt-hero-actions"><a href="#shop" class="btn-store">Shop Now &rarr;</a><a href="#categories" class="nt-outline-btn">Explore Collection</a></div>
        <div class="nt-loved">&#128101; Loved by <?= $h('customer_count', '50,000+') ?> customers worldwide</div>
    </div>
    <div class="nt-hero-image">
        <img src="<?= htmlspecialchars($heroImg) ?>" alt="">
        <div class="nt-float nt-float-1"><strong>Air Max 270</strong><span><?= htmlspecialchars($tenant['currency']) ?>129.99</span></div>
        <div class="nt-float nt-float-2"><strong>Smart Watch</strong><span><?= htmlspecialchars($tenant['currency']) ?>99.99</span></div>
        <div class="nt-float nt-float-3"><strong>Headphones</strong><span><?= htmlspecialchars($tenant['currency']) ?>39.99</span></div>
    </div>
</section>

<section class="nt-features">
    <div><strong>&#128666; Free Shipping</strong><span>On orders over <?= htmlspecialchars($tenant['currency']) ?>50</span></div>
    <div><strong>&#128274; Secure Payments</strong><span>100% secure checkout</span></div>
    <div><strong>&#128260; Easy Returns</strong><span>30-day return policy</span></div>
    <div><strong>&#127911; 24/7 Support</strong><span>Always here to help</span></div>
</section>

<section class="nt-categories" id="categories">
    <div class="nt-section-head"><h2>Shop by Categories</h2><span class="nt-view-all">View All Categories &rarr;</span></div>
    <div class="nt-cat-grid">
        <?php $icons = ['&#128092;','&#128241;','&#128132;','&#127939;','&#127968;','&#128142;']; $i = 0; ?>
        <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
        <a href="?category_id=<?= (int) $cat['id'] ?>" class="nt-cat-tile"><span><?= $icons[$i++ % count($icons)] ?></span><?= htmlspecialchars($cat['name']) ?></a>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?><span class="text-muted">Add categories to show them here</span><?php endif; ?>
    </div>
</section>

<section class="nt-products" id="shop">
    <div class="nt-section-head"><h2><?= $h('arrivals_heading', 'New Arrivals') ?></h2><span id="result-count" class="text-muted"></span></div>
    <div id="cat-filter-list" class="nt-cat-pills" data-mode="pills"></div>
    <div class="product-grid" id="product-grid"></div>
</section>

<section class="nt-promo-row">
    <div class="nt-promo-card nt-promo-dark">
        <span><?= $h('promo1_tag', 'Flash Sale') ?></span><strong><?= $h('promo1_heading', 'Up to 70% Off') ?></strong><a href="#shop" class="nt-outline-btn light">Shop Sale &rarr;</a>
    </div>
    <div class="nt-promo-card" style="background-image:url('<?= htmlspecialchars($promo1Img) ?>')">
        <div class="nt-promo-overlay"><span>New Collection</span><strong><?= $h('promo_heading', 'Summer 2025') ?></strong><a href="#shop" class="btn-store">Shop Collection &rarr;</a></div>
    </div>
</section>

<section class="nt-features nt-features-bottom">
    <div><strong>&#9989; Premium Quality</strong><span>Guaranteed best materials</span></div>
    <div><strong>&#128666; Fast Delivery</strong><span>Quick and reliable shipping</span></div>
    <div><strong>&#128274; Secure Checkout</strong><span>Your data is protected</span></div>
    <div><strong>&#11088; Customer Satisfaction</strong><span>Top rated by our customers</span></div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>
StoreApp.renderProductList();
document.getElementById('store-search-toggle').addEventListener('click', () => document.querySelector('.nt-search-wrap').classList.toggle('open'));
</script>
</body>
</html>
