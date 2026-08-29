<?php
namespace App\Models;

use App\Core\Database;

class OnlineOrder extends BaseModel
{
    protected static function table(): string { return 'online_orders'; }

    public static function create(int $tenantId, array $customer, array $items): array
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $subtotal = 0;
            $lineData = [];
            foreach ($items as $item) {
                $product = Product::find($tenantId, (int) $item['product_id']);
                if (!$product || !$product['is_on_store'] || (int) $product['quantity'] < (int) $item['quantity']) {
                    throw new \RuntimeException('Product unavailable: ' . ($product['name'] ?? $item['product_id']));
                }
                $qty = (int) $item['quantity'];
                $lineTotal = $qty * (float) $product['selling_price'];
                $subtotal += $lineTotal;
                $lineData[] = ['product_id' => $product['id'], 'quantity' => $qty, 'unit_price' => $product['selling_price'], 'line_total' => $lineTotal];
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM online_orders WHERE tenant_id = ?');
            $stmt->execute([$tenantId]);
            $orderNo = 'ORD-' . str_pad((string) ((int) $stmt->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare('INSERT INTO online_orders (tenant_id, order_no, customer_name, customer_phone, customer_email, delivery_address, subtotal, total, status)
                                    VALUES (?,?,?,?,?,?,?,?,\'pending\')');
            $stmt->execute([
                $tenantId, $orderNo, $customer['name'], $customer['phone'] ?? null, $customer['email'] ?? null,
                $customer['address'] ?? null, $subtotal, $subtotal,
            ]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO online_order_items (tenant_id, order_id, product_id, quantity, unit_price, line_total) VALUES (?,?,?,?,?,?)');
            foreach ($lineData as $line) {
                $itemStmt->execute([$tenantId, $orderId, $line['product_id'], $line['quantity'], $line['unit_price'], $line['line_total']]);
                // reserve stock immediately so it doesn't oversell
                Product::adjustStock($tenantId, $line['product_id'], -$line['quantity']);
                StockLog::log($tenantId, $line['product_id'], -$line['quantity'], 'sale', null, null, "Online order $orderNo");
            }

            $pdo->commit();
            return ['id' => $orderId, 'order_no' => $orderNo, 'total' => $subtotal];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function listForTenant(int $tenantId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM online_orders WHERE tenant_id = ? ORDER BY created_at DESC');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function withItems(int $tenantId, int $orderId): ?array
    {
        $order = self::find($tenantId, $orderId);
        if (!$order) return null;
        $stmt = self::db()->prepare('SELECT oi.*, p.name AS product_name FROM online_order_items oi
                                      JOIN products p ON p.id = oi.product_id WHERE oi.tenant_id = ? AND oi.order_id = ?');
        $stmt->execute([$tenantId, $orderId]);
        $order['items'] = $stmt->fetchAll();
        return $order;
    }

    public static function updateStatus(int $tenantId, int $orderId, string $status): bool
    {
        $stmt = self::db()->prepare('UPDATE online_orders SET status = ? WHERE id = ? AND tenant_id = ?');
        return $stmt->execute([$status, $orderId, $tenantId]);
    }

    public static function markCustomerPaid(int $tenantId, int $orderId): bool
    {
        $stmt = self::db()->prepare('UPDATE online_orders SET customer_marked_paid = 1, customer_marked_paid_at = NOW() WHERE id = ? AND tenant_id = ?');
        return $stmt->execute([$orderId, $tenantId]);
    }

    /** Convert an accepted online order into a proper POS sale record (for unified reporting). */
    public static function convertToSale(int $tenantId, int $orderId, ?int $userId): array
    {
        $order = self::withItems($tenantId, $orderId);
        if (!$order) throw new \RuntimeException('Order not found');

        $items = array_map(fn ($i) => ['product_id' => $i['product_id'], 'quantity' => $i['quantity'], 'discount' => 0], $order['items']);

        // Stock was already decremented at order time, so create the sale WITHOUT decrementing again.
        // We do this with a light-weight direct insert instead of Sale::createFullSale (which decrements stock).
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $receiptNo = Sale::nextReceiptNo($tenantId);
            $stmt = $pdo->prepare("INSERT INTO sales (tenant_id, customer_id, receipt_no, subtotal, discount, total, amount_paid, balance_due, payment_method, sale_type, status)
                                    VALUES (?,NULL,?,?,0,?,?,0,'transfer','online','completed')");
            $stmt->execute([$tenantId, $receiptNo, $order['subtotal'], $order['total'], $order['total']]);
            $saleId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO sale_items (tenant_id, sale_id, product_id, quantity, unit_cost, unit_price, discount, line_total) VALUES (?,?,?,?,?,?,0,?)');
            foreach ($order['items'] as $item) {
                $product = Product::find($tenantId, (int) $item['product_id']);
                $itemStmt->execute([$tenantId, $saleId, $item['product_id'], $item['quantity'], $product['buying_price'] ?? 0, $item['unit_price'], $item['line_total']]);
            }

            $pdo->prepare('UPDATE online_orders SET status = \'accepted\', sale_id = ? WHERE id = ? AND tenant_id = ?')->execute([$saleId, $orderId, $tenantId]);
            $pdo->commit();
            return ['sale_id' => $saleId, 'receipt_no' => $receiptNo];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
