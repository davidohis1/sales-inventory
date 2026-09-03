<?php
use App\Core\StockImages;
use App\Models\Category;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 900, 600);
$categories = Category::allForTenant((int) $tenant['id']);
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
<div class="mk-topbar">Enterprise-level commerce platform &middot; <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart">Track Order</a></div>
<header class="mk-header">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="mk-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="mk-logo-img"><?php endif; ?>
        <?= htmlspecialchars($tenant['business_name']) ?>
    </a>
    <span class="mk-cat-btn">&#9776; Category</span>
    <div class="mk-search-wrap"><span class="mk-ai-tag">&#10024; AI</span><input id="store-search" placeholder="AI-powered search..."></div>
    <div class="mk-header-icons">
        <span>&#9825;</span>
        <span>&#128276;</span>
        <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="mk-cart">&#128722; <span class="cart-count" id="cart-count">0</span></a>
    </div>
</header>
<nav class="mk-subnav">
    <span>&#9989; Free Shipping</span>
    <span>&#128179; Secure Payment</span>
    <span>&#128260; Easy Returns</span>
    <span>&#127911; 24/7 Support</span>
</nav>

<section class="mk-hero">
    <div class="mk-hero-text">
        <span class="mk-eyebrow"><?= $h('eyebrow', 'AUTUMN LUXURY COLLECTION') ?></span>
        <h1><?= $h('hero_heading', 'Elevate Your Style') ?></h1>
        <p><?= $h('hero_subheading', 'Explore our curated selection of seasonal and trending essentials.') ?></p>
        <a href="#shop" class="btn-store">Shop Now &rarr;</a>
    </div>
    <div class="mk-hero-image"><img src="<?= htmlspecialchars($heroImg) ?>" alt=""></div>
</section>

<section class="mk-cat-grid" id="categories">
    <div class="mk-section-head"><h2>Featured Category Grid</h2><span class="mk-view-all">See all &rarr;</span></div>
    <div class="mk-cat-tiles">
        <?php $icons = ['&#128092;','&#128241;','&#128132;','&#127968;','&#9917;','&#127911;','&#128717;']; $i = 0; ?>
        <?php foreach (array_slice($categories, 0, 7) as $cat): ?>
        <a href="?category_id=<?= (int) $cat['id'] ?>" class="mk-cat-tile" style="background-image:url('<?= htmlspecialchars(StockImages::url($storeType, $i + 1, 300, 300)) ?>')"><span><?= htmlspecialchars($cat['name']) ?></span></a>
        <?php $i++; endforeach; ?>
        <?php if (empty($categories)): ?><span class="text-muted">Add categories to show them here</span><?php endif; ?>
    </div>
</section>

<section class="mk-flash">
    <div class="mk-flash-head">
        <div><span class="mk-eyebrow">FLASH SALES</span><h2>Deals Ending Soon</h2></div>
        <div class="mk-countdown" id="mk-countdown">
            <div><span id="mk-h">03</span><em>H</em></div><div><span id="mk-m">09</span><em>M</em></div><div><span id="mk-s">55</span><em>S</em></div>
        </div>
    </div>
</section>

<section class="mk-products" id="shop">
    <div class="mk-section-head"><h2>All Products</h2><span id="result-count" class="text-muted"></span></div>
    <div id="cat-filter-list" class="mk-cat-pills" data-mode="pills"></div>
    <div class="product-grid" id="product-grid"></div>
</section>

<section class="mk-deals" id="deals">
    <div class="mk-section-head"><h2>Deals &amp; Offers</h2></div>
    <div class="mk-deals-grid">
        <a href="#shop" class="mk-deal-tile mk-deal-1"><strong>Daily Deals</strong><span>Up to 40% off</span></a>
        <a href="#shop" class="mk-deal-tile mk-deal-2"><strong>Mega Sale Event</strong><span>Storewide savings</span></a>
        <a href="#shop" class="mk-deal-tile mk-deal-3"><strong>Buy One Get One</strong><span>Selected items</span></a>
        <a href="#shop" class="mk-deal-tile mk-deal-4"><strong>Bundle Discounts</strong><span>Save more together</span></a>
    </div>
</section>

<section class="mk-newsletter">
    <div><h3>Stay in the loop</h3><p>Get AI-curated picks and offers straight to your inbox.</p></div>
    <form id="mk-newsletter-form"><input type="email" placeholder="Enter your email" required><button class="btn-store" type="submit">Subscribe</button></form>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>
StoreApp.renderProductList();
document.getElementById('mk-newsletter-form').addEventListener('submit', (e) => { e.preventDefault(); e.target.reset(); alert('Thanks for subscribing!'); });
(function countdown() {
    let total = 3 * 3600 + 9 * 60 + 55;
    setInterval(() => {
        total = total > 0 ? total - 1 : 0;
        document.getElementById('mk-h').textContent = String(Math.floor(total / 3600)).padStart(2, '0');
        document.getElementById('mk-m').textContent = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
        document.getElementById('mk-s').textContent = String(total % 60).padStart(2, '0');
    }, 1000);
})();
</script>
</body>
</html>
