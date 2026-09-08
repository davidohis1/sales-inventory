<?php
namespace App\Models;

class Expense extends BaseModel
{
    protected static function table(): string { return 'expenses'; }

    public static function listForTenant(int $tenantId, array $filters = []): array
    {
        $sql = 'SELECT e.*, ec.name AS category_name FROM expenses e
                LEFT JOIN expense_categories ec ON ec.id = e.category_id
                WHERE e.tenant_id = ?';
        $params = [$tenantId];
        if (!empty($filters['from'])) { $sql .= ' AND e.expense_date >= ?'; $params[] = $filters['from']; }
        if (!empty($filters['to'])) { $sql .= ' AND e.expense_date <= ?'; $params[] = $filters['to']; }
        $sql .= ' ORDER BY e.expense_date DESC, e.id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare('INSERT INTO expenses (tenant_id, branch_id, category_id, user_id, title, amount, note, expense_date)
                                      VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['tenant_id'], $data['branch_id'] ?? null, $data['category_id'] ?? null, $data['user_id'] ?? null,
            $data['title'], $data['amount'], $data['note'] ?? null, $data['expense_date'] ?? date('Y-m-d'),
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function totalForRange(int $tenantId, string $from, string $to): float
    {
        $stmt = self::db()->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE tenant_id = ? AND expense_date BETWEEN ? AND ?');
        $stmt->execute([$tenantId, $from, $to]);
        return (float) $stmt->fetchColumn();
    }

    public static function categories(int $tenantId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM expense_categories WHERE tenant_id = ? ORDER BY name');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function createCategory(int $tenantId, string $name): int
    {
        $stmt = self::db()->prepare('INSERT INTO expense_categories (tenant_id, name) VALUES (?,?)');
        $stmt->execute([$tenantId, $name]);
        return (int) self::db()->lastInsertId();
    }
}
