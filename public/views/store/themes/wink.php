<?php
use App\Core\StockImages;
use App\Models\Category;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 800, 700);
$categories = Category::allForTenant((int) $tenant['id']);
$dealImg = !empty($content['deal_path']) ? $base . $content['deal_path'] : StockImages::url($storeType, 1, 500, 500);
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
<div class="wk-topbar">
    <span>&#128666; Free Shipping — On orders over <?= htmlspecialchars($tenant['currency']) ?>75</span>
    <span>&#127991; Extra 10% Off — On prepaid orders</span>
</div>
<nav class="wk-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="wk-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="wk-logo-img"><?php else: ?><span class="wk-logo-icon">&#128717;</span><?php endif; ?>
        <?= htmlspecialchars($tenant['business_name']) ?>
    </a>
    <div class="wk-links"><a href="#shop">New In</a><a href="#categories">Categories</a><a href="#deal">Deals</a></div>
    <a href="#shop" class="wk-shop-btn">Shop Now</a>
</nav>

<section class="wk-hero">
    <div class="wk-hero-card">
        <span class="wk-hero-tag">LIMITED TIME ONLY</span>
        <h1><?= $h('hero_heading', 'Shop More, Save More!') ?></h1>
        <p><?= $h('hero_subheading', 'Discover amazing deals on your favorite products.') ?></p>
        <div class="wk-hero-trust">
            <span>&#9989; Best Prices<br><em>Guaranteed</em></span>
            <span>&#128274; Secure Payments</span>
            <span>&#128666; Fast Delivery<br><em>Worldwide</em></span>
        </div>
        <a href="#shop" class="btn-store">Explore Collection &rarr;</a>
    </div>
    <div class="wk-hero-image">
        <img src="<?= htmlspecialchars($heroImg) ?>" alt="">
        <div class="wk-badge">Up to<br><strong>50%</strong><br>OFF</div>
    </div>
</section>

<section class="wk-features">
    <div><strong>&#128666; Free Shipping</strong><span>On orders over <?= htmlspecialchars($tenant['currency']) ?>75</span></div>
    <div><strong>&#128260; Easy Returns</strong><span>30-day returns</span></div>
    <div><strong>&#127942; Premium Quality</strong><span>100% original products</span></div>
    <div><strong>&#127911; 24/7 Support</strong><span>We're here to help</span></div>
</section>

<section class="wk-cats" id="categories">
    <h2>Shop By Category</h2>
    <div class="wk-cat-row">
        <?php $icons = ['&#128092;','&#128100;','&#127968;','&#128142;','&#127911;','&#128241;']; $i = 0; ?>
        <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
        <a href="?category_id=<?= (int) $cat['id'] ?>" class="wk-cat-item">
            <span class="wk-cat-thumb" style="background-image:url('<?= htmlspecialchars(StockImages::url($storeType, $i + 2, 300, 300)) ?>')"></span>
            <?= htmlspecialchars($cat['name']) ?>
        </a>
        <?php $i++; endforeach; ?>
        <?php if (empty($categories)): ?><span class="text-muted">Add categories to show them here</span><?php endif; ?>
    </div>
</section>

<section class="wk-deal" id="deal">
    <div class="wk-deal-text">
        <span class="wk-deal-tag">DEAL OF THE DAY</span>
        <h2><?= $h('deal_heading', "Grab It Before It's Gone!") ?></h2>
        <p>Hurry! Limited stock available.</p>
        <div class="wk-countdown" id="wk-countdown">
            <div><span id="wk-h">08</span><em>Hrs</em></div>:
            <div><span id="wk-m">12</span><em>Mins</em></div>:
            <div><span id="wk-s">45</span><em>Secs</em></div>
        </div>
        <a href="#shop" class="btn-store">Shop The Deal &rarr;</a>
    </div>
    <div class="wk-deal-card">
        <img src="<?= htmlspecialchars($dealImg) ?>" alt="">
        <div class="wk-deal-info">
            <strong><?= $h('deal_product_name', 'Featured Product') ?></strong>
            <span class="wk-deal-cat"><?= $h('deal_category', 'Trending pick') ?></span>
            <div class="wk-deal-bar"><div style="width:68%;"></div></div>
            <span class="wk-deal-left">Only a few items left!</span>
        </div>
    </div>
</section>

<section class="wk-arrivals" id="shop">
    <div class="wk-section-head"><h2><?= $h('arrivals_heading', 'New Arrivals') ?></h2><span id="result-count" class="text-muted"></span></div>
    <div id="cat-filter-list" class="wk-cat-pills" data-mode="pills"></div>
    <div class="product-grid" id="product-grid"></div>
</section>

<section class="wk-newsletter">
    <div>
        <h3>&#9993; <?= $h('newsletter_heading', 'Get Exclusive Offers &amp; Updates') ?></h3>
        <p><?= $h('newsletter_subheading', 'Sign up now and get 10% off on your first order!') ?></p>
    </div>
    <form id="wk-newsletter-form"><input type="email" placeholder="Enter your email address" required><button class="btn-store" type="submit">Subscribe</button></form>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>
StoreApp.renderProductList();
document.getElementById('wk-newsletter-form').addEventListener('submit', (e) => { e.preventDefault(); e.target.reset(); alert('Thanks for subscribing!'); });
(function countdown() {
    let total = 8 * 3600 + 12 * 60 + 45;
    setInterval(() => {
        total = total > 0 ? total - 1 : 0;
        const h = String(Math.floor(total / 3600)).padStart(2, '0');
        const m = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
        const s = String(total % 60).padStart(2, '0');
        document.getElementById('wk-h').textContent = h;
        document.getElementById('wk-m').textContent = m;
        document.getElementById('wk-s').textContent = s;
    }, 1000);
})();
</script>
</body>
</html>
