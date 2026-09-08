<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in — Bizflow</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="pub-body">

<div class="auth-split">
    <div class="auth-visual-panel">
        <div class="auth-blob-1"></div>
        <div class="auth-blob-2"></div>
        <a href="<?= $base ?>/" class="auth-visual-logo"><img src="<?= $base ?>/assets/images/logo.png" class="dot" alt="Bizflow logo"> Bizflow</a>
        <div class="auth-visual-body">
            <h3>Welcome back — your business is right where you left it.</h3>
            <p>Sales, inventory, your online store, and your team, all in one dashboard. Log in to pick up exactly where you stopped.</p>
            <div class="auth-visual-card">
                <div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733; 4.9</div>
                <p>"Bizflow replaced three notebooks and a spreadsheet. Now I see my whole business in one place."</p>
                <div class="who">Ada Johnson &middot; AJ Tech Gadgets</div>
            </div>
        </div>
        <div class="auth-visual-stats">
            <div><strong>2,000+</strong><span>Businesses</span></div>
            <div><strong>99.9%</strong><span>Uptime</span></div>
            <div><strong>24/7</strong><span>Support</span></div>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="login-card">
            <div class="auth-form-mobile-logo"><img src="<?= $base ?>/assets/images/logo.png" class="dot" alt="Bizflow logo"> Bizflow</div>
            <h2>Welcome back</h2>
            <p class="text-muted" style="margin-top:-8px;">Log in to your business dashboard.</p>
            <form id="login-form">
                <div class="form-group">
                    <label>Email</label>
                    <input class="form-control" type="email" name="email" placeholder="you@business.com" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input class="form-control" type="password" name="password" required>
                </div>
                <div id="login-error" class="form-error" style="display:none; margin-bottom:10px;"></div>
                <button class="btn" type="submit" style="width:100%; justify-content:center;" id="login-submit">Log In</button>
            </form>
            <p class="auth-switch">Don't have an account? <a href="<?= $base ?>/register">Start your free trial</a></p>
            <p class="auth-switch">Platform team? <a href="<?= $base ?>/platformadmin">Admin login</a></p>
        </div>
    </div>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('login-submit');
    const errBox = document.getElementById('login-error');
    errBox.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Logging in…';

    const form = new FormData(e.target);
    const payload = Object.fromEntries(form.entries());

    try {
        const res = await fetch('<?= $base ?>/api/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Login failed');

        localStorage.setItem('access_token', json.data.access_token);
        localStorage.setItem('refresh_token', json.data.refresh_token);
        localStorage.setItem('user', JSON.stringify(json.data.user));
        localStorage.setItem('plan', JSON.stringify(json.data.plan_status));
        window.location.href = `<?= $base ?>/${json.data.tenant.slug}portal`;
    } catch (err) {
        errBox.textContent = err.message;
        errBox.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Log In';
    }
});
</script>
</body>
</html>
