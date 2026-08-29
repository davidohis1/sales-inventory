<?php
namespace App\Models;

class Customer extends BaseModel
{
    protected static function table(): string { return 'customers'; }

    public static function search(int $tenantId, string $q = ''): array
    {
        $sql = 'SELECT * FROM customers WHERE tenant_id = ?';
        $params = [$tenantId];
        if ($q !== '') {
            $sql .= ' AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)';
            $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
        }
        $sql .= ' ORDER BY name ASC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function findByExactName(int $tenantId, string $name): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM customers WHERE tenant_id = ? AND LOWER(name) = LOWER(?) LIMIT 1');
        $stmt->execute([$tenantId, trim($name)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Used by Quick Sale: the cashier types a name (never picks from a dropdown).
     * If a customer with that exact name already exists, reuse it; otherwise
     * create a new customer record on the fly.
     */
    public static function findOrCreateByName(int $tenantId, string $name, ?string $phone = null): array
    {
        $existing = self::findByExactName($tenantId, $name);
        if ($existing) return $existing;

        $id = self::create(['tenant_id' => $tenantId, 'name' => trim($name), 'phone' => $phone]);
        return self::find($tenantId, $id);
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare('INSERT INTO customers (tenant_id, name, phone, email, address, credit_limit, outstanding_debt)
                                      VALUES (?,?,?,?,?,?,0)');
        $stmt->execute([
            $data['tenant_id'], $data['name'], $data['phone'] ?? null, $data['email'] ?? null,
            $data['address'] ?? null, $data['credit_limit'] ?? 0,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $tenantId, int $id, array $data): bool
    {
        $allowed = ['name', 'phone', 'email', 'address', 'credit_limit'];
        $fields = []; $params = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
        }
        if (empty($fields)) return true;
        $params[] = $id; $params[] = $tenantId;
        $stmt = self::db()->prepare('UPDATE customers SET ' . implode(', ', $fields) . ' WHERE id = ? AND tenant_id = ?');
        return $stmt->execute($params);
    }

    public static function adjustDebt(int $tenantId, int $id, float $delta): bool
    {
        $stmt = self::db()->prepare('UPDATE customers SET outstanding_debt = outstanding_debt + ? WHERE id = ? AND tenant_id = ?');
        return $stmt->execute([$delta, $id, $tenantId]);
    }

    public static function purchaseHistory(int $tenantId, int $customerId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM sales WHERE tenant_id = ? AND customer_id = ? ORDER BY created_at DESC');
        $stmt->execute([$tenantId, $customerId]);
        return $stmt->fetchAll();
    }

    public static function paymentHistory(int $tenantId, int $customerId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM customer_payments WHERE tenant_id = ? AND customer_id = ? ORDER BY created_at DESC');
        $stmt->execute([$tenantId, $customerId]);
        return $stmt->fetchAll();
    }
}
