<?php
use App\Core\StockImages;
use App\Models\Category;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 800, 900);
$categories = Category::allForTenant((int) $tenant['id']);
$promoImg = !empty($content['promo1_path']) ? $base . $content['promo1_path'] : StockImages::url($storeType, 5, 1200, 500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/radiance.css">
</head>
<body class="theme-radiance"<?= \App\Core\ThemePalettes::styleAttr('radiance', $content['color_theme'] ?? 'signature') ?>>
<nav class="rd-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="rd-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="rd-logo-img"><?php else: ?><span class="rd-logo-mark">&#10022;</span><?php endif; ?>
        <?= htmlspecialchars($tenant['business_name']) ?>
    </a>
    <div class="rd-links"><a href="#" class="active">Home</a><a href="#shop">Shop</a><a href="#categories">Categories</a></div>
    <div class="rd-nav-actions">
        <input id="store-search" class="rd-search" placeholder="Search for products...">
        <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="rd-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>

<section class="rd-hero">
    <div class="rd-hero-text">
        <span class="rd-eyebrow"><?= $h('eyebrow', 'New Collection') ?></span>
        <h1><?= $h('hero_heading', 'Glow From Within &amp; Without') ?></h1>
        <p><?= $h('hero_subheading', 'Discover premium products crafted for a healthier, more radiant everyday routine.') ?></p>
        <div class="rd-hero-actions"><a href="#shop" class="btn-store">Shop Now &rarr;</a><a href="#categories" class="rd-outline-btn">Explore Categories</a></div>
    </div>
    <div class="rd-hero-image">
        <img src="<?= htmlspecialchars($heroImg) ?>" alt="">
        <div class="rd-badge"><span>&#10024;</span><?= $h('badge_text', 'Loved By Thousands') ?></div>
    </div>
</section>

<section class="rd-trust">
    <div><span>&#128666;</span><div><strong><?= $h('trust1_heading', 'Free Delivery') ?></strong><em><?= $h('trust1_text', 'On qualifying orders') ?></em></div></div>
    <div><span>&#128737;</span><div><strong><?= $h('trust2_heading', 'Authentic Products') ?></strong><em><?= $h('trust2_text', '100% genuine, always') ?></em></div></div>
    <div><span>&#128260;</span><div><strong><?= $h('trust3_heading', 'Easy Returns') ?></strong><em><?= $h('trust3_text', 'Hassle-free process') ?></em></div></div>
    <div><span>&#127911;</span><div><strong><?= $h('trust4_heading', '24/7 Support') ?></strong><em><?= $h('trust4_text', "We're here to help") ?></em></div></div>
</section>

<section class="rd-cats" id="categories">
    <div class="rd-section-head"><h2><?= $h('categories_heading', 'Shop by Category') ?></h2><a href="#shop" class="rd-view-all">View All &rarr;</a></div>
    <div class="rd-cat-grid">
        <?php $i = 0; foreach (array_slice($categories, 0, 5) as $cat): ?>
        <a href="?category_id=<?= (int) $cat['id'] ?>" class="rd-cat-tile">
            <div class="rd-cat-thumb" style="background-image:url('<?= htmlspecialchars(StockImages::url($storeType, $i + 1, 400, 400)) ?>')"></div>
            <strong><?= htmlspecialchars($cat['name']) ?></strong>
        </a>
        <?php $i++; endforeach; ?>
        <?php if (empty($categories)): ?><span class="text-muted">Add categories to show them here</span><?php endif; ?>
    </div>
</section>

<section class="rd-products" id="shop">
    <div class="rd-section-head"><h2><?= $h('arrivals_heading', 'New Arrivals') ?></h2><span id="result-count" class="text-muted"></span></div>
    <div id="cat-filter-list" class="rd-cat-pills" data-mode="pills"></div>
    <div class="product-grid" id="product-grid"></div>
</section>

<section class="rd-promo">
    <div class="rd-promo-inner" style="background-image:url('<?= htmlspecialchars($promoImg) ?>')">
        <span class="rd-eyebrow" style="color:#fff;">&#10022; Featured</span>
        <h2><?= $h('promo_heading', 'Discover Your Perfect Match') ?></h2>
        <p><?= $h('promo_subheading', 'Curated picks chosen for quality, for a limited time only.') ?></p>
        <a href="#shop" class="btn-store">Shop The Edit &rarr;</a>
    </div>
</section>

<section class="rd-quotes">
    <div class="rd-section-head rd-section-head-center"><h2>What Our Customers Say</h2></div>
    <div class="rd-quote-row">
        <div class="rd-quote-card"><div class="rd-quote-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div><p><?= $h('quote1', 'My skin has never looked this good! I noticed a difference within weeks.') ?></p></div>
        <div class="rd-quote-card"><div class="rd-quote-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div><p><?= $h('quote2', 'Finally found products that actually work for me. Will definitely reorder.') ?></p></div>
        <div class="rd-quote-card"><div class="rd-quote-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div><p><?= $h('quote3', 'The quality and attention to detail are unmatched. Highly recommend!') ?></p></div>
    </div>
</section>

<section class="rd-newsletter">
    <div>
        <h3><?= $h('newsletter_heading', 'Get Tips &amp; Exclusive Deals') ?></h3>
        <p><?= $h('newsletter_subheading', 'Join our list for weekly tips, early access and exclusive offers.') ?></p>
    </div>
    <form id="rd-newsletter-form"><input type="email" placeholder="Enter your email address" required><button class="btn-store" type="submit">Subscribe</button></form>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script src="<?= $base ?>/assets/js/themes/radiance-renderer.js"></script>
<script>
StoreApp.renderProductList();
document.getElementById('rd-newsletter-form').addEventListener('submit', (e) => { e.preventDefault(); e.target.reset(); alert('Thanks for subscribing!'); });
</script>
</body>
</html>
