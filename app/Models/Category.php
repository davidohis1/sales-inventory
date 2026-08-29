<?php
namespace App\Models;

class Category extends BaseModel
{
    protected static function table(): string { return 'categories'; }

    public static function allForTenant(int $tenantId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM categories WHERE tenant_id = ? ORDER BY name');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function create(int $tenantId, string $name): int
    {
        $stmt = self::db()->prepare('INSERT INTO categories (tenant_id, name) VALUES (?, ?)');
        $stmt->execute([$tenantId, $name]);
        return (int) self::db()->lastInsertId();
    }
}
