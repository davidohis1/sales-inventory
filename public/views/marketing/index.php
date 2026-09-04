<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Oripio — Run Your Whole Business From One Dashboard</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= $base ?>/assets/css/landing.css">
</head>
<body class="ld-body">

<div class="ld-hero-wrap">
    <nav class="ld-nav">
        <a href="<?= $base ?>/" class="ld-logo">Oripio</a>
        <div class="ld-nav-links">
            <a href="#features">Features</a>
            <a href="#how-it-works">How it works</a>
            <a href="#pricing">Pricing</a>
            <a href="#faq">FAQ</a>
        </div>
        <a href="<?= $base ?>/register" class="ld-pill-btn">Start Free</a>
    </nav>

    <header class="ld-hero">
        <span class="ld-badge ld-badge-1">&#9889;</span>
        <span class="ld-badge ld-badge-2">&#8594;</span>
        <h1>Run Your Business<br>Like A Pro.</h1>
        <p>Sales, inventory, customers, an online store, and digital products —<br>all in one dashboard. Start free, no card required.</p>
        <a href="<?= $base ?>/register" class="ld-pill-btn ld-pill-btn-lg">Start Free Trial &nbsp;&rarr;</a>
    </header>

    <div class="ld-illustration">
        <svg viewBox="0 0 900 420" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Two people working on the Oripio dashboard">
            <rect x="230" y="60" width="440" height="290" rx="22" fill="#fff" stroke="#e4e0fb" stroke-width="2"/>
            <rect x="230" y="60" width="440" height="42" rx="22" fill="#efeafd"/>
            <circle cx="256" cy="81" r="6" fill="#f97066"/>
            <circle cx="276" cy="81" r="6" fill="#fdb022"/>
            <circle cx="296" cy="81" r="6" fill="#17a672"/>
            <rect x="254" y="122" width="150" height="14" rx="4" fill="#6c5ce7"/>
            <rect x="254" y="146" width="180" height="10" rx="3" fill="#e4e0fb"/>
            <rect x="254" y="166" width="130" height="10" rx="3" fill="#e4e0fb"/>
            <rect x="254" y="196" width="150" height="90" rx="10" fill="#f4f2fe"/>
            <rect x="270" y="212" width="60" height="34" rx="6" fill="#6c5ce7"/>
            <rect x="340" y="212" width="48" height="34" rx="6" fill="#17a672"/>
            <rect x="270" y="256" width="118" height="16" rx="4" fill="#d9d3fb"/>
            <rect x="424" y="196" width="220" height="90" rx="10" fill="#f4f2fe"/>
            <rect x="440" y="212" width="188" height="10" rx="3" fill="#d9d3fb"/>
            <rect x="440" y="230" width="150" height="10" rx="3" fill="#d9d3fb"/>
            <rect x="440" y="248" width="170" height="10" rx="3" fill="#d9d3fb"/>

            <g>
                <ellipse cx="120" cy="382" rx="86" ry="12" fill="#efeafd"/>
                <rect x="60" y="330" width="120" height="14" rx="6" fill="#16181d"/>
                <rect x="90" y="300" width="60" height="42" rx="4" fill="#6c5ce7"/>
                <circle cx="120" cy="255" r="30" fill="#f4c6a5"/>
                <path d="M92 250 q28 -36 56 0 v-14 q-28 -22 -56 0 z" fill="#16181d"/>
                <rect x="88" y="278" width="64" height="54" rx="14" fill="#6c5ce7"/>
                <rect x="70" y="292" width="26" height="46" rx="10" fill="#6c5ce7"/>
                <rect x="144" y="292" width="26" height="46" rx="10" fill="#6c5ce7"/>
            </g>

            <g>
                <ellipse cx="790" cy="382" rx="86" ry="12" fill="#efeafd"/>
                <rect x="730" y="330" width="120" height="14" rx="6" fill="#16181d"/>
                <rect x="760" y="300" width="60" height="42" rx="4" fill="#17a672"/>
                <circle cx="790" cy="255" r="30" fill="#f4c6a5"/>
                <path d="M762 248 q28 -30 56 0 q0 -20 -28 -22 q-28 2 -28 22 z" fill="#2b2320"/>
                <rect x="758" y="278" width="64" height="54" rx="14" fill="#17a672"/>
                <rect x="740" y="292" width="26" height="46" rx="10" fill="#17a672"/>
                <rect x="814" y="292" width="26" height="46" rx="10" fill="#17a672"/>
            </g>

            <circle cx="60" cy="150" r="4" fill="#c9c2f7"/>
            <circle cx="80" cy="130" r="3" fill="#c9c2f7"/>
            <path d="M40 190 q10 -20 30 -10" stroke="#c9c2f7" stroke-width="2" fill="none"/>
            <rect x="815" y="150" width="40" height="34" rx="8" fill="#fff" stroke="#e4e0fb" stroke-width="2"/>
            <path d="M815 178 l10 10 l-10 6 z" fill="#fff" stroke="#e4e0fb" stroke-width="2"/>
        </svg>
    </div>

    <div class="ld-dots"><span class="active"></span><span></span><span></span></div>
</div>

<section class="ld-section" id="how-it-works">
    <div class="ld-section-head">
        <h2>Everything you need to run and<br>grow your business</h2>
        <p>From your first sale to your hundredth branch — one dashboard, no spreadsheets.</p>
    </div>
    <div class="ld-testimonial-row">
        <div class="ld-t-card ld-t-light">
            <p>"Set up your shop, add products, and start selling — in-store or online — the same day you sign up."</p>
            <strong>Get started in minutes</strong>
            <a href="<?= $base ?>/register">Start Free Trial &rarr;</a>
        </div>
        <div class="ld-t-card ld-t-dark">
            <div class="ld-t-icon">&#128176;</div>
            <p>"Track revenue, profit, and stock in real time, with AI insights that explain what's actually happening."</p>
            <strong>Know your numbers</strong>
            <span>Real-time reporting</span>
        </div>
    </div>
</section>

<section class="ld-section" id="online-store">
    <div class="ld-split">
        <div class="ld-split-text">
            <span class="ld-eyebrow-tag">ONLINE STORE</span>
            <h2>Sell online without touching a line of code</h2>
            <p>Turn on a branded storefront in minutes. Pick a theme, choose a header photo for your category, and edit every heading — no developer needed.</p>
            <ul class="ld-check-list">
                <li>5 ready-made themes, each fully customizable</li>
                <li>Organize products into categories customers can filter</li>
                <li>Flutterwave checkout built in, with order tracking from "Ordered" to "Delivered"</li>
                <li>See exactly how much you've earned and withdraw any time</li>
            </ul>
            <a href="<?= $base ?>/register" class="ld-pill-btn">Start Selling Online &rarr;</a>
        </div>
        <div class="ld-split-visual">
            <svg viewBox="0 0 420 340" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Online store preview">
                <rect x="10" y="10" width="400" height="320" rx="20" fill="#f6f5fc"/>
                <rect x="30" y="30" width="360" height="46" rx="12" fill="#fff"/>
                <circle cx="52" cy="53" r="10" fill="#6c5ce7"/>
                <rect x="72" y="45" width="90" height="16" rx="4" fill="#d9d3fb"/>
                <rect x="300" y="42" width="70" height="22" rx="11" fill="#14141a"/>
                <rect x="30" y="90" width="170" height="110" rx="12" fill="#fff"/>
                <rect x="46" y="104" width="138" height="60" rx="8" fill="#e4e0fb"/>
                <rect x="46" y="174" width="100" height="10" rx="3" fill="#d9d3fb"/>
                <rect x="46" y="190" width="60" height="12" rx="4" fill="#6c5ce7"/>
                <rect x="216" y="90" width="174" height="110" rx="12" fill="#fff"/>
                <rect x="232" y="104" width="142" height="60" rx="8" fill="#d7f5df"/>
                <rect x="232" y="174" width="100" height="10" rx="3" fill="#d9d3fb"/>
                <rect x="232" y="190" width="60" height="12" rx="4" fill="#17a672"/>
                <rect x="30" y="216" width="360" height="94" rx="12" fill="#14141a"/>
                <rect x="50" y="236" width="120" height="14" rx="4" fill="#fff"/>
                <rect x="50" y="260" width="180" height="10" rx="3" fill="#5a5a66"/>
                <rect x="50" y="278" width="90" height="20" rx="10" fill="#6c5ce7"/>
            </svg>
        </div>
    </div>
</section>

<section class="ld-section" id="sales-inventory">
    <div class="ld-split ld-split-reverse">
        <div class="ld-split-visual">
            <svg viewBox="0 0 420 340" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Sales and inventory dashboard preview">
                <rect x="10" y="10" width="400" height="320" rx="20" fill="#f6f5fc"/>
                <rect x="30" y="30" width="170" height="90" rx="12" fill="#fff"/>
                <rect x="46" y="46" width="90" height="10" rx="3" fill="#d9d3fb"/>
                <rect x="46" y="68" width="60" height="20" rx="4" fill="#14141a"/>
                <rect x="46" y="96" width="70" height="8" rx="3" fill="#17a672"/>
                <rect x="220" y="30" width="170" height="90" rx="12" fill="#fff"/>
                <rect x="236" y="46" width="90" height="10" rx="3" fill="#d9d3fb"/>
                <rect x="236" y="68" width="60" height="20" rx="4" fill="#14141a"/>
                <rect x="236" y="96" width="70" height="8" rx="3" fill="#f97066"/>
                <rect x="30" y="134" width="360" height="176" rx="12" fill="#fff"/>
                <rect x="48" y="152" width="140" height="12" rx="4" fill="#14141a"/>
                <g>
                    <rect x="48" y="184" width="60" height="70" rx="6" fill="#e4e0fb"/>
                    <rect x="118" y="204" width="60" height="50" rx="6" fill="#e4e0fb"/>
                    <rect x="188" y="164" width="60" height="90" rx="6" fill="#6c5ce7"/>
                    <rect x="258" y="194" width="60" height="60" rx="6" fill="#e4e0fb"/>
                    <rect x="328" y="174" width="40" height="80" rx="6" fill="#e4e0fb"/>
                </g>
            </svg>
        </div>
        <div class="ld-split-text">
            <span class="ld-eyebrow-tag">SALES &amp; INVENTORY</span>
            <h2>Never lose track of stock again</h2>
            <p>Ring up sales in seconds with POS, and watch stock levels update automatically across every branch — no more guessing what's left on the shelf.</p>
            <ul class="ld-check-list">
                <li>Real-time stock levels with low-stock alerts</li>
                <li>Point-of-sale built for fast, in-person checkout</li>
                <li>Profit tracking — revenue minus cost minus expenses, automatically</li>
                <li>AI insights that explain your trends in plain language</li>
            </ul>
            <a href="<?= $base ?>/register" class="ld-pill-btn">Start Free Trial &rarr;</a>
        </div>
    </div>
</section>

<section class="ld-section" id="why-oripio">
    <div class="ld-section-head">
        <h2>Why your business needs this</h2>
        <p>Running a business on notebooks and spreadsheets works — until it doesn't.</p>
    </div>
    <div class="ld-why-grid">
        <div class="ld-why-card">
            <span class="ld-why-icon">&#9203;</span>
            <strong>Save hours every week</strong>
            <p>Stop re-typing the same sale into three different books. One entry updates stock, revenue, and customer history at once.</p>
        </div>
        <div class="ld-why-card">
            <span class="ld-why-icon">&#128200;</span>
            <strong>Stop guessing, start knowing</strong>
            <p>See exactly what's selling, what's not, and where your money is going — instead of finding out at the end of the month.</p>
        </div>
        <div class="ld-why-card">
            <span class="ld-why-icon">&#127760;</span>
            <strong>Sell beyond your shop</strong>
            <p>Your customers aren't only the ones who walk in. An online store and digital products let you sell to anyone, anywhere.</p>
        </div>
        <div class="ld-why-card">
            <span class="ld-why-icon">&#128101;</span>
            <strong>Grow your team with confidence</strong>
            <p>Add staff with role-based access, so everyone can help run the business without you losing visibility or control.</p>
        </div>
    </div>
</section>

<section class="ld-section" id="features">
    <div class="ld-bento">
        <div class="ld-bento-card ld-bento-green">
            <span class="ld-bento-icon">&#128190;</span>
            <strong>Digital Products</strong>
            <p>Sell ebooks, courses or files with their own checkout page — free on every plan, forever.</p>
        </div>
        <div class="ld-bento-card ld-bento-purple">
            <span class="ld-bento-icon">&#127968;</span>
            <strong>Your Own Online Store</strong>
            <p>Pick a theme, choose a header photo, and start taking orders online in minutes.</p>
        </div>
        <div class="ld-bento-card ld-bento-dark" id="pricing-teaser">
            <strong>Starting at</strong>
            <div class="ld-price">&#8358;3,500<span>/mo</span></div>
            <p>3-day free trial on every plan</p>
            <a href="#pricing">See plans &rarr;</a>
        </div>
        <div class="ld-bento-card ld-bento-light">
            <strong>Secure Payments</strong>
            <p>Every online payment — store or digital product — is processed securely through Flutterwave.</p>
        </div>
    </div>
</section>

<section class="ld-section" id="pricing">
    <div class="ld-section-head">
        <h2>Simple, transparent pricing</h2>
        <p>Every plan starts with a 3-day free trial. Cancel or switch any time.</p>
    </div>
    <div class="pricing-grid" id="pricing-cards">
        <div class="empty-state" style="grid-column: 1 / -1;"><div class="spinner"></div></div>
    </div>
</section>

<section class="ld-section" id="faq">
    <div class="ld-section-head">
        <h2>Frequently asked questions</h2>
        <p>Can't find your answer? Reach out any time — we're happy to help.</p>
    </div>
    <div class="pub-faq" id="faq-list">
        <div class="pub-faq-item">
            <button class="pub-faq-q">Do I need a card to start the free trial? <span>+</span></button>
            <div class="pub-faq-a"><p>No. You get full access to every feature on your chosen plan for 3 days, with no payment details required up front.</p></div>
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
            <div class="pub-faq-a"><p>Yes. Pick your store's category and a matching theme, choose a header image, and edit the text and background images throughout — no code required.</p></div>
        </div>
    </div>
</section>

<div class="ld-cta-band">
    <h2>Ready to run your business the easy way?</h2>
    <p>Start your 3-day free trial — no card required.</p>
    <a href="<?= $base ?>/register" class="ld-pill-btn ld-pill-btn-lg">Start Free Trial</a>
</div>

<footer class="pub-footer">&copy; <?= date('Y') ?> Oripio. All rights reserved.</footer>

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

    const dots = document.querySelectorAll('.ld-dots span');
    let idx = 0;
    setInterval(() => {
        dots.forEach((d) => d.classList.remove('active'));
        idx = (idx + 1) % dots.length;
        dots[idx].classList.add('active');
    }, 2600);
})();
</script>
</body>
</html>