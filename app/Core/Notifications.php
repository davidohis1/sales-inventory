<?php
namespace App\Core;

use App\Core\Database;
use App\Models\StoreSettings;

/**
 * Central place for all outbound business notifications (admin sale alerts,
 * customer order confirmations, payment notices). Every method is best-effort
 * — failures are swallowed so a broken mail setup never breaks a sale/order.
 */
class Notifications
{
    /** Resolves who gets admin-facing notifications: the configured notification_email, else the tenant owner's email. */
    public static function adminEmail(int $tenantId): ?string
    {
        $settings = StoreSettings::get($tenantId);
        $configured = trim((string) ($settings['content']['notification_email'] ?? ''));
        if ($configured !== '') return $configured;

        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT email FROM users WHERE tenant_id = ? AND role = 'owner' AND is_active = 1 ORDER BY id ASC LIMIT 1");
        $stmt->execute([$tenantId]);
        $email = $stmt->fetchColumn();
        return $email ?: null;
    }

    public static function businessName(int $tenantId): string
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT business_name FROM tenants WHERE id = ?');
        $stmt->execute([$tenantId]);
        return (string) ($stmt->fetchColumn() ?: 'Your Store');
    }

    /** Sent to the admin whenever an in-store/POS/Quick Sale sale is completed. */
    public static function saleCompleted(int $tenantId, array $sale, ?string $staffName): void
    {
        try {
            $to = self::adminEmail($tenantId);
            if (!$to) return;
            $biz = self::businessName($tenantId);
            $body = "<h2>New Sale — {$sale['receipt_no']}</h2>"
                . "<p><strong>Total:</strong> " . number_format((float) $sale['total'], 2) . "</p>"
                . ($sale['balance_due'] > 0 ? "<p><strong>Balance due:</strong> " . number_format((float) $sale['balance_due'], 2) . "</p>" : '')
                . ($staffName ? "<p><strong>Staff:</strong> " . htmlspecialchars($staffName) . "</p>" : '')
                . "<p>Logged automatically by {$biz}'s point of sale.</p>";
            Mailer::send($to, "New sale recorded — {$sale['receipt_no']}", $body);
        } catch (\Throwable $e) { /* never break the sale over email */ }
    }

    /** Sent to the admin when a new online order is placed. */
    public static function orderPlacedAdmin(int $tenantId, array $order): void
    {
        try {
            $to = self::adminEmail($tenantId);
            if (!$to) return;
            $itemsHtml = implode('', array_map(fn ($i) => "<li>{$i['quantity']} x " . htmlspecialchars($i['product_name']) . "</li>", $order['items'] ?? []));
            $body = "<h2>New Online Order — {$order['order_no']}</h2>"
                . "<p><strong>Customer:</strong> " . htmlspecialchars($order['customer_name']) . " (" . htmlspecialchars($order['customer_phone'] ?? '') . ")</p>"
                . "<p><strong>Total:</strong> " . number_format((float) $order['total'], 2) . "</p>"
                . "<ul>{$itemsHtml}</ul>"
                . "<p>Review and accept it from your admin portal's Online Orders page.</p>";
            Mailer::send($to, "New online order — {$order['order_no']}", $body);
        } catch (\Throwable $e) { /* best-effort */ }
    }

    /** Sent to the customer right after they submit their order, regardless of the store's chosen notification channel. */
    public static function orderPlacedCustomer(int $tenantId, array $order, string $customerEmail): void
    {
        try {
            if ($customerEmail === '') return;
            $biz = self::businessName($tenantId);
            $itemsHtml = implode('', array_map(fn ($i) => "<li>{$i['quantity']} x " . htmlspecialchars($i['product_name']) . " — " . number_format((float) $i['line_total'], 2) . "</li>", $order['items'] ?? []));
            $body = "<h2>Thanks for your order, " . htmlspecialchars($order['customer_name']) . "!</h2>"
                . "<p>We've received order <strong>{$order['order_no']}</strong> from <strong>{$biz}</strong>.</p>"
                . "<ul>{$itemsHtml}</ul>"
                . "<p><strong>Total:</strong> " . number_format((float) $order['total'], 2) . "</p>"
                . "<p>We'll be in touch shortly to confirm delivery.</p>";
            Mailer::send($customerEmail, "Order confirmation — {$order['order_no']}", $body);
        } catch (\Throwable $e) { /* best-effort */ }
    }

    /** Sent to the customer once the store marks their order as fulfilled/paid. */
    public static function orderPaidCustomer(int $tenantId, array $order, string $customerEmail): void
    {
        try {
            if ($customerEmail === '') return;
            $biz = self::businessName($tenantId);
            $body = "<h2>Payment confirmed — {$order['order_no']}</h2>"
                . "<p>Hi " . htmlspecialchars($order['customer_name']) . ", your payment for order <strong>{$order['order_no']}</strong> has been confirmed by <strong>{$biz}</strong>.</p>"
                . "<p>Your order is now being prepared. Thank you for shopping with us!</p>";
            Mailer::send($customerEmail, "Payment confirmed — {$order['order_no']}", $body);
        } catch (\Throwable $e) { /* best-effort */ }
    }

    /** Sent to the admin when a customer clicks "I Have Paid" on a bank-transfer order (manual confirmation still required). */
    public static function customerClaimedPaid(int $tenantId, array $order): void
    {
        try {
            $to = self::adminEmail($tenantId);
            if (!$to) return;
            $body = "<h2>Payment claim — {$order['order_no']}</h2>"
                . "<p><strong>" . htmlspecialchars($order['customer_name']) . "</strong> says they've paid by bank transfer for order <strong>{$order['order_no']}</strong> (total " . number_format((float) $order['total'], 2) . ").</p>"
                . "<p>Please verify the transfer in your bank account, then mark the order as fulfilled in your admin portal.</p>";
            Mailer::send($to, "Customer says they've paid — {$order['order_no']}", $body);
        } catch (\Throwable $e) { /* best-effort */ }
    }
}
