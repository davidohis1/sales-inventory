<?php
namespace App\Models;

class PlatformAdmin
{
    protected static function db(): \PDO { return \App\Core\Database::connect(); }

    public static function findByEmail(string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM platform_admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM platform_admins WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $fullName, string $email, string $password): int
    {
        $stmt = self::db()->prepare('INSERT INTO platform_admins (full_name, email, password_hash) VALUES (?,?,?)');
        $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_BCRYPT)]);
        return (int) self::db()->lastInsertId();
    }

    public static function count(): int
    {
        return (int) self::db()->query('SELECT COUNT(*) FROM platform_admins')->fetchColumn();
    }
}
