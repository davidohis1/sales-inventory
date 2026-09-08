<?php
namespace App\Models;

class ActivityLog extends BaseModel
{
    protected static function table(): string { return 'activity_logs'; }

    public static function record(int $tenantId, ?int $userId, string $action, ?string $description = null, ?array $meta = null): void
    {
        $stmt = self::db()->prepare('INSERT INTO activity_logs (tenant_id, user_id, action, description, meta) VALUES (?,?,?,?,?)');
        $stmt->execute([$tenantId, $userId, $action, $description, $meta ? json_encode($meta) : null]);
    }

    public static function listForTenant(int $tenantId, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $countStmt = self::db()->prepare('SELECT COUNT(*) FROM activity_logs WHERE tenant_id = ?');
        $countStmt->execute([$tenantId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = self::db()->prepare('SELECT al.*, u.full_name AS user_name FROM activity_logs al
                                      LEFT JOIN users u ON u.id = al.user_id
                                      WHERE al.tenant_id = ? ORDER BY al.created_at DESC
                                      LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
        $stmt->execute([$tenantId]);

        return [
            'data' => $stmt->fetchAll(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public static function staffPerformance(int $tenantId, string $from, string $to): array
    {
        $stmt = self::db()->prepare("SELECT u.id, u.full_name, u.role,
                COUNT(s.id) AS total_sales, COALESCE(SUM(s.total),0) AS total_revenue
                FROM users u
                LEFT JOIN sales s ON s.user_id = u.id AND s.tenant_id = u.tenant_id
                    AND s.created_at BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59')
                WHERE u.tenant_id = ?
                GROUP BY u.id, u.full_name, u.role
                ORDER BY total_revenue DESC");
        $stmt->execute([$from, $to, $tenantId]);
        return $stmt->fetchAll();
    }
}
