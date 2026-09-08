<?php
/**
 * Shared storefront footer — included by every theme. Expects $tenant,
 * $slug, $base, and $settings to already be in scope (they are, since PHP
 * include() shares the caller's variable scope).
 */
$footerContent = $settings['content'] ?? [];
$footerCategories = \App\Models\Category::allForTenant((int) $tenant['id']);
$socials = [
    'facebook' => $footerContent['social_facebook'] ?? null,
    'instagram' => $footerContent['social_instagram'] ?? null,
    'twitter' => $footerContent['social_twitter'] ?? null,
    'tiktok' => $footerContent['social_tiktok'] ?? null,
];
$socialIcons = ['facebook' => '&#128247;', 'instagram' => '&#128248;', 'twitter' => '&#128038;', 'tiktok' => '&#127925;'];
$hasSocials = array_filter($socials);
?>
<footer class="store-footer">
    <div class="store-footer-inner">
        <div class="store-footer-col store-footer-brand">
            <?php if (!empty($footerContent['logo_path'])): ?>
                <img src="<?= $base . htmlspecialchars($footerContent['logo_path']) ?>" alt="<?= htmlspecialchars($tenant['business_name']) ?>" class="store-footer-logo">
            <?php endif; ?>
            <strong><?= htmlspecialchars($tenant['business_name']) ?></strong>
        </div>
        <div class="store-footer-col">
            <h5>Categories</h5>
            <?php foreach ($footerCategories as $cat): ?>
                <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>?category_id=<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></a>
            <?php endforeach; ?>
            <?php if (empty($footerCategories)): ?><span class="text-muted">No categories yet</span><?php endif; ?>
        </div>
        <div class="store-footer-col">
            <h5>Shop</h5>
            <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>">All Products</a>
            <a href="<?= $base ?>/<?= htmlspecialchars($slug) ?>/cart">Your Cart</a>
        </div>
        <?php if ($hasSocials): ?>
        <div class="store-footer-col">
            <h5>Follow Us</h5>
            <div class="store-footer-socials">
                <?php foreach ($socials as $key => $url): if (!$url) continue; ?>
                    <a href="<?= htmlspecialchars($url) ?>" target="_blank" title="<?= ucfirst($key) ?>"><?= $socialIcons[$key] ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="store-footer-bottom">&copy; <?= date('Y') ?> <?= htmlspecialchars($tenant['business_name']) ?>. All rights reserved.</div>
</footer>
<?php if (!empty($footerContent['whatsapp_number'])): $waDigits = preg_replace('/[^\d+]/', '', $footerContent['whatsapp_number']); ?>
<a class="wa-float-btn" href="https://wa.me/<?= htmlspecialchars($waDigits) ?>" target="_blank" title="Chat with us on WhatsApp">&#128172;</a>
<?php endif; ?>
