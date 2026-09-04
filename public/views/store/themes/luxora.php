<?php
use App\Core\StockImages;
use App\Models\Category;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 900, 1100);
$catImgs = StockImages::bank($storeType, 4, 700, 850);
$promo1Img = !empty($content['promo1_path']) ? $base . $content['promo1_path'] : StockImages::url($storeType, 5, 700, 500);
$promo2Img = !empty($content['promo2_path']) ? $base . $content['promo2_path'] : StockImages::url($storeType, 6, 700, 500);
$newsletterImg = !empty($content['newsletter_path']) ? $base . $content['newsletter_path'] : StockImages::url($storeType, 7, 700, 700);
$categories = Category::allForTenant((int) $tenant['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/store.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/themes/luxora.css">
</head>
<body class="theme-luxora">
<nav class="lx-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="lx-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="lx-logo-img"><?php endif; ?>
        <?= htmlspecialchars($tenant['business_name']) ?>
    </a>
    <div class="lx-links"><a href="#shop">Shop</a><a href="#categories">Categories</a><a href="#bestsellers">Best Sellers</a></div>
    <div class="lx-nav-actions">
        <input id="store-search" class="lx-search" placeholder="Search...">
        <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="lx-cart">&#128722; <span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>

<section class="lx-hero">
    <div class="lx-hero-text">
        <span class="lx-eyebrow"><?= $h('eyebrow', 'NEW COLLECTION') ?></span>
        <h1><?= $h('hero_heading', 'Elevate Your Everyday Style') ?></h1>
        <p><?= $h('hero_subheading', 'Discover timeless pieces crafted for comfort, designed for elegance, made for you.') ?></p>
        <div class="lx-hero-actions">
            <a href="#shop" class="btn-store">Shop Now &rarr;</a>
            <a href="#" class="lx-watch">&#9658; Watch Lookbook</a>
        </div>
        <div class="lx-trust-row">
            <div><strong>Free Shipping</strong><span>On orders over <?= htmlspecialchars($tenant['currency']) ?>99</span></div>
            <div><strong>Easy Returns</strong><span>30-day returns</span></div>
            <div><strong>Secure Payment</strong><span>100% protected</span></div>
        </div>
    </div>
    <div class="lx-hero-image">
        <img src="<?= htmlspecialchars($heroImg) ?>" alt="">
    </div>
</section>

<section class="lx-cat-strip" id="categories">
    <?php $catIcons = ['&#128092;','&#128100;','&#128087;','&#128085;','&#128096;','&#128092;','&#127913;']; $i = 0; ?>
    <?php foreach (array_slice($categories, 0, 7) as $cat): ?>
        <a href="?category_id=<?= (int) $cat['id'] ?>" class="lx-cat-icon">
            <span class="lx-cat-circle"><?= $catIcons[$i++ % count($catIcons)] ?></span>
            <?= htmlspecialchars($cat['name']) ?>
        </a>
    <?php endforeach; ?>
    <?php if (empty($categories)): ?><span class="text-muted" style="padding:10px 20px;">Add categories to show them here</span><?php endif; ?>
    <a href="#shop" class="lx-cat-icon lx-cat-sale"><span class="lx-cat-circle lx-cat-circle-dark">SALE</span>Sale</a>
</section>

<section class="lx-find-style">
    <div class="lx-section-head">
        <div><span class="lx-eyebrow">SHOP BY CATEGORY</span><h2><?= $h('find_style_heading', "Find Your Perfect Style") ?></h2></div>
        <a href="#shop" class="lx-view-all">View All Categories &rarr;</a>
    </div>
    <div class="lx-cat-grid">
        <?php foreach (array_slice($catImgs, 0, 4) as $i => $img): $names = ['Women\'s Collection', 'Men\'s Collection', 'New Arrivals', 'Accessories']; ?>
        <a href="#shop" class="lx-cat-tile" style="background-image:url('<?= htmlspecialchars($img) ?>')">
            <span><?= htmlspecialchars($names[$i]) ?><em>Explore Now &rarr;</em></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="lx-promo-row">
    <div class="lx-promo-card" style="background-image:url('<?= htmlspecialchars($promo1Img) ?>')">
        <div class="lx-promo-text"><span>LIMITED TIME OFFER</span><strong><?= $h('promo1_heading', 'Spring Sale Up to 50% Off') ?></strong><a href="#shop" class="lx-promo-btn">Shop The Sale &rarr;</a></div>
    </div>
    <div class="lx-promo-card" style="background-image:url('<?= htmlspecialchars($promo2Img) ?>')">
        <div class="lx-promo-text"><span>NEW ARRIVALS</span><strong><?= $h('promo2_heading', 'Fresh Styles Just Landed') ?></strong><a href="#shop" class="lx-promo-btn">Explore New In &rarr;</a></div>
    </div>
</section>

<section class="lx-shop" id="shop">
    <div class="lx-shop-head">
        <div><span class="lx-eyebrow">BEST SELLERS</span><h2 id="bestsellers"><?= $h('bestsellers_heading', "Our Most Loved Picks") ?></h2></div>
        <div id="cat-filter-list" class="lx-cat-pills" data-mode="pills"></div>
    </div>
    <div class="product-grid" id="product-grid"></div>
</section>

<section class="lx-features">
    <div><strong>&#128666; Free Shipping</strong><span>On orders over <?= htmlspecialchars($tenant['currency']) ?>99</span></div>
    <div><strong>&#128230; Easy Returns</strong><span>30-day returns</span></div>
    <div><strong>&#128274; Secure Payment</strong><span>100% protected</span></div>
    <div><strong>&#127911; 24/7 Support</strong><span>We're here to help</span></div>
</section>

<section class="lx-newsletter">
    <div class="lx-newsletter-img" style="background-image:url('<?= htmlspecialchars($newsletterImg) ?>')"></div>
    <div class="lx-newsletter-form">
        <span class="lx-eyebrow">GET 10% OFF YOUR FIRST ORDER</span>
        <h3><?= $h('newsletter_heading', "Join Our Style List") ?></h3>
        <p><?= $h('newsletter_subheading', "Sign up for exclusive offers, new arrivals, and style inspiration.") ?></p>
        <form id="lx-newsletter-form"><input type="email" placeholder="Enter your email" required><button class="btn-store" type="submit">Subscribe</button></form>
    </div>
</section>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<div class="toast-container" id="toast-container"></div>
<script>window.APP_BASE = <?= json_encode($base) ?>; window.TENANT_SLUG = <?= json_encode($slug) ?>; window.TENANT_CURRENCY = <?= json_encode($tenant['currency']) ?>;</script>
<script src="<?= $base ?>/assets/js/store.js"></script>
<script>
StoreApp.renderProductList();
document.getElementById('lx-newsletter-form').addEventListener('submit', (e) => { e.preventDefault(); e.target.reset(); alert('Thanks for subscribing!'); });
</script>
</body>
</html>
