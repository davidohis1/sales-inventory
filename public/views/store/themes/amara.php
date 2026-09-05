<?php
use App\Core\StockImages;
use App\Models\Category;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 900, 650);
$categories = Category::allForTenant((int) $tenant['id']);
$promo1Img = !empty($content['promo1_path']) ? $base . $content['promo1_path'] : StockImages::url($storeType, 5, 500, 600);
$promo2Img = !empty($content['promo2_path']) ? $base . $content['promo2_path'] : StockImages::url($storeType, 6, 500, 600);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/amara.css">
</head>
<body class="theme-amara"<?= \App\Core\ThemePalettes::styleAttr('amara', $content['color_theme'] ?? 'signature') ?>>
<div class="am-topbar">
    <span>&#128666; Free Shipping Over <?= htmlspecialchars($tenant['currency']) ?>150</span>
    <span><?= $h('announcement', 'Easy Returns') ?></span>
    <span>&#127760; Worldwide Delivery</span>
</div>
<nav class="am-nav">
    <div class="am-links"><a href="#shop">New In</a><a href="#categories">Shop</a><a href="#" class="am-links-hide">Bags</a><a href="#" class="am-links-hide">Shoes</a></div>
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="am-logo">
        <?= htmlspecialchars($tenant['business_name']) ?>
        <em>Timeless Elegance</em>
    </a>
    <div class="am-icons">
        <span id="store-search-toggle">&#128269;</span>
        <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="am-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>
<div class="am-search-wrap"><input id="store-search" placeholder="Search..."></div>

<section class="am-hero">
    <div class="am-hero-text">
        <span class="am-eyebrow"><?= $h('eyebrow', 'New Collection') ?></span>
        <h1><?= $h('hero_heading', 'Elegance') ?> <em><?= $h('hero_heading_2', 'Redefined') ?></em></h1>
        <p><?= $h('hero_subheading', 'Modern silhouettes. Quality materials. Designed for every unforgettable moment.') ?></p>
        <a href="#shop" class="btn-store">Shop The Collection &rarr;</a>
    </div>
    <div class="am-hero-image">
        <img src="<?= htmlspecialchars($heroImg) ?>" alt="">
        <div class="am-season-badge"><?= $h('season_badge', 'New Season') ?></div>
    </div>
</section>

<section class="am-cats" id="categories">
    <div class="am-section-head"><span class="am-line"></span><h2>Shop By Category</h2><span class="am-line"></span></div>
    <div class="am-cat-row">
        <?php $icons = ['&#128092;','&#128087;','&#128096;','&#128092;','&#9971;','&#128083;']; $i = 0; ?>
        <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
        <a href="?category_id=<?= (int) $cat['id'] ?>" class="am-cat-item"><span><?= $icons[$i++ % count($icons)] ?></span><?= htmlspecialchars($cat['name']) ?></a>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?><span class="text-muted">Add categories to show them here</span><?php endif; ?>
    </div>
</section>

<section class="am-products" id="shop">
    <div class="am-section-head"><span class="am-line"></span><h2><?= $h('products_heading', 'Best Sellers') ?></h2><span class="am-line"></span></div>
    <div id="cat-filter-list" class="am-cat-pills" data-mode="pills"></div>
    <div class="product-grid" id="product-grid"></div>
</section>

<section class="am-promo-row">
    <div class="am-promo-card" style="background-image:url('<?= htmlspecialchars($promo1Img) ?>')">
        <div class="am-promo-overlay"><h3><?= $h('promo1_heading', 'Timeless Pieces, Endless Possibilities') ?></h3><p><?= $h('promo1_subheading', 'Elevate your wardrobe with versatile staples.') ?></p><a href="#shop" class="am-outline-btn">Shop Now</a></div>
    </div>
    <div class="am-promo-card am-promo-solid">
        <h3><?= $h('promo2_heading', 'Enjoy 15% Off Your First Order') ?></h3>
        <p><?= $h('promo2_subheading', 'Sign up and be the first to know about new arrivals and offers.') ?></p>
        <a href="#shop" class="btn-store">Sign Up &amp; Save</a>
    </div>
</section>

<section class="am-quote-strip">
    <div class="am-section-head"><span class="am-line"></span><h2>Loved By Our Customers</h2><span class="am-line"></span></div>
    <div class="am-quote-row">
        <div class="am-quote-card"><span class="am-quote-mark">&#8220;</span><p><?= $h('quote1', 'A go-to for elevated essentials. The quality and attention to detail are unmatched.') ?></p></div>
        <div class="am-quote-card"><span class="am-quote-mark">&#8220;</span><p><?= $h('quote2', 'Every piece feels so refined. I always get compliments when I wear it.') ?></p></div>
        <div class="am-quote-card"><span class="am-quote-mark">&#8220;</span><p><?= $h('quote3', 'Beautiful designs and amazing customer service, every time.') ?></p></div>
    </div>
</section>

<section class="am-newsletter">
    <div><h3><?= $h('newsletter_heading', 'Stay in the Know') ?></h3><p><?= $h('newsletter_subheading', 'Subscribe for 15% off your first order and new arrivals.') ?></p></div>
    <form id="am-newsletter-form"><input type="email" placeholder="Enter your email address" required><button class="btn-store" type="submit">Subscribe</button></form>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>
StoreApp.renderProductList();
document.getElementById('store-search-toggle').addEventListener('click', () => document.querySelector('.am-search-wrap').classList.toggle('open'));
document.getElementById('am-newsletter-form').addEventListener('submit', (e) => { e.preventDefault(); e.target.reset(); alert('Thanks for subscribing!'); });
</script>
</body>
</html>
