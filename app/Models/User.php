<?php
namespace App\Models;

class User extends BaseModel
{
    protected static function table(): string { return 'users'; }

    public static function findByEmail(int $tenantId, string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM users WHERE tenant_id = ? AND email = ? LIMIT 1');
        $stmt->execute([$tenantId, $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function allForTenant(int $tenantId, string $search = ''): array
    {
        $sql = 'SELECT id, tenant_id, branch_id, full_name, email, phone, role, is_active, created_at
                FROM users WHERE tenant_id = ?';
        $params = [$tenantId];
        if ($search !== '') {
            $sql .= ' AND (full_name LIKE ? OR email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare('INSERT INTO users (tenant_id, branch_id, full_name, email, phone, password_hash, role, is_active)
                                      VALUES (?,?,?,?,?,?,?,1)');
        $stmt->execute([
            $data['tenant_id'], $data['branch_id'] ?? null, $data['full_name'], $data['email'],
            $data['phone'] ?? null, $data['password_hash'], $data['role'] ?? 'staff',
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $tenantId, int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        foreach (['full_name', 'phone', 'role', 'branch_id', 'is_active'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (isset($data['password_hash'])) {
            $fields[] = 'password_hash = ?';
            $params[] = $data['password_hash'];
        }
        if (empty($fields)) return true;
        $params[] = $id;
        $params[] = $tenantId;
        $stmt = self::db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ? AND tenant_id = ?');
        return $stmt->execute($params);
    }
}
