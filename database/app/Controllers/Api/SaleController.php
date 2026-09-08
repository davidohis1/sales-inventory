<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Sale;

class SaleController
{
    public function index(Request $request): void
    {
        $filters = [
            'from' => $request->input('from'),
            'to'   => $request->input('to'),
            'q'    => $request->input('q', ''),
        ];
        Response::success(Sale::listForTenant(Auth::tenantId(), $filters));
    }

    public function show(Request $request): void
    {
        $sale = Sale::withItems(Auth::tenantId(), (int) $request->param('id'));
        if (!$sale) { Response::error('Sale not found', 404); return; }
        Response::success($sale);
    }

    public function store(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $items = $request->input('items', []);
        if (empty($items) || !is_array($items)) { Response::error('At least one item is required', 422); return; }

        // Quick Sale support: customer is typed by name (not chosen from a dropdown).
        // If a customer with that exact name exists we reuse it; otherwise we create
        // one on the fly, so the sale and the new customer record are saved together.
        $customerId = $request->input('customer_id') ?: null;
        $customerName = trim((string) $request->input('customer_name', ''));
        if (!$customerId && $customerName !== '') {
            $customer = \App\Models\Customer::findOrCreateByName($tenantId, $customerName, $request->input('customer_phone'));
            $customerId = $customer['id'];
        }

        try {
            $result = Sale::createFullSale([
                'tenant_id'      => $tenantId,
                'branch_id'      => Auth::user()['branch_id'] ?? $request->input('branch_id'),
                'customer_id'    => $customerId,
                'user_id'        => Auth::id(),
                'items'          => $items,
                'discount'       => (float) $request->input('discount', 0),
                'amount_paid'    => $request->input('amount_paid'),
                'payment_method' => $request->input('payment_method', 'cash'),
                'payments'       => $request->input('payments'),
                'sale_type'      => 'in_store',
            ]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 422);
            return;
        }

        ActivityLog::record($tenantId, Auth::id(), 'sale.create', 'Created sale ' . $result['receipt_no']);
        \App\Core\Notifications::saleCompleted($tenantId, $result, Auth::user()['full_name'] ?? null);
        Response::success($result, 'Sale completed', 201);
    }

    public function refund(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $saleId = (int) $request->param('id');
        $lines = $request->input('lines', []);
        if (empty($lines)) { Response::error('No return lines provided', 422); return; }

        try {
            $refundTotal = Sale::refund($tenantId, $saleId, $lines, Auth::id(), $request->input('reason'));
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 422);
            return;
        }

        ActivityLog::record($tenantId, Auth::id(), 'sale.refund', "Refunded sale #$saleId (₦$refundTotal)");
        Response::success(['refund_total' => $refundTotal], 'Refund processed');
    }

    public function receipt(Request $request): void
    {
        $sale = Sale::withItems(Auth::tenantId(), (int) $request->param('id'));
        if (!$sale) { Response::error('Sale not found', 404); return; }
        Response::success($sale);
    }

    /** PUBLIC (no auth) — used for the shareable receipt link. Read-only, tenant-scoped by slug. */
    public function publicReceipt(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = \App\Models\Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Not found', 404); return; }
        $sale = Sale::withItems((int) $tenant['id'], (int) $request->param('id'));
        if (!$sale) { Response::error('Receipt not found', 404); return; }
        Response::success([
            'receipt_no' => $sale['receipt_no'], 'created_at' => $sale['created_at'],
            'subtotal' => $sale['subtotal'], 'discount' => $sale['discount'], 'total' => $sale['total'],
            'amount_paid' => $sale['amount_paid'], 'balance_due' => $sale['balance_due'],
            'items' => $sale['items'],
            'business_name' => $tenant['business_name'], 'currency' => $tenant['currency'],
        ]);
    }
}
