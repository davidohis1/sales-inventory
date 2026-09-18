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
            <a href="#services" class="active">Features</a>
            <a href="#about">About</a>
            <a href="#pricing">Pricing</a>
            <a href="#faq">FAQ</a>
        </div>
        <div class="ld-nav-right">
            <a href="<?= $base ?>/login" class="ld-nav-login">Log in</a>
            <a href="<?= $base ?>/register" class="ld-pill-btn">Start Free &rarr;</a>
        </div>
    </nav>
</div>

<!-- ================= HERO ================= -->
<section class="ld-hero-section">
    <div class="ld-hero-grid">
        <div class="ld-hero-text">
            <span class="ld-hero-eyebrow"><span class="dot"></span> Simple &bull; Fast &bull; Reliable</span>
            <h1>Simplify Work.<br>Scale Your<br><span class="accent">Business.</span></h1>
            <p>Sales, inventory, customers, an online store, and digital products — all in one dashboard built for modern businesses. Start free, no card required.</p>
            <div class="ld-hero-actions">
                <a href="<?= $base ?>/register" class="ld-pill-btn ld-pill-btn-lg">Start Free Trial &rarr;</a>
                <a href="#about" class="ld-watch-btn">
                    <span class="ld-watch-circle"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M8 5v14l11-7z"/></svg></span>
                    See how it works
                </a>
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
            <div class="ld-hero-blob"></div>
            <div class="ld-browser-frame">
                <div class="ld-browser-bar"><span></span><span></span><span></span></div>
                <img src="<?= $base ?>/assets/images/dashboard-hero.png" alt="The Bizflow dashboard — today's revenue, profit, stock value, and a revenue-by-month chart" class="ld-browser-shot">
            </div>
            <div class="ld-hero-float-card ld-hero-float-1">
                <span class="icn"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z"/></svg></span>
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
        <span>GreenMart</span>
        <span>Urban Traders</span>
        <span>Swift Retail</span>
        <span>Boutique Hub</span>
        <span>ByteWorks</span>
        <span>Lagos Essentials</span>
    </div>
</section>

<!-- ================= ABOUT + STATS ================= -->
<section class="ld-section" id="about">
    <div class="ld-about-grid">
        <div class="ld-about-text">
            <span class="ld-eyebrow-tag">ABOUT BIZFLOW</span>
            <h2>A Powerful Engine For Your Business, Not A Spreadsheet</h2>
            <p>Bizflow brings your sales floor, your stockroom, and your storefront onto one screen — so you spend less time reconciling numbers and more time running the business.</p>
            <ul class="ld-about-checklist">
                <li><span class="ck"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"/></svg></span> Real-time stock and sales, synced across every branch</li>
                <li><span class="ck"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"/></svg></span> Built-in online store and digital product checkout</li>
                <li><span class="ck"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"/></svg></span> Secure payments and payouts, powered by Flutterwave</li>
            </ul>
            <a href="<?= $base ?>/register" class="ld-pill-btn">Start Free Trial &rarr;</a>
        </div>
        <div class="ld-stat-grid-2">
            <div class="ld-stat-tile c-green">
                <span class="st-icn"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 8.5a3 3 0 1 1 3.5 5.9M21.5 20a5.5 5.5 0 0 0-4-5.3"/></svg></span>
                <strong>2,000+</strong>
                <span>Active Businesses</span>
            </div>
            <div class="ld-stat-tile c-amber">
                <span class="st-icn"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></span>
                <strong>94%</strong>
                <span>Customer Retention</span>
            </div>
            <div class="ld-stat-tile c-dark">
                <span class="st-icn"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/></svg></span>
                <strong>24/7</strong>
                <span>Support Team</span>
            </div>
            <div class="ld-stat-tile c-green">
                <span class="st-icn"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m3 17 5-5 4 4 8-8M15 8h5v5"/></svg></span>
                <strong>99.9%</strong>
                <span>Uptime Guarantee</span>
            </div>
        </div>
    </div>
</section>

<!-- ================= SERVICES ================= -->
<section class="ld-section" id="services">
    <div class="ld-section-head">
        <span class="ld-eyebrow-tag">WHAT YOU GET</span>
        <h2>Everything Your Business Needs</h2>
        <p>One dashboard, six tools that used to be six different apps.</p>
    </div>
    <div class="ld-services-grid">
        <div class="ld-feature-card">
            <div class="fc-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg></div>
            <span class="fc-tag">Core</span>
            <strong>Sales &amp; POS</strong>
            <p>Ring up sales in-store, track every transaction, and keep receipts organized automatically.</p>
            <span class="fc-cta">Learn more &rarr;</span>
        </div>
        <div class="ld-feature-card">
            <div class="fc-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8M12 13v8"/></svg></div>
            <span class="fc-tag">Core</span>
            <strong>Inventory Tracking</strong>
            <p>Always know what's in stock, get low-stock alerts, and never oversell again.</p>
            <span class="fc-cta">Learn more &rarr;</span>
        </div>
        <div class="ld-feature-card">
            <div class="fc-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3 9 4 4h16l1 5M3 9v10a1 1 0 0 0 1 1h5v-6h6v6h5a1 1 0 0 0 1-1V9M3 9h18"/></svg></div>
            <span class="fc-tag">Popular</span>
            <strong>Online Store</strong>
            <p>Launch a branded storefront with a theme, header photo, and checkout — no code needed.</p>
            <span class="fc-cta">Learn more &rarr;</span>
        </div>
        <div class="ld-feature-card">
            <div class="fc-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg></div>
            <span class="fc-tag">Free forever</span>
            <strong>Digital Products</strong>
            <p>Sell ebooks, courses, or files with their own checkout page — free on every plan.</p>
            <span class="fc-cta">Learn more &rarr;</span>
        </div>
        <div class="ld-feature-card">
            <div class="fc-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m3 17 5-5 4 4 8-8M15 8h5v5"/></svg></div>
            <span class="fc-tag">Insights</span>
            <strong>Reports &amp; AI Insights</strong>
            <p>See exactly what's selling, what's not, and where your money is going — with AI explaining why.</p>
            <span class="fc-cta">Learn more &rarr;</span>
        </div>
        <div class="ld-feature-card">
            <div class="fc-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 8.5a3 3 0 1 1 3.5 5.9M21.5 20a5.5 5.5 0 0 0-4-5.3"/></svg></div>
            <span class="fc-tag">Core</span>
            <strong>Staff &amp; Branches</strong>
            <p>Add staff with role-based access and manage multiple branches from one place.</p>
            <span class="fc-cta">Learn more &rarr;</span>
        </div>
    </div>
</section>

<!-- ================= SCREENS ================= -->
<section class="ld-section" id="screens">
    <div class="ld-screens-head">
        <div>
            <span class="ld-screens-eyebrow">Take A Look Inside</span>
            <h2>Built To Be Used Every Day</h2>
        </div>
    </div>
    <div class="ld-screens-row">
        <div class="ld-screen-card">
            <div class="sc-frame">
                <svg viewBox="0 0 300 190" xmlns="http://www.w3.org/2000/svg">
                    <rect x="10" y="10" width="280" height="170" rx="12" fill="#fff"/>
                    <rect x="26" y="26" width="90" height="10" rx="3" fill="#14141a"/>
                    <rect x="26" y="46" width="248" height="46" rx="8" fill="#f2faf6"/>
                    <rect x="40" y="58" width="70" height="8" rx="3" fill="#17a672"/>
                    <rect x="40" y="72" width="110" height="7" rx="3" fill="#d7ddd9"/>
                    <rect x="26" y="102" width="120" height="46" rx="8" fill="#f2faf6"/>
                    <rect x="154" y="102" width="120" height="46" rx="8" fill="#f2faf6"/>
                    <rect x="40" y="116" width="60" height="8" rx="3" fill="#17a672"/>
                    <rect x="168" y="116" width="60" height="8" rx="3" fill="#f5a524"/>
                </svg>
            </div>
            <div class="sc-body">
                <div><strong>Sales dashboard</strong><br><span>Today's revenue at a glance</span></div>
                <span class="sc-arrow"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
            </div>
        </div>
        <div class="ld-screen-card">
            <div class="sc-frame">
                <svg viewBox="0 0 300 190" xmlns="http://www.w3.org/2000/svg">
                    <rect x="10" y="10" width="280" height="170" rx="12" fill="#fff"/>
                    <rect x="26" y="26" width="248" height="26" rx="7" fill="#f2faf6"/>
                    <rect x="26" y="60" width="248" height="26" rx="7" fill="#fff" stroke="#e6f2ec"/>
                    <rect x="26" y="94" width="248" height="26" rx="7" fill="#f2faf6"/>
                    <rect x="26" y="128" width="248" height="26" rx="7" fill="#fff" stroke="#e6f2ec"/>
                    <rect x="38" y="70" width="120" height="7" rx="3" fill="#14141a"/>
                    <rect x="230" y="70" width="30" height="7" rx="3" fill="#17a672"/>
                    <rect x="38" y="138" width="120" height="7" rx="3" fill="#14141a"/>
                    <rect x="230" y="138" width="30" height="7" rx="3" fill="#e05656"/>
                </svg>
            </div>
            <div class="sc-body">
                <div><strong>Inventory list</strong><br><span>Stock levels, live</span></div>
                <span class="sc-arrow"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
            </div>
        </div>
        <div class="ld-screen-card">
            <div class="sc-frame">
                <svg viewBox="0 0 300 190" xmlns="http://www.w3.org/2000/svg">
                    <rect x="10" y="10" width="280" height="170" rx="12" fill="#fff"/>
                    <rect x="26" y="26" width="248" height="60" rx="8" fill="#14141a"/>
                    <rect x="40" y="42" width="90" height="8" rx="3" fill="#fff"/>
                    <rect x="40" y="58" width="140" height="7" rx="3" fill="#8f938f"/>
                    <rect x="26" y="96" width="118" height="60" rx="8" fill="#f2faf6"/>
                    <rect x="156" y="96" width="118" height="60" rx="8" fill="#f2faf6"/>
                    <rect x="40" y="110" width="60" height="8" rx="3" fill="#17a672"/>
                    <rect x="170" y="110" width="60" height="8" rx="3" fill="#17a672"/>
                </svg>
            </div>
            <div class="sc-body">
                <div><strong>Online store</strong><br><span>Your branded storefront</span></div>
                <span class="sc-arrow"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
            </div>
        </div>
    </div>
</section>

<!-- ================= TESTIMONIALS ================= -->
<section class="ld-section" id="testimonials">
    <div class="ld-section-head">
        <span class="ld-eyebrow-tag">WHAT OUR CUSTOMERS SAY</span>
        <h2>Loved By Business Owners Everywhere</h2>
        <p>We're proud to help businesses run smoother and grow faster, every single day.</p>
    </div>
    <div class="ld-testi-grid">
        <div class="ld-testi-card">
            <span class="quote-mark">&#8220;</span>
            <div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733; 4.9</div>
            <p>Bizflow replaced three different notebooks and a spreadsheet. Now I see my whole business — sales, stock, and store orders — in one place.</p>
            <div class="ld-testi-who">
                <span class="av">A</span>
                <div><strong>Ada Johnson</strong><span>Owner, AJ Tech Gadgets</span></div>
            </div>
        </div>
        <div class="ld-testi-card">
            <span class="quote-mark">&#8220;</span>
            <div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733; 4.8</div>
            <p>Setting up my storefront took an afternoon, not a developer. Payouts land in my account within a few hours of a sale.</p>
            <div class="ld-testi-who">
                <span class="av">D</span>
                <div><strong>David Musa</strong><span>Founder, Urban Traders</span></div>
            </div>
        </div>
    </div>
</section>

<!-- ================= PRICING ================= -->
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

<section class="ld-trust-strip">
    <div class="ld-trust-item"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"/></svg> Free setup, no hidden costs</div>
    <div class="ld-trust-item"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"/></svg> Cancel anytime, no lock-in</div>
    <div class="ld-trust-item"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"/></svg> Secure payments via Flutterwave</div>
    <div class="ld-trust-item"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 6 9 17l-5-5"/></svg> 24/7 support</div>
</section>

<!-- ================= FAQ ================= -->
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

<!-- ================= CTA ================= -->
<div class="ld-section" style="padding-top:0;">
    <div class="ld-cta-band">
        <span class="cta-icn"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z"/></svg></span>
        <h2>Ready to run your business the easy way?</h2>
        <p>Start your 7-day free trial — no card required.</p>
        <a href="<?= $base ?>/register" class="ld-pill-btn ld-pill-btn-lg">Start Free Trial</a>
    </div>
</div>

<!-- ================= FOOTER ================= -->
<footer class="ld-footer">
    <div class="ld-footer-grid">
        <div class="ld-footer-brand">
            <a href="<?= $base ?>/" class="ld-logo"><img src="<?= $base ?>/assets/images/logo.png" class="ld-logo-mark" alt="Bizflow logo"> Bizflow</a>
            <p>Sales, inventory, an online store, and digital products — everything your business needs, in one dashboard.</p>
            <div class="ld-footer-social">
                <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M14 9h3V6h-3c-2 0-3 1.2-3 3v2H9v3h2v7h3v-7h3l1-3h-4V9c0-.5.3-1 1-1Z"/></svg></a>
                <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><rect x="4" y="4" width="16" height="16" rx="4" fill="none" stroke="#14141a" stroke-width="1.6"/><circle cx="12" cy="12" r="3.4" fill="none" stroke="#14141a" stroke-width="1.6"/><circle cx="17" cy="7" r="1"/></svg></a>
                <a href="#" aria-label="X"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m5 5 14 14M19 5 5 19" stroke="#14141a" stroke-width="1.8" fill="none"/></svg></a>
            </div>
        </div>
        <div class="ld-footer-col">
            <strong>Company</strong>
            <ul>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Features</a></li>
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
                <li><a href="#services">Online Store</a></li>
                <li><a href="#services">Digital Products</a></li>
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