<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Oripio — Sales, Inventory &amp; Business Management, Simplified</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="pub-body">

<nav class="pub-nav">
    <div class="pub-logo"><span class="logo-dot"></span> Oripio</div>
    <div class="pub-nav-links">
        <a href="#features">Features</a>
        <a href="#pricing">Pricing</a>
        <a href="<?= $base ?>/login">Log in</a>
    </div>
    <div class="pub-nav-cta">
        <a href="<?= $base ?>/login" class="btn btn-secondary">Log in</a>
        <a href="<?= $base ?>/register" class="btn">Start Free Trial</a>
    </div>
</nav>

<header class="pub-hero">
    <span class="pub-eyebrow">&#10024; 3-day free trial, no card required</span>
    <h1>Run your whole business from one clean dashboard</h1>
    <p>Sales, inventory, customers, expenses, an online store and staff — all in one place, built for shops and small businesses that want to grow without the spreadsheet chaos.</p>
    <div class="pub-hero-actions">
        <a href="<?= $base ?>/register" class="btn">Start your free trial</a>
        <a href="#pricing" class="btn btn-secondary">See pricing</a>
    </div>
</header>

<div class="pub-hero-shot">
    <svg viewBox="0 0 1000 560" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Dashboard preview">
        <rect width="1000" height="560" rx="20" fill="#f2f3f5"/>
        <rect x="24" y="24" width="200" height="512" rx="16" fill="#ffffff" stroke="#eaecef"/>
        <rect x="48" y="48" width="140" height="18" rx="4" fill="#17a672"/>
        <rect x="48" y="96" width="150" height="12" rx="3" fill="#eef0f3"/>
        <rect x="48" y="124" width="150" height="30" rx="8" fill="#e7f8f0"/>
        <rect x="48" y="164" width="150" height="30" rx="8" fill="#f8f9fa"/>
        <rect x="48" y="204" width="150" height="30" rx="8" fill="#f8f9fa"/>
        <rect x="48" y="244" width="150" height="30" rx="8" fill="#f8f9fa"/>
        <rect x="248" y="24" width="728" height="90" rx="16" fill="#ffffff" stroke="#eaecef"/>
        <rect x="272" y="48" width="220" height="20" rx="4" fill="#16181d"/>
        <rect x="272" y="76" width="300" height="12" rx="3" fill="#c9cdd3"/>
        <rect x="248" y="130" width="230" height="120" rx="16" fill="#ffffff" stroke="#eaecef"/>
        <rect x="494" y="130" width="230" height="120" rx="16" fill="#ffffff" stroke="#eaecef"/>
        <rect x="740" y="130" width="236" height="120" rx="16" fill="#ffffff" stroke="#eaecef"/>
        <rect x="272" y="200" width="120" height="26" rx="6" fill="#16181d"/>
        <rect x="518" y="200" width="120" height="26" rx="6" fill="#16181d"/>
        <rect x="764" y="200" width="120" height="26" rx="6" fill="#dc2626"/>
        <rect x="248" y="266" width="480" height="270" rx="16" fill="#ffffff" stroke="#eaecef"/>
        <rect x="272" y="290" width="90" height="60" rx="6" fill="#d8f0e4"/>
        <rect x="374" y="330" width="90" height="20" rx="6" fill="#d8f0e4"/>
        <rect x="476" y="310" width="90" height="40" rx="6" fill="#17a672"/>
        <rect x="578" y="280" width="90" height="70" rx="6" fill="#d8f0e4"/>
        <rect x="740" y="266" width="236" height="270" rx="16" fill="#ffffff" stroke="#eaecef"/>
        <rect x="764" y="292" width="188" height="16" rx="4" fill="#eef0f3"/>
        <rect x="764" y="320" width="188" height="16" rx="4" fill="#eef0f3"/>
        <rect x="764" y="348" width="188" height="16" rx="4" fill="#eef0f3"/>
    </svg>
</div>

<section class="pub-section" id="features">
    <div class="pub-section-head">
        <h2>Everything a growing business needs</h2>
        <p>No plugins, no separate tools to juggle — it's all built in and works together.</p>
    </div>
    <div class="pub-feature-grid">
        <div class="pub-feature-card"><div class="pf-icon">&#128179;</div><h3>Point of Sale</h3><p>Ring up sales in seconds, accept split payments, and print or share receipts instantly.</p></div>
        <div class="pub-feature-card"><div class="pf-icon">&#128230;</div><h3>Inventory Tracking</h3><p>Real-time stock levels, low-stock alerts, and a full history of every stock movement.</p></div>
        <div class="pub-feature-card"><div class="pf-icon">&#128100;</div><h3>Customers &amp; Debt</h3><p>Track repeat customers, outstanding balances, and payment history automatically.</p></div>
        <div class="pub-feature-card"><div class="pf-icon">&#127968;</div><h3>Online Store</h3><p>Turn on a branded storefront in minutes and start taking orders online.</p></div>
        <div class="pub-feature-card"><div class="pf-icon">&#128202;</div><h3>Reports &amp; Insights</h3><p>Profit, sales and inventory reports — plus AI-generated insights in plain language.</p></div>
        <div class="pub-feature-card"><div class="pf-icon">&#128101;</div><h3>Team &amp; Branches</h3><p>Add staff with role-based access and manage multiple branches from one account.</p></div>
    </div>
</section>

<section class="pub-section" id="pricing">
    <div class="pub-section-head">
        <h2>Simple, transparent pricing</h2>
        <p>Every plan starts with a 3-day free trial. Cancel or switch any time.</p>
    </div>
    <div class="pricing-grid" id="pricing-cards">
        <div class="empty-state" style="grid-column: 1 / -1;"><div class="spinner"></div></div>
    </div>
</section>

<div class="pub-cta-band">
    <h2>Ready to run your business the easy way?</h2>
    <p>Start your 3-day free trial — no card required.</p>
    <a href="<?= $base ?>/register" class="btn">Start Free Trial</a>
</div>

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
