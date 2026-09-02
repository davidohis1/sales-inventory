<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in — Oripio</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="pub-body">
<nav class="pub-nav">
    <a href="<?= $base ?>/" class="pub-logo"><span class="logo-dot"></span> Oripio</a>
    <div></div>
    <div class="pub-nav-cta"><span class="text-muted" style="font-size:13px;">New here?</span><a href="<?= $base ?>/register" class="btn">Start Free Trial</a></div>
</nav>

<div class="login-page" style="min-height: calc(100vh - 68px);">
    <div class="login-card">
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
