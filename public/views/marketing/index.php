<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bizflow — Run Your Whole Business From One Dashboard</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/landing.css">
</head>
<body class="ld-body">

<div class="ld-nav-wrap">
    <nav class="ld-nav">
        <a href="<?= $base ?>/" class="ld-logo"><img src="<?= $base ?>/assets/images/logo.png" class="ld-logo-mark" alt="Bizflow logo"> Bizflow</a>
        <div class="ld-nav-links">
            <a href="#features" class="active">Features</a>
            <a href="#how-it-works">How it works</a>
            <a href="#pricing">Pricing</a>
            <a href="#faq">FAQ</a>
        </div>
        <div class="ld-nav-right">
            <a href="<?= $base ?>/login" class="ld-nav-login">Log in</a>
            <a href="<?= $base ?>/register" class="ld-pill-btn">Start Free &rarr;</a>
        </div>
    </nav>
</div>

<section class="ld-hero-section">
    <div class="ld-hero-grid">
        <div class="ld-hero-text">
            <span class="ld-hero-eyebrow"><span class="dot"></span> Simple &bull; Fast &bull; Reliable</span>
            <h1>Simplify Work.<br>Scale Your<br><span class="accent">Business.</span></h1>
            <p>Sales, inventory, customers, an online store, and digital products — all in one dashboard built for modern businesses. Start free, no card required.</p>
            <div class="ld-hero-actions">
                <a href="<?= $base ?>/register" class="ld-pill-btn ld-pill-btn-lg">Start Free Trial &rarr;</a>
                <a href="#how-it-works" class="ld-watch-btn"><span class="ld-watch-circle">&#9654;</span> See how it works</a>
            </div>
            <div class="ld-hero-proof">
                <div class="ld-avatar-stack">
                    <span style="background:#0e7a53;">A</span>
                    <span style="background:#17a672;">B</span>
                    <span style="background:#fdb022;">C</span>
                </div>
                <div class="ld-hero-proof-text">
                    <strong>2,000+ businesses onboard</strong>
                    <span class="stars">&#9733;&#9733;&#9733;&#9733;&#9733; 4.8</span>
                </div>
            </div>
        </div>

        <div class="ld-hero-visual">
            <div class="ld-hero-visual-glow"></div>
            <div class="ld-browser-frame">
                <div class="ld-browser-bar"><span></span><span></span><span></span></div>
                <img src="<?= $base ?>/assets/images/dashboard-hero.png" alt="The Bizflow dashboard — today's revenue, profit, stock value, and a revenue-by-month chart" class="ld-browser-shot">
            </div>
            <div class="ld-hero-float-card ld-hero-float-1">
                <span class="icn">&#9889;</span>
                <div><div class="lbl">Get set up in</div><div class="val">5 minutes</div></div>
            </div>
            <div class="ld-hero-float-card ld-hero-float-2">
                <span class="tag">Limited offer</span>
                <span class="big">7-Day Free Trial</span>
            </div>
        </div>
    </div>
</section>

<section class="ld-logos-strip">
    <div class="ld-logos-row">
        <span><span class="ic">&#128722;</span> GreenMart</span>
        <span><span class="ic">&#128241;</span> Urban Traders</span>
        <span><span class="ic">&#128717;</span> Swift Retail</span>
        <span><span class="ic">&#9878;&#65039;</span> Boutique Hub</span>
        <span><span class="ic">&#128188;</span> ByteWorks</span>
        <span><span class="ic">&#127968;</span> Lagos Essentials</span>
    </div>
</section>

<section class="ld-stat-band">
    <div class="ld-stat-card">
        <div class="ld-stat-text">
            <span class="ld-eyebrow-tag">WHY CHOOSE US</span>
            <h2>2,000+ Businesses Trust Bizflow</h2>
            <p>From single-branch shops to growing teams, we help businesses streamline sales, cut spreadsheet chaos, and grow faster.</p>
            <a href="#how-it-works" class="ld-pill-btn">See How It Works &rarr;</a>
        </div>
        <div class="ld-stat-grid">
            <div class="ld-stat-col">
                <div class="ld-stat-box"><strong>2,000+</strong><span>Active Businesses</span></div>
                <div class="ld-stat-box"><strong>94%</strong><span>Customer Retention</span></div>
            </div>
            <div class="ld-stat-col">
                <div class="ld-stat-box"><strong>24/7</strong><span>Support Team</span></div>
                <div class="ld-stat-box"><strong>99.9%</strong><span>Uptime Guarantee</span></div>
            </div>
        </div>
    </div>
</section>

<section class="ld-section" id="how-it-works">
    <div class="ld-section-head">
        <span class="ld-eyebrow-tag">HOW WE HELP</span>
        <h2>Your Favourite Business<br>Management Partner</h2>
        <p>From your first sale to your hundredth branch — one dashboard, no spreadsheets.</p>
    </div>
    <div class="ld-why-row">
        <div class="ld-why-item">
            <span class="icn">&#9889;</span>
            <strong>Lightning Fast Setup</strong>
            <p>Add products, staff, and branches in minutes — start selling the same day you sign up.</p>
        </div>
        <div class="ld-why-item">
            <span class="icn">&#128202;</span>
            <strong>Real-Time Insights</strong>
            <p>Track revenue, profit, and stock as it happens, with AI insights that explain the numbers.</p>
        </div>
        <div class="ld-why-item">
            <span class="icn">&#127775;</span>
            <strong>Built To Grow With You</strong>
            <p>From a single shop to multiple branches and staff, Bizflow scales right alongside your business.</p>
        </div>
    </div>
</section>

<section class="ld-section" id="features">
    <div class="ld-explore-head">
        <div>
            <span class="ld-explore-eyebrow">Explore Bizflow</span>
            <h2>Everything You'll Love</h2>
        </div>
        <div class="ld-explore-arrows">
            <button type="button" id="feat-prev" aria-label="Previous">&#8592;</button>
            <button type="button" id="feat-next" aria-label="Next">&#8594;</button>
        </div>
    </div>
    <div class="ld-cat-pills">
        <button class="active" data-cat="all">All</button>
        <button data-cat="sales">Sales &amp; POS</button>
        <button data-cat="inventory">Inventory</button>
        <button data-cat="store">Online Store</button>
        <button data-cat="digital">Digital Products</button>
        <button data-cat="reports">Reports</button>
    </div>
    <div class="ld-feature-row" id="feature-cards">
        <div class="ld-feature-card" data-cat="sales">
            <div class="fc-top"><span class="fc-badge">Core</span>&#128179;</div>
            <div class="fc-body">
                <strong>Sales &amp; POS</strong>
                <p>Ring up sales in-store, track every transaction, and keep receipts organized automatically.</p>
                <span class="fc-cta">Learn more &rarr;</span>
            </div>
        </div>
        <div class="ld-feature-card" data-cat="inventory">
            <div class="fc-top"><span class="fc-badge">Core</span>&#128230;</div>
            <div class="fc-body">
                <strong>Inventory Tracking</strong>
                <p>Always know what's in stock, get low-stock alerts, and never oversell again.</p>
                <span class="fc-cta">Learn more &rarr;</span>
            </div>
        </div>
        <div class="ld-feature-card" data-cat="store">
            <div class="fc-top"><span class="fc-badge">Popular</span>&#127968;</div>
            <div class="fc-body">
                <strong>Online Store</strong>
                <p>Launch a branded storefront with a theme, header photo, and checkout — no code needed.</p>
                <span class="fc-cta">Learn more &rarr;</span>
            </div>
        </div>
        <div class="ld-feature-card" data-cat="digital">
            <div class="fc-top"><span class="fc-badge">Free forever</span>&#128190;</div>
            <div class="fc-body">
                <strong>Digital Products</strong>
                <p>Sell ebooks, courses, or files with their own checkout page — free on every plan.</p>
                <span class="fc-cta">Learn more &rarr;</span>
            </div>
        </div>
        <div class="ld-feature-card" data-cat="reports">
            <div class="fc-top"><span class="fc-badge">Insights</span>&#128200;</div>
            <div class="fc-body">
                <strong>Reports &amp; AI Insights</strong>
                <p>See exactly what's selling, what's not, and where your money is going — with AI explaining why.</p>
                <span class="fc-cta">Learn more &rarr;</span>
            </div>
        </div>
        <div class="ld-feature-card" data-cat="sales">
            <div class="fc-top"><span class="fc-badge">Core</span>&#128101;</div>
            <div class="fc-body">
                <strong>Staff &amp; Branches</strong>
                <p>Add staff with role-based access and manage multiple branches from one place.</p>
                <span class="fc-cta">Learn more &rarr;</span>
            </div>
        </div>
    </div>
</section>

<section class="ld-section" id="testimonials">
    <div class="ld-testi-split">
        <div class="ld-testi-visual">
            <div class="avatar-big">&#128075;</div>
            <p style="font-weight:700; margin:0;">Trusted by 2,000+ business owners</p>
            <div class="thumb-badge">&#128077; Loved by our customers</div>
        </div>
        <div class="ld-testi-text">
            <span class="ld-testi-eyebrow">What our customers say</span>
            <h2>Loved By Business Owners Everywhere</h2>
            <p>We're proud to help businesses run smoother and grow faster, every single day.</p>
            <div class="ld-testi-card">
                <div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733; 4.9</div>
                <p>"Bizflow replaced three different notebooks and a spreadsheet. Now I see my whole business — sales, stock, and store orders — in one place."</p>
                <div class="ld-testi-who">
                    <span class="av">A</span>
                    <div><strong>Ada Johnson</strong><span>Owner, AJ Tech Gadgets</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="ld-promo-band">
    <div>
        <span class="ld-promo-eyebrow">Start Selling Today</span>
        <h2>See Your Whole Business On One Screen</h2>
        <p>Sales, stock, customers, and your online store — updated in real time, from any device.</p>
        <div class="ld-promo-actions">
            <a href="<?= $base ?>/register" class="ld-pill-btn">Start Free Trial &rarr;</a>
            <a href="<?= $base ?>/plans" class="ld-pill-btn ld-pill-btn-outline">See Plans</a>
        </div>
    </div>
    <div class="ld-promo-visual">
        <svg viewBox="0 0 420 340" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Dashboard preview on a phone and desktop">
            <rect x="10" y="10" width="260" height="300" rx="20" fill="#fff" stroke="#e4e0fb" stroke-width="2"/>
            <rect x="10" y="10" width="260" height="36" rx="20" fill="#e7f8f0"/>
            <rect x="28" y="64" width="224" height="70" rx="10" fill="#f4f2fe"/>
            <rect x="42" y="80" width="90" height="10" rx="3" fill="#d9d3fb"/>
            <rect x="42" y="98" width="60" height="18" rx="6" fill="#17a672"/>
            <rect x="28" y="146" width="224" height="70" rx="10" fill="#f4f2fe"/>
            <rect x="42" y="162" width="110" height="10" rx="3" fill="#d9d3fb"/>
            <rect x="42" y="180" width="60" height="18" rx="6" fill="#17a672"/>
            <rect x="28" y="228" width="224" height="60" rx="10" fill="#14141a"/>
            <rect x="42" y="244" width="90" height="10" rx="3" fill="#fff"/>
            <rect x="42" y="260" width="130" height="8" rx="3" fill="#8a86a8"/>
            <rect x="300" y="60" width="110" height="220" rx="18" fill="#fff" stroke="#e4e0fb" stroke-width="2"/>
            <rect x="316" y="80" width="78" height="60" rx="8" fill="#e4e0fb"/>
            <rect x="316" y="150" width="78" height="10" rx="3" fill="#d9d3fb"/>
            <rect x="316" y="166" width="50" height="14" rx="5" fill="#17a672"/>
            <rect x="316" y="196" width="78" height="10" rx="3" fill="#d9d3fb"/>
            <rect x="316" y="212" width="50" height="14" rx="5" fill="#17a672"/>
        </svg>
    </div>
</div>

<section class="ld-trust-strip">
    <div class="ld-trust-item"><span class="icn">&#127974;</span><div><strong>Free Setup</strong><span>No hidden costs</span></div></div>
    <div class="ld-trust-item"><span class="icn">&#128260;</span><div><strong>Cancel Anytime</strong><span>No lock-in contracts</span></div></div>
    <div class="ld-trust-item"><span class="icn">&#128274;</span><div><strong>Secure Payments</strong><span>Powered by Flutterwave</span></div></div>
    <div class="ld-trust-item"><span class="icn">&#127911;</span><div><strong>24/7 Support</strong><span>We're here to help</span></div></div>
</section>

<section class="ld-section" id="pricing">
    <div class="ld-section-head">
        <span class="ld-eyebrow-tag">PRICING</span>
        <h2>Simple, Transparent Pricing</h2>
        <p>Every plan starts with a 7-day free trial. Cancel or switch any time.</p>
    </div>
    <div class="pricing-grid" id="pricing-cards">
        <div class="empty-state" style="grid-column: 1 / -1;"><div class="spinner"></div></div>
    </div>
</section>

<section class="ld-section" id="faq">
    <div class="ld-section-head">
        <span class="ld-eyebrow-tag">FAQ</span>
        <h2>Frequently Asked Questions</h2>
        <p>Can't find your answer? Reach out any time — we're happy to help.</p>
    </div>
    <div class="pub-faq" id="faq-list">
        <div class="pub-faq-item">
            <button class="pub-faq-q">Do I need a card to start the free trial? <span>+</span></button>
            <div class="pub-faq-a"><p>No. You get full access to every feature on your chosen plan for 7 days, with no payment details required up front.</p></div>
        </div>
        <div class="pub-faq-item">
            <button class="pub-faq-q">What happens when my trial ends? <span>+</span></button>
            <div class="pub-faq-a"><p>You'll be prompted to choose a plan to keep going. Your data is never deleted — pick a plan any time and pick up exactly where you left off.</p></div>
        </div>
        <div class="pub-faq-item">
            <button class="pub-faq-q">Is the Digital Products feature really free? <span>+</span></button>
            <div class="pub-faq-a"><p>Yes — selling digital products (ebooks, courses, files) is free on every plan, including after your trial ends. We only take a small fee when you withdraw your earnings.</p></div>
        </div>
        <div class="pub-faq-item">
            <button class="pub-faq-q">How do I get paid for online orders and digital products? <span>+</span></button>
            <div class="pub-faq-a"><p>Payments are collected securely through Flutterwave and land in your Earnings page. Request a withdrawal any time — payouts are processed manually, usually within 3 hours.</p></div>
        </div>
        <div class="pub-faq-item">
            <button class="pub-faq-q">Can I customize my online store's design? <span>+</span></button>
            <div class="pub-faq-a"><p>Yes. Pick your store's category and a matching theme, choose a header image from a curated gallery, and edit the text throughout — no code required.</p></div>
        </div>
    </div>
</section>

<div class="ld-cta-band">
    <h2>Ready to run your business the easy way?</h2>
    <p>Start your 7-day free trial — no card required.</p>
    <a href="<?= $base ?>/register" class="ld-pill-btn ld-pill-btn-lg">Start Free Trial</a>
</div>

<footer class="ld-footer">
    <div class="ld-footer-grid">
        <div class="ld-footer-brand">
            <a href="<?= $base ?>/" class="ld-logo"><img src="<?= $base ?>/assets/images/logo.png" class="ld-logo-mark" alt="Bizflow logo"> Bizflow</a>
            <p>Sales, inventory, an online store, and digital products — everything your business needs, in one dashboard.</p>
            <div class="ld-footer-social">
                <a href="#" aria-label="Facebook">f</a>
                <a href="#" aria-label="Instagram">ig</a>
                <a href="#" aria-label="X">x</a>
            </div>
        </div>
        <div class="ld-footer-col">
            <strong>Company</strong>
            <ul>
                <li><a href="#how-it-works">How it works</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#faq">FAQ</a></li>
            </ul>
        </div>
        <div class="ld-footer-col">
            <strong>Support</strong>
            <ul>
                <li><a href="#faq">Help Center</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="<?= $base ?>/login">Log in</a></li>
            </ul>
        </div>
        <div class="ld-footer-col">
            <strong>For Businesses</strong>
            <ul>
                <li><a href="<?= $base ?>/register">Start Free Trial</a></li>
                <li><a href="#features">Online Store</a></li>
                <li><a href="#features">Digital Products</a></li>
            </ul>
        </div>
        <div class="ld-footer-col ld-footer-newsletter">
            <strong>Newsletter</strong>
            <p>Subscribe for product updates and tips.</p>
            <form class="ld-newsletter-form" onsubmit="return false;">
                <input type="email" placeholder="Enter your email" required>
                <button type="submit" aria-label="Subscribe">&rarr;</button>
            </form>
        </div>
    </div>
    <div class="ld-footer-bottom">&copy; <?= date('Y') ?> Bizflow. All rights reserved.</div>
</footer>

<script>
document.querySelectorAll('.pub-faq-q').forEach((btn) => {
    btn.addEventListener('click', () => {
        const item = btn.closest('.pub-faq-item');
        const wasOpen = item.classList.contains('open');
        document.querySelectorAll('.pub-faq-item.open').forEach((el) => el.classList.remove('open'));
        if (!wasOpen) item.classList.add('open');
    });
});

document.querySelectorAll('.ld-cat-pills button').forEach((btn) => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.ld-cat-pills button').forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        const cat = btn.dataset.cat;
        document.querySelectorAll('#feature-cards .ld-feature-card').forEach((card) => {
            card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
        });
    });
});

(function () {
    const row = document.getElementById('feature-cards');
    document.getElementById('feat-prev').addEventListener('click', () => row.scrollBy({ left: -280, behavior: 'smooth' }));
    document.getElementById('feat-next').addEventListener('click', () => row.scrollBy({ left: 280, behavior: 'smooth' }));
})();

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
