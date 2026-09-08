<?php
namespace App\Models;

class DigitalProduct extends BaseModel
{
    protected static function table(): string { return 'digital_products'; }

    public static function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = substr($slug, 0, 60);
        if ($slug === '') $slug = 'product-' . random_int(1000, 9999);
        return $slug;
    }

    public static function slugExists(string $slug): bool
    {
        $stmt = self::db()->prepare('SELECT id FROM digital_products WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return (bool) $stmt->fetch();
    }

    public static function uniqueSlugFrom(string $name): string
    {
        // Reserve the same top-level paths as tenant slugs, since digital product
        // pages live in the same bare "/{slug}" namespace.
        $reserved = ['register', 'login', 'pricing', 'plans', 'platformadmin', 'payments', 'api', 'admin', 'assets', 'uploads', 'logout'];
        $base = self::slugify($name);
        if (in_array($base, $reserved, true)) $base .= '-dp';
        $slug = $base;
        $i = 1;
        while (self::slugExists($slug) || \App\Models\Tenant::slugExists($slug) || in_array($slug, $reserved, true)) {
            $i++;
            $slug = $base . '-' . $i;
        }
        return $slug;
    }

    public static function create(int $tenantId, array $data): int
    {
        $stmt = self::db()->prepare(
            "INSERT INTO digital_products (tenant_id, slug, name, price, compare_price, category, description, video_url, images, file_path, file_name, is_published)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $tenantId, $data['slug'], $data['name'], $data['price'], $data['compare_price'] ?? null,
            $data['category'] ?? null, $data['description'] ?? null, $data['video_url'] ?? null,
            json_encode($data['images'] ?? []), $data['file_path'] ?? null, $data['file_name'] ?? null,
            $data['is_published'] ?? 1,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $tenantId, int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        $map = ['name', 'price', 'compare_price', 'category', 'description', 'video_url', 'file_path', 'file_name', 'is_published'];
        foreach ($map as $f) {
            if (array_key_exists($f, $data)) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
        }
        if (array_key_exists('images', $data)) { $fields[] = 'images = ?'; $params[] = json_encode($data['images']); }
        if (empty($fields)) return true;
        $params[] = $id;
        $params[] = $tenantId;
        $stmt = self::db()->prepare('UPDATE digital_products SET ' . implode(', ', $fields) . ' WHERE id = ? AND tenant_id = ?');
        return $stmt->execute($params);
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM digital_products WHERE slug = ? AND is_published = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if ($row) $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        return $row ?: null;
    }

    public static function findForTenant(int $tenantId, int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM digital_products WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        if ($row) $row['images'] = json_decode($row['images'] ?? '[]', true) ?: [];
        return $row ?: null;
    }

    public static function listForTenant(int $tenantId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM digital_products WHERE tenant_id = ? ORDER BY created_at DESC');
        $stmt->execute([$tenantId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) { $r['images'] = json_decode($r['images'] ?? '[]', true) ?: []; }
        return $rows;
    }

    public static function incrementViews(int $id): void
    {
        self::db()->prepare('UPDATE digital_products SET views_count = views_count + 1 WHERE id = ?')->execute([$id]);
    }

    public static function incrementSales(int $id): void
    {
        self::db()->prepare('UPDATE digital_products SET sales_count = sales_count + 1 WHERE id = ?')->execute([$id]);
    }
}
