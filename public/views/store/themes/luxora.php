<?php
use App\Core\StockImages;
$content = $settings['content'] ?? [];
$h = fn ($k, $d) => htmlspecialchars($content[$k] ?? $d);
$storeType = $settings['store_type'] ?? 'general';
$heroImg = !empty($content['banner_path']) ? $base . $content['banner_path'] : StockImages::url($storeType, 0, 900, 1100);
$banner1 = StockImages::url($storeType, 1, 700, 500);
$banner2 = StockImages::url($storeType, 2, 700, 500);
$banner3 = StockImages::url($storeType, 3, 700, 500);
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
<div class="lx-announce">Free shipping on orders over <?= htmlspecialchars($tenant['currency']) ?>75 &middot; Easy 30-day returns</div>
<nav class="lx-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>" class="lx-logo">
        <?php if (!empty($content['logo_path'])): ?><img src="<?= $base . htmlspecialchars($content['logo_path']) ?>" alt="" class="lx-logo-img"><?php endif; ?>
        <?= htmlspecialchars($tenant['business_name']) ?>
    </a>
    <div class="lx-links"><a href="#shop">Shop</a><a href="#bestsellers">Best Sellers</a><a href="#about">About</a></div>
    <div class="lx-nav-actions">
        <input id="store-search" class="lx-search" placeholder="Search...">
        <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart" class="lx-cart">&#128722; <span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>

<section class="lx-hero">
    <div class="lx-hero-text">
        <span class="lx-eyebrow">— <?= $h('eyebrow', 'NEW SEASON') ?></span>
        <h1><?= $h('hero_heading', 'Quality that speaks for you.') ?></h1>
        <p><?= $h('hero_subheading', 'Discover great products made for every moment, every mood, every you.') ?></p>
        <a href="#shop" class="btn-store">Shop Now &rarr;</a>
    </div>
    <div class="lx-hero-image">
        <img src="<?= htmlspecialchars($heroImg) ?>" alt="">
        <div class="lx-hero-badge"><?= $h('promo_badge', 'Up to 40% Off') ?></div>
    </div>
</section>

<section class="lx-promo-row">
    <div class="lx-promo-card" style="background-image:url('<?= htmlspecialchars($banner1) ?>')"><div class="lx-promo-text"><span>New Arrivals</span><strong>Fresh styles just landed</strong></div></div>
    <div class="lx-promo-card" style="background-image:url('<?= htmlspecialchars($banner2) ?>')"><div class="lx-promo-text"><span>Season Sale</span><strong>Up to 40% off sitewide</strong></div></div>
    <div class="lx-promo-card" style="background-image:url('<?= htmlspecialchars($banner3) ?>')"><div class="lx-promo-text"><span>Member Exclusive</span><strong>Extra 10% off for members</strong></div></div>
</section>

<section class="lx-shop" id="shop">
    <div class="lx-shop-head">
        <h2 id="bestsellers">Shop the Collection</h2>
        <div id="cat-filter-list" class="lx-cat-pills" data-mode="pills"></div>
    </div>
    <div class="product-grid" id="product-grid"></div>
</section>

<section class="lx-testimonials" id="about">
    <h2>What Our Customers Say</h2>
    <div class="lx-testimonial-row">
        <blockquote>"The quality, fit, and style are always amazing. This is my go-to for every season."</blockquote>
        <blockquote>"Love the designs and how fast my order arrives. Highly recommend!"</blockquote>
        <blockquote>"Beautiful pieces at great prices. Customer service is excellent too."</blockquote>
    </div>
</section>

<section class="lx-newsletter">
    <div>
        <h3>Join the <?= htmlspecialchars($tenant['business_name']) ?> Community</h3>
        <p>Subscribe now and get 10% off your first order.</p>
    </div>
    <form id="lx-newsletter-form"><input type="email" placeholder="Enter your email address" required><button class="btn-store" type="submit">Subscribe</button></form>
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
