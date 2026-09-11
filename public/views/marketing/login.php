<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in — Bizflow</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/auth.css">
</head>
<body class="tz-body">

<div class="tz-page">
    <div class="tz-card">
        <nav class="tz-nav">
            <a href="<?= $base ?>/" class="tz-logo"><img src="<?= $base ?>/assets/images/logo.png" alt=""> Bizflow<span class="t">.</span></a>
            <div class="tz-nav-links">
                <a href="<?= $base ?>/">Home</a>
                <a href="<?= $base ?>/login" class="active">Log In</a>
                <a href="<?= $base ?>/register">Join</a>
                <a href="<?= $base ?>/#features">About Us</a>
            </div>
            <div class="tz-nav-search">&#128269; Search</div>
        </nav>

        <div class="tz-split">
            <div class="tz-visual">
                <div class="tz-visual-content">
                    <span class="tz-eyebrow">WELCOME BACK</span>
                    <h1>Pick up right where <span class="accent">you</span> left off.</h1>
                    <p>Sales, inventory, your team, and your online store — all waiting for you in one dashboard.</p>
                    <div class="tz-visual-actions">
                        <a href="<?= $base ?>/" class="tz-btn-outline">Explore more</a>
                        <a href="<?= $base ?>/register" class="tz-btn-fill">Start Free Trial</a>
                    </div>
                </div>
            </div>

            <div class="tz-form-side">
                <div class="tz-form-mobile-logo"><img src="<?= $base ?>/assets/images/logo.png" alt=""> Bizflow</div>

                <div class="tz-step active" id="step-login">
                    <h2>Welcome back<span class="dot">.</span></h2>
                    <div id="login-error" class="tz-error" style="display:none;"></div>
                    <form id="login-form">
                        <div class="tz-field">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="you@business.com" required autofocus>
                            <span class="tz-field-icon">&#9993;</span>
                        </div>
                        <div class="tz-field">
                            <label>Password</label>
                            <input type="password" name="password" id="login-password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
                            <span class="tz-field-icon clickable" id="login-eye">&#128065;</span>
                        </div>
                        <div class="tz-inline-link"><a href="<?= $base ?>/forgot-password">Forgot password?</a></div>
                        <button class="tz-submit-btn" type="submit" id="login-submit">Log In</button>
                    </form>
                    <p class="tz-member-link">New here? <a href="<?= $base ?>/register">Create Account</a></p>
                    <p class="tz-member-link" style="margin-top:-8px;"><a href="<?= $base ?>/platformadmin">Platform admin login</a></p>
                </div>

                <div class="tz-step" id="step-verify">
                    <a href="javascript:void(0)" class="tz-back-link" id="verify-back">&larr; Back to log in</a>
                    <h2>Verify your email<span class="dot">.</span></h2>
                    <p class="tz-member-link" style="text-align:left; margin-top:-14px;">Enter the 6-digit code we sent to <strong id="verify-email-label" style="color:#fff;"></strong> to finish logging in.</p>
                    <div id="verify-error" class="tz-error" style="display:none;"></div>
                    <form id="verify-form">
                        <div class="tz-otp-row">
                            <input type="text" inputmode="numeric" maxlength="1" required>
                            <input type="text" inputmode="numeric" maxlength="1" required>
                            <input type="text" inputmode="numeric" maxlength="1" required>
                            <input type="text" inputmode="numeric" maxlength="1" required>
                            <input type="text" inputmode="numeric" maxlength="1" required>
                            <input type="text" inputmode="numeric" maxlength="1" required>
                        </div>
                        <button class="tz-submit-btn" type="submit" id="verify-submit">Verify &amp; Log In</button>
                    </form>
                    <p class="tz-resend">Didn't get it? <button type="button" id="resend-btn">Resend code</button></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/assets/js/auth-otp.js"></script>
<script>
const API = '<?= $base ?>';
const loginStep = document.getElementById('step-login');
const verifyStep = document.getElementById('step-verify');
let pendingEmail = '';

document.getElementById('login-eye').addEventListener('click', () => {
    const f = document.getElementById('login-password');
    f.type = f.type === 'password' ? 'text' : 'password';
});

document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('login-submit');
    const errBox = document.getElementById('login-error');
    errBox.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Logging in…';

    const form = new FormData(e.target);
    const payload = Object.fromEntries(form.entries());

    try {
        const res = await fetch(`${API}/api/auth/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (!json.success) {
            if (json.errors && json.errors.requires_verification) {
                pendingEmail = json.errors.email || payload.email;
                document.getElementById('verify-email-label').textContent = pendingEmail;
                loginStep.classList.remove('active');
                verifyStep.classList.add('active');
                btn.disabled = false; btn.textContent = 'Log In';
                return;
            }
            throw new Error(json.message || 'Login failed');
        }

        localStorage.setItem('access_token', json.data.access_token);
        localStorage.setItem('refresh_token', json.data.refresh_token);
        localStorage.setItem('user', JSON.stringify(json.data.user));
        localStorage.setItem('plan', JSON.stringify(json.data.plan_status));
        window.location.href = `${API}/${json.data.tenant.slug}portal`;
    } catch (err) {
        errBox.textContent = err.message;
        errBox.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Log In';
    }
});

document.getElementById('verify-back').addEventListener('click', () => {
    verifyStep.classList.remove('active');
    loginStep.classList.add('active');
});

BizflowOtp.wire('verify-form', pendingEmailGetter, {
    onSubmit: async (code) => {
        const btn = document.getElementById('verify-submit');
        const errBox = document.getElementById('verify-error');
        errBox.style.display = 'none';
        btn.disabled = true; btn.textContent = 'Verifying…';
        try {
            const res = await fetch(`${API}/api/auth/verify-email`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: pendingEmail, code }),
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Verification failed');
            localStorage.setItem('access_token', json.data.access_token);
            localStorage.setItem('refresh_token', json.data.refresh_token);
            localStorage.setItem('user', JSON.stringify(json.data.user));
            localStorage.setItem('plan', JSON.stringify(json.data.plan_status));
            window.location.href = `${API}/${json.data.tenant.slug}portal`;
        } catch (err) {
            errBox.textContent = err.message;
            errBox.style.display = 'block';
            btn.disabled = false; btn.textContent = 'Verify & Log In';
        }
    },
    onResend: async () => {
        await fetch(`${API}/api/auth/resend-verification`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: pendingEmail }),
        });
    },
});
function pendingEmailGetter() { return pendingEmail; }
</script>
</body>
</html>
