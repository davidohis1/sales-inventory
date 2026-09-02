<?php
namespace App\Controllers\Api;

use App\Core\Auth;
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
 */
class PlatformAuthController
{
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

        $user = User::find($tenantId, $userId);

        $accessToken = Auth::issueAccessToken($user);
        $refreshToken = Auth::issueRefreshToken($user);
        ActivityLog::record($tenantId, $userId, 'auth.register', "$fullName registered $businessName");

        Response::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => ['id' => $userId, 'full_name' => $fullName, 'email' => $email, 'role' => 'owner'],
            'tenant' => ['slug' => $slug, 'business_name' => $businessName],
            'trial_days' => 3,
        ], 'Business registered — your 3-day free trial has started', 201);
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
}
