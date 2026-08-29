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

    /** Accepting a pending order converts it to a sale and moves it to "processing". */
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
            Notifications::orderStatusChanged($tenantId, $orderBefore, 'processing');
        }
        Response::success($result, 'Order accepted and is now processing');
    }

    /**
     * Generic status change (used for "Mark as Delivered" and "Cancel").
     * Every status change here emails the customer, if they gave an email.
     */
    public function updateStatus(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        $status = (string) $request->input('status', '');
        if (!in_array($status, ['pending', 'processing', 'delivered', 'cancelled'], true)) { Response::error('Invalid status', 422); return; }

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