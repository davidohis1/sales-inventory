<?php
namespace App\Models;

class ProductImage extends BaseModel
{
    protected static function table(): string { return 'product_images'; }

    public static function forProduct(int $tenantId, int $productId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM product_images WHERE tenant_id = ? AND product_id = ? ORDER BY is_primary DESC, id ASC');
        $stmt->execute([$tenantId, $productId]);
        return $stmt->fetchAll();
    }

    public static function countForProduct(int $tenantId, int $productId): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM product_images WHERE tenant_id = ? AND product_id = ?');
        $stmt->execute([$tenantId, $productId]);
        return (int) $stmt->fetchColumn();
    }

    public static function add(int $tenantId, int $productId, string $path, bool $isPrimary = false): int
    {
        if ($isPrimary) {
            $stmt = self::db()->prepare('UPDATE product_images SET is_primary = 0 WHERE tenant_id = ? AND product_id = ?');
            $stmt->execute([$tenantId, $productId]);
        }
        $stmt = self::db()->prepare('INSERT INTO product_images (tenant_id, product_id, image_path, is_primary) VALUES (?,?,?,?)');
        $stmt->execute([$tenantId, $productId, $path, $isPrimary ? 1 : 0]);
        return (int) self::db()->lastInsertId();
    }
}
