<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Plans &amp; Pricing — Oripio</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="pub-body">
<nav class="pub-nav">
    <a href="<?= $base ?>/" class="pub-logo"><span class="logo-dot"></span> Oripio</a>
    <div class="pub-nav-links"><a href="<?= $base ?>/#features">Features</a></div>
    <div class="pub-nav-cta">
        <a href="<?= $base ?>/login" class="btn btn-secondary">Log in</a>
        <a href="<?= $base ?>/register" class="btn">Start Free Trial</a>
    </div>
</nav>

<section class="pub-section" style="padding-top:50px;">
    <div class="pub-section-head">
        <h2>Simple, transparent pricing</h2>
        <p>Every plan starts with a 3-day free trial. Already have an account and your trial just ended? Log in and upgrade from your dashboard.</p>
    </div>
    <div class="pricing-grid" id="pricing-cards">
        <div class="empty-state" style="grid-column: 1 / -1;"><div class="spinner"></div></div>
    </div>
</section>

<footer class="pub-footer">&copy; <?= date('Y') ?> Oripio. All rights reserved.</footer>

<script>
(async function () {
    try {
        const res = await fetch('<?= $base ?>/api/plans');
        const json = await res.json();
        const plans = (json && json.data) || [];
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
        const fmt = (n) => '&#8358;' + Number(n || 0).toLocaleString();
        document.getElementById('pricing-cards').innerHTML = plans.map((p, i) => `
            <div class="pricing-card ${i === 1 ? 'featured' : ''}">
                ${i === 1 ? '<span class="pc-badge">Most Popular</span>' : ''}
                <div class="pc-name">${esc(p.name)}</div>
                <div class="pc-price">${fmt(p.price_monthly)}<span>/month</span></div>
                <p class="pc-desc">${esc(p.description || '')}</p>
                <ul>${(p.features || []).map((f) => `<li class="${f.enabled ? '' : 'off'}">${f.enabled ? '&#10003;' : '&#10005;'} ${esc(f.feature_label)}</li>`).join('')}</ul>
                <a href="<?= $base ?>/register" class="btn ${i === 1 ? '' : 'btn-secondary'}">Start Free Trial</a>
            </div>`).join('');
    } catch (e) {
        document.getElementById('pricing-cards').innerHTML = '<p class="text-muted" style="grid-column:1/-1; text-align:center;">Could not load pricing right now.</p>';
    }
})();
</script>
</body>
</html>
