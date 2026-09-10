<?php
/**
 * Shared themed page chrome — the exact topbar/nav/subnav markup from each
 * theme's homepage, minus the hero and homepage-only sections, so that the
 * product page, the shop/all-products page, the cart and the checkout page
 * all open with the *real* nav of whichever theme the tenant picked instead
 * of a generic bar. Include this right after <body ...>, then close whatever
 * it opens (see $themeHeaderOpensShell below) before your own content.
 *
 * Expects in scope: $tenant, $slug, $base, $settings, $theme, $content, $h
 * (all already set by renderStore()/the including page before this include).
 *
 * Sets $themeHeaderOpensShell = true for the one theme (aurora) whose nav is
 * immediately followed by a sidebar-shell wrapper on the homepage — callers
 * that want that sidebar (the shop page) can open <aside class="ar-sidebar">
 * themselves; callers that don't (the product page) should close the shell
 * right away with a plain <div class="ar-shell"><main> so the page still
 * lays out correctly without a sidebar.
 */
$storeUrl = $base . '/' . htmlspecialchars($slug);
$logoImg = !empty($content['logo_path']) ? $base . htmlspecialchars($content['logo_path']) : null;
$bizName = htmlspecialchars($tenant['business_name']);
$themeHeaderOpensShell = ($theme === 'aurora');
if ($theme === 'verdant') {
    require_once __DIR__ . '/verdant-icons.php';
    $vdTagline = vd_store_tagline($settings['store_type'] ?? 'general');
}
?>
<?php switch ($theme):
    case 'aurora': ?>
<div class="ar-topbar">
    <span>&#128666; <?= $h('announcement', 'Free Delivery on Orders Over ' . $tenant['currency'] . '50') ?></span>
    <span>&#128260; 30-Day Easy Returns</span>
    <span class="ar-topbar-hide">&#127911; 24/7 Customer Support</span>
</div>
<header class="ar-header">
    <a href="<?= $storeUrl ?>" class="ar-logo">
        <?php if ($logoImg): ?><img src="<?= $logoImg ?>" alt="" class="ar-logo-img"><?php else: ?><span class="ar-logo-mark">&#128722;</span><?php endif; ?>
        <span><?= $bizName ?><em>Shop More, Save More</em></span>
    </a>
    <div class="ar-search-wrap"><input id="store-search" class="ar-search" placeholder="Search for products, brands and more..."><button class="ar-search-btn">&#128269;</button></div>
    <a href="<?= $storeUrl ?>/cart" class="ar-cart">&#128722; <span class="cart-count" id="cart-count">0</span> My Cart</a>
</header>
<nav class="ar-subnav">
    <span class="ar-cat-dropdown">&#9776; All Categories</span>
    <div class="ar-sublinks"><a href="<?= $storeUrl ?>">Home</a><a href="<?= $storeUrl ?>/shop" class="<?= ($page ?? '') === 'shop' ? 'active' : '' ?>">Shop</a><a href="<?= $storeUrl ?>/shop">Categories</a><a href="<?= $storeUrl ?>/shop">Deals</a></div>
    <span class="ar-hot-offers">&#128293; Hot Offers</span>
</nav>
    <?php break;
    case 'wink': ?>
<div class="wk-topbar">
    <span>&#128666; Free Shipping — On orders over <?= htmlspecialchars($tenant['currency']) ?>75</span>
    <span>&#127991; Extra 10% Off — On prepaid orders</span>
</div>
<nav class="wk-nav">
    <a href="<?= $storeUrl ?>" class="wk-logo">
        <?php if ($logoImg): ?><img src="<?= $logoImg ?>" alt="" class="wk-logo-img"><?php else: ?><span class="wk-logo-icon">&#128717;</span><?php endif; ?>
        <?= $bizName ?>
    </a>
    <div class="wk-links"><a href="<?= $storeUrl ?>/shop">New In</a><a href="<?= $storeUrl ?>/shop">Categories</a><a href="<?= $storeUrl ?>/shop">Deals</a></div>
    <a href="<?= $storeUrl ?>/shop" class="wk-shop-btn">Shop Now</a>
</nav>
    <?php break;
    case 'luxora': ?>
<nav class="lx-nav">
    <a href="<?= $storeUrl ?>" class="lx-logo">
        <?php if ($logoImg): ?><img src="<?= $logoImg ?>" alt="" class="lx-logo-img"><?php endif; ?>
        <?= $bizName ?>
    </a>
    <div class="lx-links"><a href="<?= $storeUrl ?>/shop">Shop</a><a href="<?= $storeUrl ?>/shop">Categories</a><a href="<?= $storeUrl ?>/shop">Best Sellers</a></div>
    <div class="lx-nav-actions">
        <input id="store-search" class="lx-search" placeholder="Search...">
        <a href="<?= $storeUrl ?>/cart" class="lx-cart">&#128722; <span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>
    <?php break;
    case 'marketly': ?>
<div class="mk-topbar">Enterprise-level commerce platform &middot; <a href="<?= $storeUrl ?>/cart">Track Order</a></div>
<header class="mk-header">
    <a href="<?= $storeUrl ?>" class="mk-logo">
        <?php if ($logoImg): ?><img src="<?= $logoImg ?>" alt="" class="mk-logo-img"><?php endif; ?>
        <?= $bizName ?>
    </a>
    <span class="mk-cat-btn">&#9776; Category</span>
    <div class="mk-search-wrap"><span class="mk-ai-tag">&#10024; AI</span><input id="store-search" placeholder="AI-powered search..."></div>
    <div class="mk-header-icons">
        <span>&#9825;</span>
        <span>&#128276;</span>
        <a href="<?= $storeUrl ?>/cart" class="mk-cart">&#128722; <span class="cart-count" id="cart-count">0</span></a>
    </div>
</header>
<nav class="mk-subnav">
    <span>&#9989; Free Shipping</span>
    <span>&#128179; Secure Payment</span>
    <span>&#128260; Easy Returns</span>
    <span>&#127911; 24/7 Support</span>
</nav>
    <?php break;
    case 'novatrend': ?>
<div class="nt-topbar">
    <span>&#128666; Free Worldwide Shipping Over <?= htmlspecialchars($tenant['currency']) ?>50</span>
    <span>&#127991; <?= $h('announcement', 'Summer Sale Up to 70% Off') ?></span>
    <span>&#9889; Limited Time Flash Deals</span>
</div>
<nav class="nt-nav">
    <a href="<?= $storeUrl ?>" class="nt-logo">
        <?php if ($logoImg): ?><img src="<?= $logoImg ?>" alt="" class="nt-logo-img"><?php endif; ?>
        <?= $bizName ?>
    </a>
    <div class="nt-links"><a href="<?= $storeUrl ?>">Home</a><a href="<?= $storeUrl ?>/shop" class="<?= ($page ?? '') === 'shop' ? 'active' : '' ?>">Shop</a><a href="<?= $storeUrl ?>/shop">Categories</a><a href="<?= $storeUrl ?>/shop">Best Sellers</a></div>
    <div class="nt-icons">
        <span id="store-search-toggle">&#128269;</span>
        <a href="<?= $storeUrl ?>/cart" class="nt-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>
<div class="nt-search-wrap"><input id="store-search" placeholder="Search products..."></div>
    <?php break;
    case 'verdant': ?>
<div class="vd-topbar">
    <span>&#128666; <?= $h('topbar_text', 'Free Shipping on Orders Over ' . $tenant['currency'] . '50') ?></span>
    <div class="vd-topbar-links"><a href="<?= $storeUrl ?>/cart">Track Order</a><a href="<?= $storeUrl ?>/shop">FAQ</a><a href="<?= $storeUrl ?>/shop">Store Locator</a><a href="<?= $storeUrl ?>">Contact Us</a></div>
</div>
<nav class="vd-nav">
    <a href="<?= $storeUrl ?>" class="vd-logo">
        <?php if ($logoImg): ?><img src="<?= $logoImg ?>" alt="" class="vd-logo-img"><?php else: ?><span class="vd-logo-mark">&#127807;</span><?php endif; ?>
        <span class="vd-logo-text"><span class="vd-logo-name"><?= $bizName ?></span><span class="vd-logo-tagline"><?= htmlspecialchars($vdTagline) ?></span></span>
    </a>
    <div class="vd-links"><a href="<?= $storeUrl ?>">Home</a><a href="<?= $storeUrl ?>/shop" class="<?= ($page ?? '') === 'shop' ? 'active' : '' ?>">Shop</a><a href="<?= $storeUrl ?>/shop">Categories</a><a href="<?= $storeUrl ?>/shop">About</a></div>
    <div class="vd-icons">
        <span id="store-search-toggle" title="Search">&#128269;</span>
        <span class="vd-account-icon" title="Account"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg></span>
        <a href="<?= $storeUrl ?>/cart" class="vd-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>
<div class="vd-search-wrap"><input id="store-search" placeholder="Search products..."></div>
    <?php break;
    case 'blossom': ?>
<nav class="bl-nav">
    <a href="<?= $storeUrl ?>" class="bl-logo">
        <?php if ($logoImg): ?><img src="<?= $logoImg ?>" alt="" class="bl-logo-img"><?php else: ?><span class="bl-logo-mark">&#10047;</span><?php endif; ?>
        <span><?= $bizName ?></span>
    </a>
    <div class="bl-links"><a href="<?= $storeUrl ?>">Home</a><a href="<?= $storeUrl ?>/shop" class="<?= ($page ?? '') === 'shop' ? 'active' : '' ?>">Categories</a><a href="<?= $storeUrl ?>/shop">Offers</a><a href="<?= $storeUrl ?>/shop">About</a></div>
    <div class="bl-nav-right">
        <input id="store-search" class="bl-search" placeholder="Search for products...">
        <a href="<?= $storeUrl ?>/cart" class="bl-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>
    <?php break;
    case 'radiance': ?>
<nav class="rd-nav">
    <a href="<?= $storeUrl ?>" class="rd-logo">
        <?php if ($logoImg): ?><img src="<?= $logoImg ?>" alt="" class="rd-logo-img"><?php else: ?><span class="rd-logo-mark">&#10022;</span><?php endif; ?>
        <?= $bizName ?>
    </a>
    <div class="rd-links"><a href="<?= $storeUrl ?>">Home</a><a href="<?= $storeUrl ?>/shop" class="<?= ($page ?? '') === 'shop' ? 'active' : '' ?>">Shop</a><a href="<?= $storeUrl ?>/shop">Categories</a></div>
    <div class="rd-nav-actions">
        <input id="store-search" class="rd-search" placeholder="Search for products...">
        <a href="<?= $storeUrl ?>/cart" class="rd-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>
    <?php break;
    case 'amara': ?>
<div class="am-topbar">
    <span>&#128666; <?= $h('topbar1', 'Free Shipping Over ' . $tenant['currency'] . '150') ?></span>
    <span><?= $h('announcement', 'Easy Returns') ?></span>
    <span>&#127760; <?= $h('topbar3', 'Worldwide Delivery') ?></span>
</div>
<nav class="am-nav">
    <div class="am-links"><a href="<?= $storeUrl ?>/shop">New In</a><a href="<?= $storeUrl ?>/shop" class="<?= ($page ?? '') === 'shop' ? 'active' : '' ?>">Shop</a><a href="<?= $storeUrl ?>/shop" class="am-links-hide">Bags</a><a href="<?= $storeUrl ?>/shop" class="am-links-hide">Shoes</a></div>
    <a href="<?= $storeUrl ?>" class="am-logo"><?= $bizName ?><em>Timeless Elegance</em></a>
    <div class="am-icons">
        <span id="store-search-toggle">&#128269;</span>
        <a href="<?= $storeUrl ?>/cart" class="am-cart">&#128722;<span class="cart-count" id="cart-count">0</span></a>
    </div>
</nav>
<div class="am-search-wrap"><input id="store-search" placeholder="Search products..."></div>
    <?php break;
endswitch; ?>
