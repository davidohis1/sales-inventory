<?php
namespace App\Models;

class StockLog extends BaseModel
{
    protected static function table(): string { return 'stock_logs'; }

    public static function log(int $tenantId, int $productId, int $changeQty, string $reason, ?int $userId = null, ?int $branchId = null, ?string $note = null): int
    {
        $stmt = self::db()->prepare('INSERT INTO stock_logs (tenant_id, product_id, branch_id, user_id, change_qty, reason, note)
                                      VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$tenantId, $productId, $branchId, $userId, $changeQty, $reason, $note]);
        return (int) self::db()->lastInsertId();
    }

    public static function historyForProduct(int $tenantId, int $productId, int $limit = 100): array
    {
        $stmt = self::db()->prepare('SELECT sl.*, u.full_name AS user_name FROM stock_logs sl
                                      LEFT JOIN users u ON u.id = sl.user_id
                                      WHERE sl.tenant_id = ? AND sl.product_id = ?
                                      ORDER BY sl.created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute([$tenantId, $productId]);
        return $stmt->fetchAll();
    }
}
