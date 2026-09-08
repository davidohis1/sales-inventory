<?php
namespace App\Controllers\Admin;

use App\Core\Env;
use App\Core\JWT;
use App\Core\Request;
use App\Core\Response;
use App\Models\PlatformAdmin;

class AdminAuthController
{
    public function login(Request $request): void
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        if ($email === '' || $password === '') { Response::error('Email and password are required', 422); return; }

        $admin = PlatformAdmin::findByEmail($email);
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            Response::error('Invalid email or password', 401);
            return;
        }

        $ttl = (int) Env::get('JWT_ACCESS_TTL', 3600);
        $token = JWT::encode(['type' => 'platform_admin_access', 'sub' => $admin['id'], 'email' => $admin['email']], null, $ttl);

        Response::success([
            'access_token' => $token,
            'admin' => ['id' => (int) $admin['id'], 'full_name' => $admin['full_name'], 'email' => $admin['email']],
        ], 'Login successful');
    }
}
