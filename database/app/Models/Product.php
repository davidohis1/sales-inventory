<?php
namespace App\Models;

class Product extends BaseModel
{
    protected static function table(): string { return 'products'; }

    public static function search(int $tenantId, string $q = '', ?int $branchId = null, bool $onlyOnStore = false, bool $onlyActive = true, int $limit = 50, ?int $categoryId = null, ?float $minPrice = null, ?float $maxPrice = null): array
    {
        $sql = 'SELECT p.*, c.name AS category_name,
                       (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.id ASC LIMIT 1) AS primary_image,
                       (SELECT COUNT(*) FROM product_images pi2 WHERE pi2.product_id = p.id) AS image_count
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.tenant_id = ?';
        $params = [$tenantId];

        if ($onlyActive) { $sql .= ' AND p.is_active = 1'; }
        if ($onlyOnStore) { $sql .= ' AND p.is_on_store = 1 AND p.quantity > 0'; }
        if ($branchId) { $sql .= ' AND (p.branch_id = ? OR p.branch_id IS NULL)'; $params[] = $branchId; }
        if ($categoryId) { $sql .= ' AND p.category_id = ?'; $params[] = $categoryId; }
        if ($minPrice !== null) { $sql .= ' AND p.selling_price >= ?'; $params[] = $minPrice; }
        if ($maxPrice !== null) { $sql .= ' AND p.selling_price <= ?'; $params[] = $maxPrice; }
        if ($q !== '') {
            $sql .= ' AND (p.name LIKE ? OR p.sku LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        $sql .= ' ORDER BY p.name ASC LIMIT ' . (int) $limit;

        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findWithImages(int $tenantId, int $id): ?array
    {
        $product = self::find($tenantId, $id);
        if (!$product) return null;
        $product['images'] = ProductImage::forProduct($tenantId, $id);
        return $product;
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare('INSERT INTO products
            (tenant_id, category_id, branch_id, name, sku, description, buying_price, selling_price, quantity, min_stock_level, is_active)
            VALUES (?,?,?,?,?,?,?,?,?,?,1)');
        $stmt->execute([
            $data['tenant_id'], $data['category_id'] ?? null, $data['branch_id'] ?? null,
            $data['name'], $data['sku'], $data['description'] ?? null,
            $data['buying_price'] ?? 0, $data['selling_price'] ?? 0,
            $data['quantity'] ?? 0, $data['min_stock_level'] ?? 5,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $tenantId, int $id, array $data): bool
    {
        $allowed = ['category_id', 'branch_id', 'name', 'sku', 'description', 'buying_price', 'selling_price', 'min_stock_level', 'is_active'];
        $fields = [];
        $params = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (empty($fields)) return true;
        $params[] = $id;
        $params[] = $tenantId;
        $stmt = self::db()->prepare('UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ? AND tenant_id = ?');
        return $stmt->execute($params);
    }

    public static function setOnStore(int $tenantId, int $id, bool $onStore): bool
    {
        $stmt = self::db()->prepare('UPDATE products SET is_on_store = ? WHERE id = ? AND tenant_id = ?');
        return $stmt->execute([$onStore ? 1 : 0, $id, $tenantId]);
    }

    public static function adjustStock(int $tenantId, int $id, int $deltaQty): bool
    {
        $stmt = self::db()->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ? AND tenant_id = ?');
        return $stmt->execute([$deltaQty, $id, $tenantId]);
    }

    public static function lowStock(int $tenantId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM products WHERE tenant_id = ? AND is_active = 1 AND quantity <= min_stock_level ORDER BY quantity ASC');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function outOfStockCount(int $tenantId): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM products WHERE tenant_id = ? AND is_active = 1 AND quantity <= 0');
        $stmt->execute([$tenantId]);
        return (int) $stmt->fetchColumn();
    }

    public static function lowStockCount(int $tenantId): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM products WHERE tenant_id = ? AND is_active = 1 AND quantity > 0 AND quantity <= min_stock_level');
        $stmt->execute([$tenantId]);
        return (int) $stmt->fetchColumn();
    }

    public static function stockValue(int $tenantId): float
    {
        $stmt = self::db()->prepare('SELECT COALESCE(SUM(quantity * buying_price), 0) FROM products WHERE tenant_id = ? AND is_active = 1');
        $stmt->execute([$tenantId]);
        return (float) $stmt->fetchColumn();
    }
}
