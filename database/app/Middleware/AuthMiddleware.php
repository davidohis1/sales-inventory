<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\JWT;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

/**
 * Validates the JWT access token, loads the user, and ensures the token's
 * tenant matches the tenant slug in the URL (tenant isolation).
 */
class AuthMiddleware
{
    public function __invoke(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            Response::error('Unauthorized: missing token', 401);
            return false;
        }

        $payload = JWT::decode($token);
        if (!$payload || ($payload['type'] ?? '') !== 'access') {
            Response::error('Unauthorized: invalid or expired token', 401);
            return false;
        }

        $slug = $request->param('slug');
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT u.*, t.slug AS tenant_slug FROM users u
                                JOIN tenants t ON t.id = u.tenant_id
                                WHERE u.id = ? AND u.tenant_id = ? AND u.is_active = 1 LIMIT 1");
        $stmt->execute([$payload['sub'], $payload['tenant_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('Unauthorized: account not found or inactive', 401);
            return false;
        }

        if ($slug !== null && $user['tenant_slug'] !== $slug) {
            Response::error('Forbidden: tenant mismatch', 403);
            return false;
        }

        Auth::$currentUser = [
            'id'        => (int) $user['id'],
            'tenant_id' => (int) $user['tenant_id'],
            'branch_id' => $user['branch_id'] !== null ? (int) $user['branch_id'] : null,
            'role'      => $user['role'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
        ];

        return true;
    }
}
