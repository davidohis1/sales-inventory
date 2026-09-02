<?php
namespace App\Middleware;

use App\Core\JWT;
use App\Core\Request;
use App\Core\Response;
use App\Models\PlatformAdmin;

class PlatformAdminMiddleware
{
    public static ?array $current = null;

    public function __invoke(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) { Response::error('Unauthorized: missing token', 401); return false; }

        $payload = JWT::decode($token);
        if (!$payload || ($payload['type'] ?? '') !== 'platform_admin_access') {
            Response::error('Unauthorized: invalid or expired token', 401);
            return false;
        }

        $admin = PlatformAdmin::find((int) $payload['sub']);
        if (!$admin) { Response::error('Unauthorized: admin not found', 401); return false; }

        self::$current = ['id' => (int) $admin['id'], 'full_name' => $admin['full_name'], 'email' => $admin['email']];
        return true;
    }
}
