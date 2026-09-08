<?php
namespace App\Models;

use App\Core\Database;

class Sale extends BaseModel
{
    protected static function table(): string { return 'sales'; }

    public static function nextReceiptNo(int $tenantId): string
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM sales WHERE tenant_id = ?');
        $stmt->execute([$tenantId]);
        $count = (int) $stmt->fetchColumn() + 1;
        return 'RCT-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a full sale transaction: header + line items + stock decrement + stock log + customer debt.
     * $items = [['product_id'=>,'quantity'=>,'discount'=>], ...]
     * $payments = [['method'=>'cash','amount'=>1000], ...] (for split payments)
     */
    public static function createFullSale(array $data): array
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $tenantId = $data['tenant_id'];
            $items = $data['items'];

            $subtotal = 0;
            $lineData = [];
            foreach ($items as $item) {
                $product = Product::find($tenantId, (int) $item['product_id']);
                if (!$product) throw new \RuntimeException('Product not found: ' . $item['product_id']);
                if ((int) $product['quantity'] < (int) $item['quantity']) {
                    throw new \RuntimeException('Insufficient stock for ' . $product['name']);
                }
                $qty = (int) $item['quantity'];
                $discount = (float) ($item['discount'] ?? 0);
                $unitPrice = (float) $product['selling_price'];
                $lineTotal = ($unitPrice * $qty) - $discount;
                $subtotal += $unitPrice * $qty;
                $lineData[] = [
                    'product_id' => $product['id'],
                    'quantity' => $qty,
                    'unit_cost' => $product['buying_price'],
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'line_total' => $lineTotal,
                ];
            }

            $overallDiscount = (float) ($data['discount'] ?? 0);
            $total = array_sum(array_column($lineData, 'line_total')) - $overallDiscount;
            $total = max(0, $total);

            $amountPaid = (float) ($data['amount_paid'] ?? $total);
            $balanceDue = max(0, $total - $amountPaid);
            $paymentMethod = $balanceDue > 0 ? 'credit' : ($data['payment_method'] ?? 'cash');

            $receiptNo = self::nextReceiptNo($tenantId);

            $stmt = $pdo->prepare('INSERT INTO sales
                (tenant_id, branch_id, customer_id, user_id, receipt_no, subtotal, discount, total, amount_paid, balance_due, payment_method, sale_type, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,\'completed\')');
            $stmt->execute([
                $tenantId, $data['branch_id'] ?? null, $data['customer_id'] ?? null, $data['user_id'] ?? null,
                $receiptNo, $subtotal, $overallDiscount, $total, $amountPaid, $balanceDue,
                $data['payment_method'] ?? $paymentMethod, $data['sale_type'] ?? 'in_store',
            ]);
            $saleId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO sale_items (tenant_id, sale_id, product_id, quantity, unit_cost, unit_price, discount, line_total)
                                        VALUES (?,?,?,?,?,?,?,?)');
            foreach ($lineData as $line) {
                $itemStmt->execute([
                    $tenantId, $saleId, $line['product_id'], $line['quantity'],
                    $line['unit_cost'], $line['unit_price'], $line['discount'], $line['line_total'],
                ]);
                Product::adjustStock($tenantId, $line['product_id'], -$line['quantity']);
                StockLog::log($tenantId, $line['product_id'], -$line['quantity'], 'sale', $data['user_id'] ?? null, $data['branch_id'] ?? null, "Sale $receiptNo");
            }

            // split payments
            if (!empty($data['payments']) && is_array($data['payments'])) {
                $payStmt = $pdo->prepare('INSERT INTO sale_payments (tenant_id, sale_id, method, amount) VALUES (?,?,?,?)');
                foreach ($data['payments'] as $p) {
                    $payStmt->execute([$tenantId, $saleId, $p['method'], $p['amount']]);
                }
            } elseif ($amountPaid > 0) {
                $payStmt = $pdo->prepare('INSERT INTO sale_payments (tenant_id, sale_id, method, amount) VALUES (?,?,?,?)');
                $payStmt->execute([$tenantId, $saleId, $data['payment_method'] ?? 'cash', $amountPaid]);
            }

            if ($balanceDue > 0 && !empty($data['customer_id'])) {
                Customer::adjustDebt($tenantId, (int) $data['customer_id'], $balanceDue);
            }

            $pdo->commit();
            return ['id' => $saleId, 'receipt_no' => $receiptNo, 'total' => $total, 'balance_due' => $balanceDue];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function withItems(int $tenantId, int $saleId): ?array
    {
        $sale = self::find($tenantId, $saleId);
        if (!$sale) return null;
        $stmt = self::db()->prepare('SELECT si.*, p.name AS product_name FROM sale_items si
                                      JOIN products p ON p.id = si.product_id
                                      WHERE si.tenant_id = ? AND si.sale_id = ?');
        $stmt->execute([$tenantId, $saleId]);
        $sale['items'] = $stmt->fetchAll();
        return $sale;
    }

    public static function listForTenant(int $tenantId, array $filters = []): array
    {
        $sql = 'SELECT s.*, c.name AS customer_name, u.full_name AS staff_name FROM sales s
                LEFT JOIN customers c ON c.id = s.customer_id
                LEFT JOIN users u ON u.id = s.user_id
                WHERE s.tenant_id = ?';
        $params = [$tenantId];
        if (!empty($filters['from'])) { $sql .= ' AND s.created_at >= ?'; $params[] = $filters['from'] . ' 00:00:00'; }
        if (!empty($filters['to'])) { $sql .= ' AND s.created_at <= ?'; $params[] = $filters['to'] . ' 23:59:59'; }
        if (!empty($filters['q'])) { $sql .= ' AND (s.receipt_no LIKE ? OR c.name LIKE ?)'; $params[] = '%' . $filters['q'] . '%'; $params[] = '%' . $filters['q'] . '%'; }
        $sql .= ' ORDER BY s.created_at DESC LIMIT 200';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function refund(int $tenantId, int $saleId, array $lines, ?int $userId, ?string $reason): float
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $totalRefund = 0;
            foreach ($lines as $line) {
                $itemStmt = $pdo->prepare('SELECT * FROM sale_items WHERE id = ? AND tenant_id = ? AND sale_id = ?');
                $itemStmt->execute([$line['sale_item_id'], $tenantId, $saleId]);
                $item = $itemStmt->fetch();
                if (!$item) continue;
                $qty = min((int) $line['quantity'], (int) $item['quantity'] - (int) $item['returned_qty']);
                if ($qty <= 0) continue;
                $refundAmt = $qty * (float) $item['unit_price'];
                $totalRefund += $refundAmt;

                $pdo->prepare('UPDATE sale_items SET returned_qty = returned_qty + ? WHERE id = ?')->execute([$qty, $item['id']]);
                $pdo->prepare('INSERT INTO sale_returns (tenant_id, sale_id, sale_item_id, quantity, reason, refund_amount, user_id) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$tenantId, $saleId, $item['id'], $qty, $reason, $refundAmt, $userId]);

                Product::adjustStock($tenantId, (int) $item['product_id'], $qty);
                StockLog::log($tenantId, (int) $item['product_id'], $qty, 'return', $userId, null, "Refund on sale #$saleId");
            }
            if ($totalRefund > 0) {
                $pdo->prepare("UPDATE sales SET status = 'partial_refund' WHERE id = ? AND tenant_id = ?")->execute([$saleId, $tenantId]);
            }
            $pdo->commit();
            return $totalRefund;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
