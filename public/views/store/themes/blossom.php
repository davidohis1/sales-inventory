<?php
use App\Core\StockImages;
use App\Models\Category;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 800, 900);
$categories = Category::allForTenant((int) $tenant['id']);
$promoImg = !empty($content['promo1_path']) ? $base . $content['promo1_path'] : StockImages::url($storeType, 5, 900, 300);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/blossom.css">
</head>
<body class="theme-blossom"<?= \App\Core\ThemePalettes::styleAttr('blossom', $content['color_theme'] ?? 'signature') ?>>
<nav class="bl-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="bl-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="bl-logo-img"><?php else: ?><span class="bl-logo-mark">&#10047;</span><?php endif; ?>
        <span><?= htmlspecialchars($tenant['business_name']) ?></span>
    </a>
    <div class="bl-links"><a href="#" class="active">Home</a><a href="#categories">Categories</a><a href="#shop">Offers</a><a href="#about">About</a></div>
    <div class="bl-nav-right">
        <input id="store-search" class="bl-search" placeholder="Search for products...">
        <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="bl-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>

<section class="bl-hero">
    <div class="bl-hero-text">
        <span class="bl-eyebrow"><?= $h('eyebrow', 'Radiate Confidence Every Day') ?></span>
        <h1><?= $h('hero_heading', 'Beauty & Wellness for a Better You') ?></h1>
        <p><?= $h('hero_subheading', 'Discover premium products for a healthier, more radiant everyday routine.') ?></p>
        <div class="bl-hero-actions"><a href="#shop" class="btn-store">Shop Now &rarr;</a><a href="#categories" class="bl-outline-btn">Explore Categories &#8942;&#8942;</a></div>
    </div>
    <div class="bl-hero-image">
        <img src="<?= htmlspecialchars($heroImg) ?>" alt="">
        <div class="bl-badge"><strong><?= $h('badge_percent', '100%') ?></strong><span><?= $h('badge_text', 'Original Products') ?></span></div>
    </div>
</section>

<section class="bl-trust">
    <div><span>&#128737;</span><div><strong><?= $h('badge_percent', '100%') ?> Original</strong><em>Authentic &amp; trusted</em></div></div>
    <div><span>&#128100;</span><div><strong>Expert Approved</strong><em>Carefully tested</em></div></div>
    <div><span>&#128666;</span><div><strong>Fast Delivery</strong><em>At your doorstep</em></div></div>
    <div><span>&#128260;</span><div><strong>Easy Returns</strong><em>Hassle-free</em></div></div>
</section>

<section class="bl-cats" id="categories">
    <div class="bl-section-head"><h2>Shop by Category</h2><a href="#shop" class="bl-view-all">View All Categories &rarr;</a></div>
    <div class="bl-cat-grid">
        <?php $i = 0; foreach (array_slice($categories, 0, 5) as $cat): ?>
        <a href="?category_id=<?= (int) $cat['id'] ?>" class="bl-cat-tile">
            <div class="bl-cat-thumb" style="background-image:url('<?= htmlspecialchars(StockImages::url($storeType, $i + 1, 300, 300)) ?>')"></div>
            <strong><?= htmlspecialchars($cat['name']) ?></strong>
        </a>
        <?php $i++; endforeach; ?>
        <?php if (empty($categories)): ?><span class="text-muted">Add categories to show them here</span><?php endif; ?>
    </div>
</section>

<section class="bl-promo">
    <div class="bl-promo-inner" style="background-image:url('<?= htmlspecialchars($promoImg) ?>')">
        <span class="bl-eyebrow" style="color:#fff;">&#9889; <?= $h('promo_tag', 'Limited Time Offer') ?></span>
        <h2><?= $h('promo_heading', 'Up to 30% Off') ?></h2>
        <p><?= $h('promo_subheading', 'On top brands, for a limited time only.') ?></p>
        <a href="#shop" class="btn-store">Shop The Sale &rarr;</a>
    </div>
</section>

<section class="bl-products" id="shop">
    <div class="bl-section-head"><h2><?= $h('products_heading', 'Best Sellers') ?></h2><span id="result-count" class="text-muted"></span></div>
    <div id="cat-filter-list" class="bl-cat-pills" data-mode="pills"></div>
    <div class="product-grid" id="product-grid"></div>
</section>

<section class="bl-features">
    <div><strong>&#128179; Secure Payments</strong><span>100% safe &amp; secure</span></div>
    <div><strong>&#127873; Exclusive Offers</strong><span>Save more on top brands</span></div>
    <div><strong>&#127911; 24/7 Support</strong><span>We're here to help</span></div>
    <div><strong>&#127942; Loyalty Rewards</strong><span>Earn points &amp; get gifts</span></div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>StoreApp.renderProductList();</script>
</body>
</html>
