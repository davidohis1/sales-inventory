<?php
namespace App\Models;

class CustomerPayment extends BaseModel
{
    protected static function table(): string { return 'customer_payments'; }

    public static function record(int $tenantId, int $customerId, float $amount, string $method, ?int $saleId, ?int $userId, ?string $note = null): int
    {
        $stmt = self::db()->prepare('INSERT INTO customer_payments (tenant_id, customer_id, sale_id, amount, method, note, user_id)
                                      VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$tenantId, $customerId, $saleId, $amount, $method, $note, $userId]);
        return (int) self::db()->lastInsertId();
    }
}
