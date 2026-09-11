<?php $base = $GLOBALS['base']; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset your password — Bizflow</title>
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
                    <span class="tz-eyebrow">ACCOUNT RECOVERY</span>
                    <h1>Forgot your password? <span class="accent">No</span> problem.</h1>
                    <p>Enter your email and we'll send you a 6-digit code to get back into your dashboard.</p>
                    <div class="tz-visual-actions">
                        <a href="<?= $base ?>/" class="tz-btn-outline">Explore more</a>
                        <a href="<?= $base ?>/login" class="tz-btn-fill">Back to Log In</a>
                    </div>
                </div>
            </div>

            <div class="tz-form-side">
                <div class="tz-form-mobile-logo"><img src="<?= $base ?>/assets/images/logo.png" alt=""> Bizflow</div>

                <div class="tz-step active" id="step-request">
                    <h2>Reset password<span class="dot">.</span></h2>
                    <div id="request-error" class="tz-error" style="display:none;"></div>
                    <form id="request-form">
                        <div class="tz-field">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="you@business.com" required autofocus>
                            <span class="tz-field-icon">&#9993;</span>
                        </div>
                        <button class="tz-submit-btn" type="submit" id="request-submit">Send Reset Code</button>
                    </form>
                    <p class="tz-member-link">Remembered it? <a href="<?= $base ?>/login">Log In</a></p>
                </div>

                <div class="tz-step" id="step-code">
                    <a href="javascript:void(0)" class="tz-back-link" id="code-back">&larr; Back</a>
                    <h2>Enter code<span class="dot">.</span></h2>
                    <p class="tz-member-link" style="text-align:left; margin-top:-14px;">We sent a 6-digit code to <strong id="code-email-label" style="color:#fff;"></strong>. It expires in 15 minutes.</p>
                    <div id="code-error" class="tz-error" style="display:none;"></div>
                    <form id="code-form">
                        <div class="tz-otp-row">
                            <input type="text" inputmode="numeric" maxlength="1" required>
                            <input type="text" inputmode="numeric" maxlength="1" required>
                            <input type="text" inputmode="numeric" maxlength="1" required>
                            <input type="text" inputmode="numeric" maxlength="1" required>
                            <input type="text" inputmode="numeric" maxlength="1" required>
                            <input type="text" inputmode="numeric" maxlength="1" required>
                        </div>
                        <div class="tz-field">
                            <label>New Password</label>
                            <input type="password" id="reset-password" placeholder="At least 6 characters" minlength="6" required>
                            <span class="tz-field-icon clickable" id="reset-eye">&#128065;</span>
                        </div>
                        <button class="tz-submit-btn" type="submit" id="code-submit">Reset Password</button>
                    </form>
                    <p class="tz-resend">Didn't get it? <button type="button" id="resend-btn">Resend code</button></p>
                </div>

                <div class="tz-step" id="step-done">
                    <h2>Password reset<span class="dot">.</span></h2>
                    <p class="tz-member-link" style="text-align:left; margin-top:-14px;">Your password has been changed. You can now log in with your new password.</p>
                    <a href="<?= $base ?>/login" class="tz-submit-btn" style="display:block; text-align:center; text-decoration:none; box-sizing:border-box;">Go to Log In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/assets/js/auth-otp.js"></script>
<script>
const API = '<?= $base ?>';
const requestStep = document.getElementById('step-request');
const codeStep = document.getElementById('step-code');
const doneStep = document.getElementById('step-done');
let pendingEmail = '';

document.getElementById('reset-eye').addEventListener('click', () => {
    const f = document.getElementById('reset-password');
    f.type = f.type === 'password' ? 'text' : 'password';
});

document.getElementById('request-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('request-submit');
    const errBox = document.getElementById('request-error');
    errBox.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Sending…';

    const form = new FormData(e.target);
    pendingEmail = form.get('email');

    try {
        const res = await fetch(`${API}/api/auth/forgot-password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: pendingEmail }),
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Something went wrong');

        document.getElementById('code-email-label').textContent = pendingEmail;
        requestStep.classList.remove('active');
        codeStep.classList.add('active');
    } catch (err) {
        errBox.textContent = err.message;
        errBox.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = 'Send Reset Code';
    }
});

document.getElementById('code-back').addEventListener('click', () => {
    codeStep.classList.remove('active');
    requestStep.classList.add('active');
});

BizflowOtp.wire('code-form', () => pendingEmail, {
    onSubmit: async (code) => {
        const btn = document.getElementById('code-submit');
        const errBox = document.getElementById('code-error');
        const password = document.getElementById('reset-password').value;
        errBox.style.display = 'none';

        if (password.length < 6) {
            errBox.textContent = 'Password must be at least 6 characters';
            errBox.style.display = 'block';
            return;
        }

        btn.disabled = true; btn.textContent = 'Resetting…';
        try {
            const res = await fetch(`${API}/api/auth/reset-password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: pendingEmail, code, password }),
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Reset failed');
            codeStep.classList.remove('active');
            doneStep.classList.add('active');
        } catch (err) {
            errBox.textContent = err.message;
            errBox.style.display = 'block';
            btn.disabled = false; btn.textContent = 'Reset Password';
        }
    },
    onResend: async () => {
        await fetch(`${API}/api/auth/forgot-password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: pendingEmail }),
        });
    },
});
</script>
</body>
</html>
