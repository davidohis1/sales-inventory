<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\JWT;
use App\Core\Request;
use App\Core\Response;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ActivityLog;

class AuthController
{
    public function login(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Business not found', 404); return; }

        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        if ($email === '' || $password === '') { Response::error('Email and password are required', 422); return; }

        $user = User::findByEmail((int) $tenant['id'], $email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
            return;
        }
        if (!$user['is_active']) { Response::error('This account has been disabled', 403); return; }

        $accessToken = Auth::issueAccessToken($user);
        $refreshToken = Auth::issueRefreshToken($user);

        ActivityLog::record((int) $tenant['id'], (int) $user['id'], 'auth.login', $user['full_name'] . ' logged in');

        Response::success([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => [
                'id' => (int) $user['id'], 'full_name' => $user['full_name'], 'email' => $user['email'],
                'role' => $user['role'], 'branch_id' => $user['branch_id'],
            ],
            'tenant' => ['slug' => $tenant['slug'], 'business_name' => $tenant['business_name'], 'currency' => $tenant['currency']],
        ], 'Login successful');
    }

    public function refresh(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Business not found', 404); return; }

        $refreshToken = (string) $request->input('refresh_token', '');
        $payload = JWT::decode($refreshToken);
        if (!$payload || ($payload['type'] ?? '') !== 'refresh' || (int) $payload['tenant_id'] !== (int) $tenant['id']) {
            Response::error('Invalid or expired refresh token', 401);
            return;
        }

        $user = User::find((int) $tenant['id'], (int) $payload['sub']);
        if (!$user || !$user['is_active']) { Response::error('Account not found or inactive', 401); return; }

        Response::success(['access_token' => Auth::issueAccessToken($user)], 'Token refreshed');
    }

    public function me(Request $request): void
    {
        Response::success(Auth::user());
    }
}
