<?php
use App\Core\StockImages;
use App\Models\Category;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 800, 1000);
$categories = Category::allForTenant((int) $tenant['id']);
$promoImg = !empty($content['promo1_path']) ? $base . $content['promo1_path'] : StockImages::url($storeType, 5, 700, 500);
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
    <span>&#128666; Free Shipping on Orders Over <?= htmlspecialchars($tenant['currency']) ?>50</span>
    <div class="vd-topbar-links"><a href="#shop">Track Order</a><a href="#faq">FAQ</a></div>
</div>
<nav class="vd-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="vd-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="vd-logo-img"><?php else: ?><span class="vd-logo-mark">&#127807;</span><?php endif; ?>
        <span><?= htmlspecialchars($tenant['business_name']) ?></span>
    </a>
    <div class="vd-links"><a href="#" class="active">Home</a><a href="#shop">Shop</a><a href="#categories">Categories</a><a href="#about">About</a></div>
    <div class="vd-icons">
        <span id="store-search-toggle">&#128269;</span>
        <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="vd-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>
<div class="vd-search-wrap"><input id="store-search" placeholder="Search products..."></div>

<section class="vd-hero">
    <div class="vd-hero-text">
        <span class="vd-eyebrow"><?= $h('eyebrow', 'Naturally Radiant') ?></span>
        <h1><?= $h('hero_heading', 'Quality that cares, service that shines.') ?></h1>
        <p><?= $h('hero_subheading', 'Discover the perfect blend of care and quality for a better everyday experience.') ?></p>
        <a href="#shop" class="btn-store">Shop Now &rarr;</a>
    </div>
    <div class="vd-hero-image"><img src="<?= htmlspecialchars($heroImg) ?>" alt=""></div>
</section>

<section class="vd-trust">
    <div><span>&#127807;</span><div><strong>Quality Ingredients</strong><em>Safe &amp; effective</em></div></div>
    <div><span>&#128172;</span><div><strong>Expert Approved</strong><em>Trusted by professionals</em></div></div>
    <div><span>&#9989;</span><div><strong>Satisfaction Guaranteed</strong><em>Clean &amp; simple</em></div></div>
    <div><span>&#128007;</span><div><strong>Ethically Made</strong><em>Responsibly sourced</em></div></div>
</section>

<section class="vd-cats" id="categories">
    <div class="vd-section-head"><span class="vd-line"></span><h2>Shop By Category</h2><span class="vd-line"></span></div>
    <div class="vd-cat-row">
        <?php $icons = ['&#128167;','&#127839;','&#129529;','&#128168;','&#128064;','&#9728;']; $i = 0; ?>
        <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
        <a href="?category_id=<?= (int) $cat['id'] ?>" class="vd-cat-item"><span class="vd-cat-circle"><?= $icons[$i++ % count($icons)] ?></span><?= htmlspecialchars($cat['name']) ?><em>Explore</em></a>
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
    <div class="vd-promo-image" style="background-image:url('<?= htmlspecialchars($promoImg) ?>')"></div>
    <div class="vd-promo-text">
        <span class="vd-eyebrow">Special Offer</span>
        <h2><?= $h('promo_heading', 'Get 20% Off Your First Order') ?></h2>
        <p><?= $h('promo_subheading', "Join our club and unlock exclusive offers and tips.") ?></p>
        <form id="vd-newsletter-form" class="vd-newsletter-form"><input type="email" placeholder="Enter your email" required><button class="btn-store" type="submit">Join Now</button></form>
    </div>
</section>

<section class="vd-features">
    <div><strong>&#128666; Free Shipping</strong><span>On orders over <?= htmlspecialchars($tenant['currency']) ?>50</span></div>
    <div><strong>&#128274; Secure Payment</strong><span>100% safe &amp; secure</span></div>
    <div><strong>&#128260; Easy Returns</strong><span>30 days return policy</span></div>
    <div><strong>&#127911; Customer Support</strong><span>We're here to help</span></div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>
StoreApp.renderProductList();
document.getElementById('store-search-toggle').addEventListener('click', () => document.querySelector('.vd-search-wrap').classList.toggle('open'));
document.getElementById('vd-newsletter-form').addEventListener('submit', (e) => { e.preventDefault(); e.target.reset(); alert('Thanks for joining!'); });
</script>
</body>
</html>
