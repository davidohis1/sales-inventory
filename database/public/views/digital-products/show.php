<?php
$images = $product['images'] ?: [];
$mainImage = $images[0] ?? null;
$videoEmbed = null;
if (!empty($product['video_url'])) {
    $url = $product['video_url'];
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([a-zA-Z0-9_-]{6,})/', $url, $m)) {
        $videoEmbed = "https://www.youtube.com/embed/{$m[1]}";
    } elseif (str_contains($url, 'vimeo.com')) {
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) $videoEmbed = "https://player.vimeo.com/video/{$m[1]}";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['name']) ?> &mdash; <?= htmlspecialchars($tenant['business_name']) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/digital-product.css">
</head>
<body class="dp-body">
<nav class="dp-nav">
    <a href="<?= $base ?>/<?= htmlspecialchars($tenant['slug']) ?>" class="dp-brand"><span class="logo-dot"></span> <?= htmlspecialchars($tenant['business_name']) ?></a>
    <span class="dp-secure">&#128274; Secure checkout via Flutterwave</span>
</nav>

<div class="dp-shell">
    <div class="dp-gallery">
        <div class="dp-main-image" id="dp-main-image">
            <?php if ($mainImage): ?><img src="<?= $base . htmlspecialchars($mainImage) ?>" alt="" id="dp-main-img-tag">
            <?php else: ?><div class="dp-no-image">&#128190;</div><?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="dp-thumbs">
            <?php foreach ($images as $img): ?>
            <img src="<?= $base . htmlspecialchars($img) ?>" class="dp-thumb" data-full="<?= $base . htmlspecialchars($img) ?>">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($videoEmbed): ?>
        <div class="dp-video"><iframe src="<?= htmlspecialchars($videoEmbed) ?>" allowfullscreen></iframe></div>
        <?php endif; ?>
    </div>

    <div class="dp-info">
        <?php if (!empty($product['category'])): ?><span class="dp-cat-tag"><?= htmlspecialchars($product['category']) ?></span><?php endif; ?>
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <div class="dp-price-row">
            <span class="dp-price"><?= htmlspecialchars($tenant['currency']) ?><?= number_format((float) $product['price'], 2) ?></span>
            <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
            <span class="dp-compare"><?= htmlspecialchars($tenant['currency']) ?><?= number_format((float) $product['compare_price'], 2) ?></span>
            <span class="dp-save-badge">Save <?= round((1 - $product['price'] / $product['compare_price']) * 100) ?>%</span>
            <?php endif; ?>
        </div>
        <div class="dp-meta"><span>&#128200; <?= (int) $product['sales_count'] ?> sold</span></div>

        <div class="dp-buy-card">
            <h3>Get instant access</h3>
            <form id="dp-buy-form">
                <div class="form-group"><label>Your name</label><input class="form-control" name="buyer_name" required></div>
                <div class="form-group"><label>Email (we'll send your download link here)</label><input class="form-control" type="email" name="buyer_email" required></div>
                <div id="dp-error" class="form-error"></div>
                <button class="btn-store" type="submit" id="dp-buy-btn" style="width:100%; justify-content:center;">Buy Now &mdash; <?= htmlspecialchars($tenant['currency']) ?><?= number_format((float) $product['price'], 2) ?></button>
            </form>
            <p class="dp-secure-note">&#128274; Secure payment powered by Flutterwave</p>
        </div>

        <div class="dp-description">
            <h3>Description</h3>
            <div class="dp-description-body"><?= $product['description'] ?: '<p class="text-muted">No description provided.</p>' ?></div>
        </div>
    </div>
</div>

<footer class="dp-footer">Sold by <?= htmlspecialchars($tenant['business_name']) ?> &middot; Powered by Oripio</footer>

<div class="toast-container" id="toast-container"></div>
<div id="dp-modal-root"></div>
<script>
document.querySelectorAll('.dp-thumb').forEach((t) => t.addEventListener('click', () => {
    document.getElementById('dp-main-img-tag').src = t.dataset.full;
}));

document.getElementById('dp-buy-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('dp-buy-btn');
    const errBox = document.getElementById('dp-error');
    errBox.textContent = '';
    btn.disabled = true; btn.textContent = 'Please wait…';
    const data = Object.fromEntries(new FormData(e.target).entries());
    try {
        const res = await fetch('<?= $base ?>/api/digital-products/<?= htmlspecialchars($product['slug']) ?>/buy', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Could not start checkout');
        if (json.data.simulated) {
            document.getElementById('dp-modal-root').innerHTML = `
                <div class="modal-backdrop"><div class="modal">
                    <h3>Development checkout</h3>
                    <p class="text-muted">Flutterwave isn't configured yet — simulate a successful payment to test the flow.</p>
                    <button class="btn-store" id="dp-sim-confirm" style="width:100%; justify-content:center;">Simulate Successful Payment</button>
                </div></div>`;
            document.getElementById('dp-sim-confirm').addEventListener('click', () => {
                window.location.href = '<?= $base ?>/payments/callback?tx_ref=' + encodeURIComponent(json.data.tx_ref) + '&transaction_id=SIMULATED&status=successful';
            });
        } else {
            window.location.href = json.data.link;
        }
    } catch (err) {
        errBox.textContent = err.message;
        btn.disabled = false;
        btn.textContent = 'Buy Now — <?= htmlspecialchars($tenant['currency']) ?><?= number_format((float) $product['price'], 2) ?>';
    }
});
</script>
</body>
</html>
