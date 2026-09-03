<?php
use App\Core\StockImages;
use App\Models\Category;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 500, 500);
$categories = Category::allForTenant((int) $tenant['id']);
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
<div class="ar-topbar">
    <span>&#128666; Free Delivery on Orders Over <?= htmlspecialchars($tenant['currency']) ?>50</span>
    <span>&#128260; 30-Day Easy Returns</span>
    <span class="ar-topbar-hide">&#127911; 24/7 Customer Support</span>
</div>
<header class="ar-header">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="ar-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="ar-logo-img"><?php else: ?><span class="ar-logo-mark">&#128722;</span><?php endif; ?>
        <span><?= htmlspecialchars($tenant['business_name']) ?><em>Shop More, Save More</em></span>
    </a>
    <div class="ar-search-wrap"><input id="store-search" class="ar-search" placeholder="Search for products, brands and more..."><button class="ar-search-btn">&#128269;</button></div>
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="ar-cart">&#128722; <span class="cart-count" id="cart-count">0</span> My Cart</a>
</header>
<nav class="ar-subnav">
    <span class="ar-cat-dropdown">&#9776; All Categories</span>
    <div class="ar-sublinks"><a href="#" class="active">Home</a><a href="#shop">Shop</a><a href="#categories">Categories</a><a href="#deal">Deals</a></div>
    <span class="ar-hot-offers">&#128293; Hot Offers</span>
</nav>

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
        <section class="ar-hero">
            <div class="ar-hero-text">
                <span class="ar-eyebrow">— <?= $h('eyebrow', 'New Arrival') ?></span>
                <h1><?= $h('hero_heading', 'New Collection 2024') ?></h1>
                <p><?= $h('hero_subheading', 'Discover the latest arrivals with innovative features and premium designs.') ?></p>
                <a href="#shop" class="btn-store">Shop Now &rarr;</a>
            </div>
            <div class="ar-hero-image"><img src="<?= htmlspecialchars($heroImg) ?>" alt=""></div>
        </section>

        <section class="ar-features">
            <div><strong>&#128666;</strong><span>Free Shipping<br><em>On orders over <?= htmlspecialchars($tenant['currency']) ?>50</em></span></div>
            <div><strong>&#9989;</strong><span>Secure Payment<br><em>100% secure payment</em></span></div>
            <div><strong>&#128260;</strong><span>Easy Returns<br><em>30 days return policy</em></span></div>
            <div><strong>&#127873;</strong><span>Gift Cards<br><em>The perfect gift</em></span></div>
        </section>

        <section class="ar-popular" id="shop">
            <div class="ar-section-head"><h2>Popular Products</h2><span id="result-count" class="text-muted"></span></div>
            <div class="product-grid" id="product-grid"></div>
        </section>

        <section class="ar-cats" id="categories">
            <div class="ar-section-head"><h2>Shop By Category</h2></div>
            <div class="ar-cat-row">
                <?php $icons = ['&#8986;','&#128717;','&#128100;','&#127968;','&#127911;','&#128268;']; $i = 0; ?>
                <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
                <a href="?category_id=<?= (int) $cat['id'] ?>" class="ar-cat-item"><span><?= $icons[$i++ % count($icons)] ?></span><?= htmlspecialchars($cat['name']) ?></a>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?><span class="text-muted">Add categories to show them here</span><?php endif; ?>
            </div>
        </section>

        <section class="ar-deal" id="deal">
            <div class="ar-deal-text">
                <span class="ar-deal-tag">Deal Of The Day</span>
                <h2><?= $h('deal_heading', 'Grab It Before It\'s Gone!') ?></h2>
                <div class="ar-countdown" id="ar-countdown">
                    <div><span id="ar-h">12</span><em>Days</em></div>
                    <div><span id="ar-hh">05</span><em>Hrs</em></div>
                    <div><span id="ar-m">34</span><em>Mins</em></div>
                    <div><span id="ar-s">56</span><em>Secs</em></div>
                </div>
                <a href="#shop" class="btn-store">See All Deals &rarr;</a>
            </div>
        </section>
    </main>
</div>

<section class="ar-newsletter">
    <div><h3>Subscribe To Our Newsletter</h3><p>Get the latest updates on new arrivals, offers &amp; more.</p></div>
    <form id="ar-newsletter-form"><input type="email" placeholder="Enter your email address" required><button class="btn-store" type="submit">Subscribe</button></form>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>
StoreApp.renderProductList();
document.getElementById('ar-filter-toggle').addEventListener('click', () => document.getElementById('ar-sidebar').classList.toggle('open'));
document.getElementById('ar-newsletter-form').addEventListener('submit', (e) => { e.preventDefault(); e.target.reset(); alert('Thanks for subscribing!'); });
(function countdown() {
    let total = 12 * 86400 + 5 * 3600 + 34 * 60 + 56;
    setInterval(() => {
        total = total > 0 ? total - 1 : 0;
        document.getElementById('ar-h').textContent = String(Math.floor(total / 86400)).padStart(2, '0');
        document.getElementById('ar-hh').textContent = String(Math.floor((total % 86400) / 3600)).padStart(2, '0');
        document.getElementById('ar-m').textContent = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
        document.getElementById('ar-s').textContent = String(total % 60).padStart(2, '0');
    }, 1000);
})();
</script>
</body>
</html>
