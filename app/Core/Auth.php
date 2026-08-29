<?php
namespace App\Core;

/**
 * Auth helper: issues + validates JWT access/refresh tokens and exposes
 * the currently authenticated user (set by AuthMiddleware).
 */
class Auth
{
    public static ?array $currentUser = null; // ['id','tenant_id','branch_id','role','full_name','email']

    public static function issueAccessToken(array $user): string
    {
        $ttl = (int) Env::get('JWT_ACCESS_TTL', 3600); // 1 hour
        return JWT::encode([
            'type'      => 'access',
            'sub'       => $user['id'],
            'tenant_id' => $user['tenant_id'],
            'branch_id' => $user['branch_id'],
            'role'      => $user['role'],
            'email'     => $user['email'],
        ], null, $ttl);
    }

    public static function issueRefreshToken(array $user): string
    {
        $ttl = (int) Env::get('JWT_REFRESH_TTL', 1209600); // 14 days
        return JWT::encode([
            'type'      => 'refresh',
            'sub'       => $user['id'],
            'tenant_id' => $user['tenant_id'],
        ], null, $ttl);
    }

    public static function user(): ?array
    {
        return self::$currentUser;
    }

    public static function id(): ?int
    {
        return self::$currentUser['id'] ?? null;
    }

    public static function tenantId(): ?int
    {
        return self::$currentUser['tenant_id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::$currentUser['role'] ?? null;
    }

    public static function hasRole(array $roles): bool
    {
        return in_array(self::role(), $roles, true);
    }
}
