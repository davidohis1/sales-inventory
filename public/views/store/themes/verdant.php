<?php
use App\Core\StockImages;
use App\Models\Category;
require_once __DIR__ . '/../partials/verdant-icons.php';
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 800, 1000);
$categories = Category::allForTenant((int) $tenant['id']);
$promoImg = !empty($content['promo1_path']) ? $base . $content['promo1_path'] : StockImages::url($storeType, 5, 700, 500);
$vdTagline = vd_store_tagline($storeType);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/verdant.css">
</head>
<body class="theme-verdant"<?= \App\Core\ThemePalettes::styleAttr('verdant', $content['color_theme'] ?? 'signature') ?>>
<div class="vd-topbar">
    <span>&#128666; <?= $h('topbar_text', 'Free Shipping on Orders Over ' . $tenant['currency'] . '50') ?></span>
    <div class="vd-topbar-links"><a href="#shop">Track Order</a><a href="#faq">FAQ</a><a href="#shop">Store Locator</a><a href="#contact">Contact Us</a></div>
</div>
<nav class="vd-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="vd-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="vd-logo-img"><?php else: ?><span class="vd-logo-mark">&#127807;</span><?php endif; ?>
        <span class="vd-logo-text"><span class="vd-logo-name"><?= htmlspecialchars($tenant['business_name']) ?></span><span class="vd-logo-tagline"><?= htmlspecialchars($vdTagline) ?></span></span>
    </a>
    <div class="vd-links"><a href="#" class="active">Home</a><a href="#shop">Shop</a><a href="#categories">Categories</a><a href="#about">About</a></div>
    <div class="vd-icons">
        <span id="store-search-toggle" title="Search">&#128269;</span>
        <span class="vd-account-icon" title="Account"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg></span>
        <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="vd-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>
<div class="vd-search-wrap"><input id="store-search" placeholder="Search products..."></div>

<section class="vd-hero">
    <div class="vd-hero-text">
        <span class="vd-eyebrow"><?= $h('eyebrow', 'Naturally Radiant') ?></span>
        <h1><?= $h('hero_heading', 'Quality that cares, service that shines.') ?></h1>
        <p><?= $h('hero_subheading', 'Discover the perfect blend of care and quality for a better everyday experience.') ?></p>
        <div class="vd-hero-ctas">
            <a href="#shop" class="btn-store">Shop Now &rarr;</a>
            <span class="vd-watch-video"><span class="vd-play-circle">&#9654;</span> Watch Video</span>
        </div>
    </div>
    <div class="vd-hero-image"><img src="<?= htmlspecialchars($heroImg) ?>" alt=""></div>
</section>

<section class="vd-trust">
    <div><span><?= vd_icon('quality') ?></span><div><strong><?= $h('trust1_heading', 'Quality Guaranteed') ?></strong><em><?= $h('trust1_text', 'Checked before it ships') ?></em></div></div>
    <div><span><?= vd_icon('chat') ?></span><div><strong><?= $h('trust2_heading', 'Trusted Service') ?></strong><em><?= $h('trust2_text', 'Here whenever you need us') ?></em></div></div>
    <div><span><?= vd_icon('truck') ?></span><div><strong><?= $h('trust3_heading', 'Fast Delivery') ?></strong><em><?= $h('trust3_text', 'Straight to your door') ?></em></div></div>
    <div><span><?= vd_icon('returns') ?></span><div><strong><?= $h('trust4_heading', 'Easy Returns') ?></strong><em><?= $h('trust4_text', 'Hassle-free, every time') ?></em></div></div>
</section>

<section class="vd-cats" id="categories">
    <div class="vd-section-head"><span class="vd-line"></span><h2>Shop By Category</h2><span class="vd-line"></span></div>
    <div class="vd-cat-row">
        <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
        <a href="?category_id=<?= (int) $cat['id'] ?>" class="vd-cat-item"><span class="vd-cat-circle"><?= vd_category_icon($cat['name']) ?></span><?= htmlspecialchars($cat['name']) ?><em>Explore</em></a>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?><span class="text-muted">Add categories to show them here</span><?php endif; ?>
    </div>
</section>

<section class="vd-products" id="shop">
    <div class="vd-section-head"><h2><?= $h('products_heading', 'Featured Products') ?></h2><a href="#shop" class="vd-view-all">View All &rarr;</a></div>
    <div id="cat-filter-list" class="vd-cat-pills" data-mode="pills"></div>
    <div class="product-grid" id="product-grid"></div>
</section>

<section class="vd-promo">
    <div class="vd-promo-image" style="background-image:url('<?= htmlspecialchars($promoImg) ?>')"><div class="vd-promo-image-tint"></div></div>
    <div class="vd-promo-text">
        <span class="vd-eyebrow">Special Offer</span>
        <h2><?= $h('promo_heading', 'Get 20% Off Your First Order') ?></h2>
        <p><?= $h('promo_subheading', "Join our club and unlock exclusive offers and tips.") ?></p>
    </div>
    <form id="vd-newsletter-form" class="vd-promo-join">
        <span class="vd-promo-join-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 6l10 7 10-7"/></svg></span>
        <input type="email" placeholder="Enter your email" required>
        <button class="btn-store" type="submit">Join Now</button>
    </form>
</section>

<section class="vd-features">
    <div><strong><?= vd_icon('truck') ?> Free Shipping</strong><span>On orders over <?= htmlspecialchars($tenant['currency']) ?>50</span></div>
    <div><strong><?= vd_icon('shield') ?> Secure Payment</strong><span>100% safe &amp; secure</span></div>
    <div><strong><?= vd_icon('returns') ?> Easy Returns</strong><span>30 days return policy</span></div>
    <div><strong><?= vd_icon('headset') ?> Customer Support</strong><span>We're here to help</span></div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script src="<?= $base ?>/assets/js/themes/verdant-renderer.js"></script>
<script>
StoreApp.renderProductList();
document.getElementById('store-search-toggle').addEventListener('click', () => document.querySelector('.vd-search-wrap').classList.toggle('open'));
document.getElementById('vd-newsletter-form').addEventListener('submit', (e) => { e.preventDefault(); e.target.reset(); alert('Thanks for joining!'); });
</script>
</body>
</html>
