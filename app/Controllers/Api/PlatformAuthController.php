<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;

/**
 * Top-level (no tenant slug in the URL) auth endpoints used by the public
 * marketing site's /register and /login pages. Business owners don't know
 * or need a "slug" up front — registration mints one for them, and login
 * finds their business by their account email.
 *
 * New accounts must verify their email with a 6-digit code before they can
 * log in (see register()/verifyEmail()). This only applies to the owner
 * account created here — staff accounts an owner adds from inside the
 * dashboard log in through the per-tenant AuthController and are never
 * gated by email_verified_at.
 */
class PlatformAuthController
{
    private const CODE_TTL_MINUTES = 15;

    public function register(Request $request): void
    {
        $businessName = trim((string) $request->input('business_name', ''));
        $fullName = trim((string) $request->input('full_name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $phone = trim((string) $request->input('phone', ''));
        $password = (string) $request->input('password', '');

        $errors = [];
        if ($businessName === '') $errors['business_name'] = 'Business name is required';
        if ($fullName === '') $errors['full_name'] = 'Your full name is required';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'A valid email is required';
        if (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters';
        if (!empty($errors)) { Response::error('Please fix the errors below', 422, $errors); return; }

        if (User::findByEmailGlobal($email)) {
            Response::error('An account already exists with this email. Try logging in instead.', 409);
            return;
        }

        $slug = Tenant::uniqueSlugFrom($businessName);
        $tenantId = Tenant::create([
            'slug' => $slug,
            'business_name' => $businessName,
            'owner_email' => $email,
            'owner_phone' => $phone ?: null,
            'currency' => (string) $request->input('currency', 'NGN'),
        ]);

        $userId = User::create([
            'tenant_id' => $tenantId,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone ?: null,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role' => 'owner',
        ]);

        $code = self::generateCode();
        User::setVerificationCode($userId, $code, self::expiryTimestamp());
        self::sendCodeEmail($email, $fullName, $code, 'verify');

        ActivityLog::record($tenantId, $userId, 'auth.register', "$fullName registered $businessName");

        Response::success([
            'email' => $email,
            'tenant' => ['slug' => $slug, 'business_name' => $businessName],
            'requires_verification' => true,
            'trial_days' => 7,
        ], 'We sent a 6-digit code to your email — enter it to verify your account', 201);
    }

    public function login(Request $request): void
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        if ($email === '' || $password === '') { Response::error('Email and password are required', 422); return; }

        $user = User::findByEmailGlobal($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::error('Invalid email or password', 401);
            return;
        }

        if (empty($user['email_verified_at'])) {
            Response::error('Please verify your email to continue', 403, ['requires_verification' => true, 'email' => $email]);
            return;
        }

        $tenant = Tenant::findById((int) $user['tenant_id']);
        if (!$tenant) { Response::error('Business account not found', 404); return; }

        $status = Tenant::accessStatus($tenant);

        $accessToken = Auth::issueAccessToken($user);
        $refreshToken = Auth::issueRefreshToken($user);
        ActivityLog::record((int) $tenant['id'], (int) $user['id'], 'auth.login', $user['full_name'] . ' logged in');

        // Note: we still issue tokens even when expired, so the frontend can send the
        // user straight to /plans and let them pay — feature API routes themselves are
        // separately blocked with 402 by TenantStatusMiddleware until they do.
        Response::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => ['id' => (int) $user['id'], 'full_name' => $user['full_name'], 'email' => $user['email'], 'role' => $user['role'], 'branch_id' => $user['branch_id']],
            'tenant' => ['slug' => $tenant['slug'], 'business_name' => $tenant['business_name'], 'currency' => $tenant['currency']],
            'plan_status' => $status,
        ], $status['status'] === 'expired' ? 'Your free trial has expired. Choose a plan to continue.' : 'Login successful');
    }

    /** Step 2 of registration: confirm the 6-digit code, then log the owner straight in. */
    public function verifyEmail(Request $request): void
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $code = trim((string) $request->input('code', ''));
        if ($email === '' || $code === '') { Response::error('Email and code are required', 422); return; }

        $user = User::findByEmailGlobal($email);
        if (!$user) { Response::error('Account not found', 404); return; }

        if (!empty($user['email_verified_at'])) {
            Response::error('This email is already verified — please log in.', 400);
            return;
        }

        if (empty($user['verification_code']) || !hash_equals((string) $user['verification_code'], $code) || self::isExpired($user['verification_code_expires_at'])) {
            Response::error('That code is invalid or has expired. Request a new one.', 422);
            return;
        }

        User::markEmailVerified((int) $user['id']);
        $tenant = Tenant::findById((int) $user['tenant_id']);
        if (!$tenant) { Response::error('Business account not found', 404); return; }

        $accessToken = Auth::issueAccessToken($user);
        $refreshToken = Auth::issueRefreshToken($user);
        ActivityLog::record((int) $tenant['id'], (int) $user['id'], 'auth.verify_email', $user['full_name'] . ' verified their email');

        Response::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => ['id' => (int) $user['id'], 'full_name' => $user['full_name'], 'email' => $user['email'], 'role' => $user['role'], 'branch_id' => $user['branch_id']],
            'tenant' => ['slug' => $tenant['slug'], 'business_name' => $tenant['business_name'], 'currency' => $tenant['currency']],
            'plan_status' => Tenant::accessStatus($tenant),
        ], 'Email verified — welcome to Bizflow!');
    }

    /** Re-sends a fresh signup verification code. Responds the same way whether or not the email exists, to avoid leaking which emails are registered. */
    public function resendVerification(Request $request): void
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { Response::error('A valid email is required', 422); return; }

        $user = User::findByEmailGlobal($email);
        if ($user && empty($user['email_verified_at'])) {
            $code = self::generateCode();
            User::setVerificationCode((int) $user['id'], $code, self::expiryTimestamp());
            self::sendCodeEmail($email, $user['full_name'], $code, 'verify');
        }

        Response::success(null, 'If that email needs verifying, a new code is on its way.');
    }

    /** Step 1 of "forgot password": email a 6-digit reset code. Responds the same way regardless of whether the email exists. */
    public function forgotPassword(Request $request): void
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { Response::error('A valid email is required', 422); return; }

        $user = User::findByEmailGlobal($email);
        if ($user) {
            $code = self::generateCode();
            User::setResetCode((int) $user['id'], $code, self::expiryTimestamp());
            self::sendCodeEmail($email, $user['full_name'], $code, 'reset');
        }

        Response::success(null, 'If an account exists for that email, a reset code is on its way.');
    }

    /** Step 2 of "forgot password": confirm the code and set a new password. */
    public function resetPassword(Request $request): void
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $code = trim((string) $request->input('code', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $code === '') { Response::error('Email and code are required', 422); return; }
        if (strlen($password) < 6) { Response::error('Password must be at least 6 characters', 422); return; }

        $user = User::findByEmailGlobal($email);
        if (!$user || empty($user['reset_code']) || !hash_equals((string) $user['reset_code'], $code) || self::isExpired($user['reset_code_expires_at'])) {
            Response::error('That code is invalid or has expired. Request a new one.', 422);
            return;
        }

        User::applyPasswordReset((int) $user['id'], password_hash($password, PASSWORD_BCRYPT));
        ActivityLog::record((int) $user['tenant_id'], (int) $user['id'], 'auth.reset_password', $user['full_name'] . ' reset their password');

        Response::success(null, 'Password reset — you can now log in with your new password.');
    }

    private static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private static function expiryTimestamp(): string
    {
        return date('Y-m-d H:i:s', time() + self::CODE_TTL_MINUTES * 60);
    }

    private static function isExpired(?string $expiresAt): bool
    {
        return $expiresAt === null || strtotime($expiresAt) < time();
    }

    private static function sendCodeEmail(string $to, string $fullName, string $code, string $kind): void
    {
        $firstName = trim(explode(' ', trim($fullName))[0] ?? '');
        $heading = $kind === 'verify' ? 'Verify your email' : 'Reset your password';
        $intro = $kind === 'verify'
            ? 'Enter this code to verify your Bizflow account:'
            : 'Enter this code to reset your Bizflow password:';
        $subject = $kind === 'verify' ? 'Your Bizflow verification code' : 'Your Bizflow password reset code';

        $html = '<div style="font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif; max-width:420px; margin:0 auto; padding:32px 24px;">'
            . '<h2 style="color:#16181d; margin:0 0 6px;">' . htmlspecialchars($heading) . '</h2>'
            . '<p style="color:#4a4438; margin:0 0 20px;">Hi ' . htmlspecialchars($firstName ?: 'there') . ', ' . htmlspecialchars($intro) . '</p>'
            . '<div style="background:#e7f8f0; border-radius:12px; padding:18px; text-align:center; font-size:32px; font-weight:800; letter-spacing:8px; color:#0e7a53;">' . htmlspecialchars($code) . '</div>'
            . '<p style="color:#8a8f98; font-size:13px; margin-top:20px;">This code expires in ' . self::CODE_TTL_MINUTES . ' minutes. If you didn\'t request this, you can safely ignore this email.</p>'
            . '</div>';

        Mailer::send($to, $subject, $html, 'Bizflow');
    }
}
