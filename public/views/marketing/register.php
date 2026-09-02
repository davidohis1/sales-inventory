<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Start your free trial — Oripio</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="pub-body">
<nav class="pub-nav">
    <a href="<?= $base ?>/" class="pub-logo"><span class="logo-dot"></span> Oripio</a>
    <div></div>
    <div class="pub-nav-cta"><span class="text-muted" style="font-size:13px;">Already have an account?</span><a href="<?= $base ?>/login" class="btn btn-secondary">Log in</a></div>
</nav>

<div class="login-page" style="min-height: calc(100vh - 68px);">
    <div class="login-card wide">
        <span class="trial-badge">&#9889; 3-day free trial — no card required</span>
        <h2>Register your business</h2>
        <p class="text-muted" style="margin-top:-8px;">Set up your account in under a minute.</p>
        <form id="register-form">
            <div class="form-group">
                <label>Business name</label>
                <input class="form-control" name="business_name" placeholder="e.g. AJ Electronics" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Your full name</label>
                    <input class="form-control" name="full_name" placeholder="Jane Doe" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input class="form-control" name="phone" placeholder="080...">
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input class="form-control" type="email" name="email" placeholder="you@business.com" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input class="form-control" type="password" name="password" placeholder="At least 6 characters" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Currency</label>
                    <select class="form-control" name="currency">
                        <option value="NGN" selected>NGN — Naira</option>
                        <option value="GHS">GHS — Cedi</option>
                        <option value="KES">KES — Shilling</option>
                        <option value="USD">USD — Dollar</option>
                    </select>
                </div>
            </div>
            <div id="register-error" class="form-error" style="display:none; margin-bottom:10px;"></div>
            <button class="btn" type="submit" style="width:100%; justify-content:center;" id="register-submit">Start Free Trial</button>
        </form>
        <p class="auth-switch">By continuing you agree to the fair-use of your 3-day free trial. Already registered? <a href="<?= $base ?>/login">Log in</a></p>
    </div>
</div>

<script>
document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('register-submit');
    const errBox = document.getElementById('register-error');
    errBox.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Creating your account…';

    const form = new FormData(e.target);
    const payload = Object.fromEntries(form.entries());

    try {
        const res = await fetch('<?= $base ?>/api/auth/register', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (!json.success) {
            const firstError = json.errors ? Object.values(json.errors)[0] : json.message;
            throw new Error(firstError || 'Registration failed');
        }
        localStorage.setItem('access_token', json.data.access_token);
        localStorage.setItem('refresh_token', json.data.refresh_token);
        localStorage.setItem('user', JSON.stringify(json.data.user));
        window.location.href = `<?= $base ?>/${json.data.tenant.slug}portal`;
    } catch (err) {
        errBox.textContent = err.message;
        errBox.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Start Free Trial';
    }
});
</script>
</body>
</html>
