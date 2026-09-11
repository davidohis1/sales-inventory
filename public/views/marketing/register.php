<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Start your free trial — Bizflow</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/auth.css">
</head>
<body class="tz-body">

<div class="tz-page">
    <div class="tz-card">
        <nav class="tz-nav">
            <a href="<?= $base ?>/" class="tz-logo"><img src="<?= $base ?>/assets/images/logo.png" alt=""> Bizflow<span class="t">.</span></a>
            <div class="tz-nav-links">
                <a href="<?= $base ?>/">Home</a>
                <a href="<?= $base ?>/login">Log In</a>
                <a href="<?= $base ?>/register" class="active">Join</a>
                <a href="<?= $base ?>/#features">About Us</a>
            </div>
            <div class="tz-nav-search">&#128269; Search</div>
        </nav>

        <div class="tz-split">
            <div class="tz-visual">
                <div class="tz-visual-content">
                    <span class="tz-eyebrow">JOIN FOR FREE</span>
                    <h1>Run your business <span class="accent">your</span> way, from day one.</h1>
                    <p>Get started with the easiest, most reliable dashboard to manage sales, stock, and your storefront.</p>
                    <div class="tz-visual-actions">
                        <a href="<?= $base ?>/" class="tz-btn-outline">Explore more</a>
                        <a href="<?= $base ?>/#pricing" class="tz-btn-fill">View Pricing</a>
                    </div>
                </div>
            </div>

            <div class="tz-form-side">
                <div class="tz-form-mobile-logo"><img src="<?= $base ?>/assets/images/logo.png" alt=""> Bizflow</div>

                <div class="tz-step active" id="step-register">
                    <h2>Create new account<span class="dot">.</span></h2>
                    <div id="register-error" class="tz-error" style="display:none;"></div>
                    <form id="register-form">
                        <div class="tz-field">
                            <label>Business Name</label>
                            <input name="business_name" placeholder="e.g. AJ Electronics" required autofocus>
                            <span class="tz-field-icon">&#127970;</span>
                        </div>
                        <div class="tz-field-row">
                            <div class="tz-field">
                                <label>First Name</label>
                                <input name="first_name" placeholder="Jane" required>
                                <span class="tz-field-icon">&#128100;</span>
                            </div>
                            <div class="tz-field">
                                <label>Last Name</label>
                                <input name="last_name" placeholder="Doe" required>
                                <span class="tz-field-icon">&#128100;</span>
                            </div>
                        </div>
                        <div class="tz-field">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="you@business.com" required>
                            <span class="tz-field-icon">&#9993;</span>
                        </div>
                        <div class="tz-field">
                            <label>Password</label>
                            <input type="password" name="password" id="register-password" placeholder="At least 6 characters" minlength="6" required>
                            <span class="tz-field-icon clickable" id="register-eye">&#128065;</span>
                        </div>
                        <p class="tz-member-link">Already A Member? <a href="<?= $base ?>/login">Log In</a></p>
                        <button class="tz-submit-btn" type="submit" id="register-submit">Create Account</button>
                    </form>
                </div>

                <div class="tz-step" id="step-verify">
                    <a href="javascript:void(0)" class="tz-back-link" id="verify-back">&larr; Back</a>
                    <h2>Verify your email<span class="dot">.</span></h2>
                    <p class="tz-member-link" style="text-align:left; margin-top:-14px;">Enter the 6-digit code we sent to <strong id="verify-email-label" style="color:#fff;"></strong>.</p>
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
                        <button class="tz-submit-btn" type="submit" id="verify-submit">Verify Account</button>
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
const registerStep = document.getElementById('step-register');
const verifyStep = document.getElementById('step-verify');
let pendingEmail = '';

document.getElementById('register-eye').addEventListener('click', () => {
    const f = document.getElementById('register-password');
    f.type = f.type === 'password' ? 'text' : 'password';
});

document.getElementById('register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('register-submit');
    const errBox = document.getElementById('register-error');
    errBox.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Creating your account…';

    const form = new FormData(e.target);
    const raw = Object.fromEntries(form.entries());
    const payload = {
        business_name: raw.business_name,
        full_name: `${raw.first_name} ${raw.last_name}`.trim(),
        email: raw.email,
        password: raw.password,
    };

    try {
        const res = await fetch(`${API}/api/auth/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (!json.success) {
            const firstError = json.errors ? Object.values(json.errors)[0] : json.message;
            throw new Error(firstError || 'Registration failed');
        }

        pendingEmail = json.data.email;
        document.getElementById('verify-email-label').textContent = pendingEmail;
        registerStep.classList.remove('active');
        verifyStep.classList.add('active');
    } catch (err) {
        errBox.textContent = err.message;
        errBox.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Create Account';
    }
});

document.getElementById('verify-back').addEventListener('click', () => {
    verifyStep.classList.remove('active');
    registerStep.classList.add('active');
});

BizflowOtp.wire('verify-form', () => pendingEmail, {
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
            btn.disabled = false; btn.textContent = 'Verify Account';
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
</script>
</body>
</html>
