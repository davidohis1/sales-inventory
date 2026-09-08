<?php
namespace App\Models;

class HeaderImage
{
    protected static function db(): \PDO { return \App\Core\Database::connect(); }

    public static function forCategory(string $storeType): array
    {
        $stmt = self::db()->prepare('SELECT * FROM header_images WHERE store_type = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$storeType]);
        return $stmt->fetchAll();
    }

    /** Every image, grouped by category — for the platform admin's management view. */
    public static function allGrouped(): array
    {
        $rows = self::db()->query('SELECT * FROM header_images ORDER BY store_type ASC, sort_order ASC, id ASC')->fetchAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['store_type']][] = $row;
        }
        return $grouped;
    }

    public static function create(string $storeType, string $imagePath, ?string $label = null): int
    {
        $stmt = self::db()->prepare('INSERT INTO header_images (store_type, image_path, label) VALUES (?,?,?)');
        $stmt->execute([$storeType, $imagePath, $label]);
        return (int) self::db()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM header_images WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM header_images WHERE id = ?');
        return $stmt->execute([$id]);
    }
}
