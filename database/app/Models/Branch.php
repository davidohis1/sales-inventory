<?php
namespace App\Models;

class Branch extends BaseModel
{
    protected static function table(): string { return 'branches'; }

    public static function allForTenant(int $tenantId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM branches WHERE tenant_id = ? ORDER BY is_main DESC, name');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function create(int $tenantId, string $name, ?string $address = null): int
    {
        $stmt = self::db()->prepare('INSERT INTO branches (tenant_id, name, address, is_main, is_active) VALUES (?,?,?,0,1)');
        $stmt->execute([$tenantId, $name, $address]);
        return (int) self::db()->lastInsertId();
    }
}
