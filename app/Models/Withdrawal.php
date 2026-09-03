<?php
namespace App\Models;

class Withdrawal extends BaseModel
{
    protected static function table(): string { return 'withdrawals'; }

    /** Fee percent charged per source. Store earnings: no extra fee (it's the tenant's own money from their own store). Digital products: 5%, per product spec. */
    public const FEE_PERCENT = ['store' => 0.0, 'digital_product' => 5.0];

    public static function create(int $tenantId, string $source, float $amount, ?array $bank = null): int
    {
        $feePercent = self::FEE_PERCENT[$source] ?? 0.0;
        $fee = round($amount * $feePercent / 100, 2);
        $net = round($amount - $fee, 2);

        $stmt = self::db()->prepare(
            "INSERT INTO withdrawals (tenant_id, source, amount, fee_percent, fee_amount, net_amount, bank_name, account_name, account_number, status)
             VALUES (?,?,?,?,?,?,?,?,?,'requested')"
        );
        $stmt->execute([
            $tenantId, $source, $amount, $feePercent, $fee, $net,
            $bank['bank_name'] ?? null, $bank['account_name'] ?? null, $bank['account_number'] ?? null,
        ]);
        return (int) self::db()->lastInsertId();
    }

    /** Sum of amounts already claimed (requested, processing, or paid) for a source — locks that money out of the available balance. */
    public static function claimedTotal(int $tenantId, string $source): float
    {
        $stmt = self::db()->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE tenant_id = ? AND source = ? AND status IN ('requested','processing','paid')"
        );
        $stmt->execute([$tenantId, $source]);
        return (float) $stmt->fetchColumn();
    }

    public static function historyForTenant(int $tenantId, string $source): array
    {
        $stmt = self::db()->prepare('SELECT * FROM withdrawals WHERE tenant_id = ? AND source = ? ORDER BY created_at DESC LIMIT 50');
        $stmt->execute([$tenantId, $source]);
        return $stmt->fetchAll();
    }
}
