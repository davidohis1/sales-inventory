<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Notifications;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\OnlineOrder;
use App\Models\StoreSettings;
use App\Models\Tenant;

class OrderController
{
    /** PUBLIC: place an order from the storefront cart/checkout. */
    public function place(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Store not found', 404); return; }

        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $items = $request->input('items', []);
        if ($name === '' || empty($items)) { Response::error('Name and at least one cart item are required', 422); return; }

        try {
            $result = OnlineOrder::create((int) $tenant['id'], [
                'name' => $name, 'phone' => $request->input('phone'),
                'email' => $email ?: null, 'address' => $request->input('address'),
            ], $items);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 422);
            return;
        }

        $tenantId = (int) $tenant['id'];
        $fullOrder = OnlineOrder::withItems($tenantId, (int) $result['id']);

        // Anyone who orders from the online store is automatically added to
        // the customer list, matched by email so repeat shoppers don't get
        // duplicate customer records.
        if ($email !== '') {
            try {
                Customer::findOrCreateByEmail($tenantId, $name, $email, $request->input('phone'));
            } catch (\Throwable $e) { /* best-effort — never block the order over this */ }
        }

        Notifications::orderPlacedAdmin($tenantId, $fullOrder);
        if ($email !== '') {
            Notifications::orderPlacedCustomer($tenantId, $fullOrder, $email);
        }

        $settings = StoreSettings::get($tenantId);
        $result['order_channel'] = $settings['content']['order_channel'] ?? 'email';
        $result['whatsapp_number'] = $settings['content']['whatsapp_number'] ?? null;
        $result['bank_name'] = $settings['content']['bank_name'] ?? null;
        $result['bank_account_name'] = $settings['content']['bank_account_name'] ?? null;
        $result['bank_account_number'] = $settings['content']['bank_account_number'] ?? null;

        Response::success($result, 'Order placed successfully! The store will contact you to confirm delivery.', 201);
    }

    /** PUBLIC: customer clicks "I Have Paid" on a bank-transfer checkout. Doesn't auto-confirm — just alerts the admin to verify. */
    public function markPaid(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Store not found', 404); return; }
        $tenantId = (int) $tenant['id'];
        $id = (int) $request->param('id');

        $order = OnlineOrder::withItems($tenantId, $id);
        if (!$order) { Response::error('Order not found', 404); return; }

        OnlineOrder::markCustomerPaid($tenantId, $id);
        Notifications::customerClaimedPaid($tenantId, $order);

        Response::success(['text' => "Thanks! We've been notified and will confirm your payment shortly."], 'Payment claim recorded');
    }

    /** PUBLIC: starts a Flutterwave checkout for an online order (in addition to the existing bank-transfer "I Have Paid" flow). */
    public function payInit(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Store not found', 404); return; }
        $tenantId = (int) $tenant['id'];
        $id = (int) $request->param('id');

        $order = OnlineOrder::withItems($tenantId, $id);
        if (!$order) { Response::error('Order not found', 404); return; }
        if ((float) $order['amount_paid'] >= (float) $order['total']) { Response::error('This order is already paid for', 422); return; }

        $txRef = 'ORD-' . $tenantId . '-' . $id . '-' . strtoupper(bin2hex(random_bytes(5)));
        OnlineOrder::attachTxRef($tenantId, $id, $txRef);

        if (!\App\Core\Flutterwave::isConfigured()) {
            Response::success(['simulated' => true, 'tx_ref' => $txRef], 'Flutterwave is not configured — using simulated checkout for development');
            return;
        }

        $base = rtrim((string) \App\Core\Env::get('APP_URL', ''), '/');
        $result = \App\Core\Flutterwave::initializePayment([
            'tx_ref' => $txRef,
            'amount' => (string) $order['total'],
            'currency' => $tenant['currency'] ?: 'NGN',
            'redirect_url' => ($base ?: '') . "/payments/callback",
            'customer' => [
                'email' => $order['customer_email'] ?: 'customer@example.com',
                'name' => $order['customer_name'],
                'phonenumber' => $order['customer_phone'] ?? '',
            ],
            'customizations' => ['title' => $tenant['business_name'] . ' — Order ' . $order['order_no'], 'description' => 'Payment for order ' . $order['order_no']],
        ]);

        if (($result['status'] ?? '') !== 'success' || empty($result['data']['link'])) {
            Response::error($result['message'] ?? 'Could not start payment with Flutterwave', 502);
            return;
        }
        Response::success(['link' => $result['data']['link'], 'tx_ref' => $txRef]);
    }

    /** PUBLIC: confirms a store-order Flutterwave payment (used by the dev "simulate" button; the real redirect flow goes through /payments/callback instead). */
    public function verifyPayment(Request $request): void
    {
        $txRef = (string) $request->input('tx_ref', '');
        $transactionId = (string) $request->input('transaction_id', '');
        $result = self::verifyOrderPayment($txRef, $transactionId);
        if (!$result['ok']) { Response::error($result['message'], 422); return; }
        Response::success($result['order'], $result['message']);
    }

    /** Called from the shared /payments/callback page after Flutterwave redirects back for an ORD- tx_ref. Not behind tenant auth (public checkout). */
    public static function verifyOrderPayment(string $txRef, string $transactionId): array
    {
        $order = OnlineOrder::findByTxRef($txRef);
        if (!$order) return ['ok' => false, 'message' => 'Order not found for this payment reference'];
        if ((float) $order['amount_paid'] >= (float) $order['total']) return ['ok' => true, 'message' => 'Already confirmed', 'order' => $order];

        if (\App\Core\Flutterwave::isConfigured() && $transactionId !== '') {
            $verify = \App\Core\Flutterwave::verifyTransaction($transactionId);
            $ok = ($verify['status'] ?? '') === 'success'
                && ($verify['data']['status'] ?? '') === 'successful'
                && (float) ($verify['data']['amount'] ?? 0) >= (float) $order['total']
                && ($verify['data']['tx_ref'] ?? '') === $txRef;
            if (!$ok) return ['ok' => false, 'message' => 'Payment could not be verified'];
        }
        // (Simulated dev-mode confirmation falls through here when Flutterwave isn't configured.)

        OnlineOrder::markPaidViaFlutterwave((int) $order['tenant_id'], (int) $order['id'], $txRef, $transactionId ?: 'SIMULATED', (float) $order['total']);
        $tenant = Tenant::findById((int) $order['tenant_id']);
        return ['ok' => true, 'message' => 'Payment confirmed', 'order' => OnlineOrder::withItems((int) $order['tenant_id'], (int) $order['id']), 'tenant_slug' => $tenant['slug'] ?? null];
    }

    /** ADMIN: list pending/online orders. */
    public function index(Request $request): void
    {
        Response::success(OnlineOrder::listForTenant(Auth::tenantId()));
    }

    public function show(Request $request): void
    {
        $order = OnlineOrder::withItems(Auth::tenantId(), (int) $request->param('id'));
        if (!$order) { Response::error('Order not found', 404); return; }
        Response::success($order);
    }

    /** Accepting a fresh order converts it to a sale and moves it to "accepted". */
    public function accept(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        $orderBefore = OnlineOrder::withItems($tenantId, $id);
        try {
            $result = OnlineOrder::convertToSale($tenantId, $id, Auth::id());
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 422);
            return;
        }
        ActivityLog::record($tenantId, Auth::id(), 'order.accept', "Accepted online order #$id -> sale " . $result['receipt_no']);
        if ($orderBefore && !empty($orderBefore['customer_email'])) {
            Notifications::orderStatusChanged($tenantId, $orderBefore, 'accepted');
        }
        Response::success($result, 'Order accepted');
    }

    /**
     * Generic status change — walks an order through the fulfillment workflow:
     * ordered -> accepted -> on_delivery -> delivered  (or cancelled at any point).
     * Every status change here emails the customer, if they gave an email.
     */
    public function updateStatus(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        $status = (string) $request->input('status', '');
        if (!in_array($status, ['ordered', 'accepted', 'on_delivery', 'delivered', 'cancelled'], true)) { Response::error('Invalid status', 422); return; }

        $order = OnlineOrder::withItems($tenantId, $id);
        if (!$order) { Response::error('Order not found', 404); return; }

        OnlineOrder::updateStatus($tenantId, $id, $status);
        ActivityLog::record($tenantId, Auth::id(), 'order.status', "Order #$id status changed to $status");

        if (!empty($order['customer_email'])) {
            Notifications::orderStatusChanged($tenantId, $order, $status);
        }

        Response::success(OnlineOrder::find($tenantId, $id), 'Order status updated');
    }
}