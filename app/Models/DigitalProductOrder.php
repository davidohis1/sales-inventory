<?php
namespace App\Models;

class DigitalProductOrder extends BaseModel
{
    protected static function table(): string { return 'digital_product_orders'; }

    public static function create(int $productId, int $tenantId, string $buyerName, string $buyerEmail, float $amount, string $txRef): int
    {
        $stmt = self::db()->prepare(
            "INSERT INTO digital_product_orders (product_id, tenant_id, buyer_name, buyer_email, amount, tx_ref, status) VALUES (?,?,?,?,?,?,'pending')"
        );
        $stmt->execute([$productId, $tenantId, $buyerName, $buyerEmail, $amount, $txRef]);
        return (int) self::db()->lastInsertId();
    }

    public static function findByTxRef(string $txRef): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM digital_product_orders WHERE tx_ref = ? LIMIT 1');
        $stmt->execute([$txRef]);
        return $stmt->fetch() ?: null;
    }

    public static function findByToken(string $token): ?array
    {
        $stmt = self::db()->prepare("SELECT * FROM digital_product_orders WHERE download_token = ? AND status = 'successful' LIMIT 1");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function markSuccessful(string $txRef, string $flwTransactionId): string
    {
        $token = bin2hex(random_bytes(24));
        $stmt = self::db()->prepare("UPDATE digital_product_orders SET status = 'successful', flw_transaction_id = ?, download_token = ? WHERE tx_ref = ?");
        $stmt->execute([$flwTransactionId, $token, $txRef]);
        return $token;
    }

    public static function markFailed(string $txRef): void
    {
        self::db()->prepare("UPDATE digital_product_orders SET status = 'failed' WHERE tx_ref = ?")->execute([$txRef]);
    }

    /** Revenue + sales count for the seller dashboard, optionally within a date range. */
    public static function summaryForTenant(int $tenantId, ?string $from = null, ?string $to = null): array
    {
        $sql = "SELECT COALESCE(SUM(amount),0) AS revenue, COUNT(*) AS sales FROM digital_product_orders WHERE tenant_id = ? AND status = 'successful'";
        $params = [$tenantId];
        if ($from) { $sql .= ' AND created_at >= ?'; $params[] = $from . ' 00:00:00'; }
        if ($to) { $sql .= ' AND created_at <= ?'; $params[] = $to . ' 23:59:59'; }
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return ['revenue' => (float) $row['revenue'], 'sales' => (int) $row['sales']];
    }

    public static function recentForTenant(int $tenantId, int $limit = 30): array
    {
        $stmt = self::db()->prepare(
            "SELECT o.*, p.name AS product_name FROM digital_product_orders o
             JOIN digital_products p ON p.id = o.product_id
             WHERE o.tenant_id = ? AND o.status = 'successful' ORDER BY o.created_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Total ever earned via Flutterwave on digital products — for the earnings/withdrawal balance. */
    public static function totalEarnings(int $tenantId): float
    {
        $stmt = self::db()->prepare("SELECT COALESCE(SUM(amount),0) FROM digital_product_orders WHERE tenant_id = ? AND status = 'successful'");
        $stmt->execute([$tenantId]);
        return (float) $stmt->fetchColumn();
    }
}
