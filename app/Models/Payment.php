<?php
namespace App\Models;

class Payment extends BaseModel
{
    protected static function table(): string { return 'payments'; }

    public static function create(int $tenantId, int $planId, float $amount, string $currency, string $txRef): int
    {
        $stmt = self::db()->prepare('INSERT INTO payments (tenant_id, plan_id, amount, currency, tx_ref, status) VALUES (?,?,?,?,?,"pending")');
        $stmt->execute([$tenantId, $planId, $amount, $currency, $txRef]);
        return (int) self::db()->lastInsertId();
    }

    public static function findByTxRef(string $txRef): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM payments WHERE tx_ref = ? LIMIT 1');
        $stmt->execute([$txRef]);
        return $stmt->fetch() ?: null;
    }

    public static function markResult(string $txRef, string $status, ?string $flwTransactionId, ?string $rawResponse): void
    {
        $stmt = self::db()->prepare('UPDATE payments SET status = ?, flw_transaction_id = ?, raw_response = ? WHERE tx_ref = ?');
        $stmt->execute([$status, $flwTransactionId, $rawResponse, $txRef]);
    }

    public static function historyForTenant(int $tenantId): array
    {
        $stmt = self::db()->prepare('SELECT p.*, pl.name AS plan_name FROM payments p JOIN plans pl ON pl.id = p.plan_id
                                      WHERE p.tenant_id = ? ORDER BY p.created_at DESC LIMIT 50');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }
}
