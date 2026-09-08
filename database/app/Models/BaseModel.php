<?php
namespace App\Models;

use App\Core\Database;
use PDO;

abstract class BaseModel
{
    protected static function db(): PDO
    {
        return Database::connect();
    }

    protected static function table(): string
    {
        throw new \RuntimeException('table() not implemented');
    }

    public static function find(int $tenantId, int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM ' . static::table() . ' WHERE id = ? AND tenant_id = ? LIMIT 1');
        $stmt->execute([$id, $tenantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function delete(int $tenantId, int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM ' . static::table() . ' WHERE id = ? AND tenant_id = ?');
        return $stmt->execute([$id, $tenantId]);
    }
}
