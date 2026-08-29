<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\CustomerPayment;

class CustomerController
{
    public function index(Request $request): void
    {
        Response::success(Customer::search(Auth::tenantId(), (string) $request->input('q', '')));
    }

    public function show(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        $customer = Customer::find($tenantId, $id);
        if (!$customer) { Response::error('Customer not found', 404); return; }
        $customer['purchase_history'] = Customer::purchaseHistory($tenantId, $id);
        $customer['payment_history'] = Customer::paymentHistory($tenantId, $id);
        Response::success($customer);
    }

    public function store(Request $request): void
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') { Response::error('Name is required', 422); return; }
        $id = Customer::create([
            'tenant_id' => Auth::tenantId(), 'name' => $name,
            'phone' => $request->input('phone'), 'email' => $request->input('email'),
            'address' => $request->input('address'), 'credit_limit' => (float) $request->input('credit_limit', 0),
        ]);
        Response::success(Customer::find(Auth::tenantId(), $id), 'Customer created', 201);
    }

    public function update(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        if (!Customer::find($tenantId, $id)) { Response::error('Customer not found', 404); return; }
        $data = [];
        foreach (['name', 'phone', 'email', 'address', 'credit_limit'] as $f) {
            if ($request->input($f) !== null) $data[$f] = $request->input($f);
        }
        Customer::update($tenantId, $id, $data);
        Response::success(Customer::find($tenantId, $id), 'Customer updated');
    }

    public function recordPayment(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        $customer = Customer::find($tenantId, $id);
        if (!$customer) { Response::error('Customer not found', 404); return; }

        $amount = (float) $request->input('amount', 0);
        if ($amount <= 0) { Response::error('Amount must be greater than zero', 422); return; }

        CustomerPayment::record($tenantId, $id, $amount, (string) $request->input('method', 'cash'), $request->input('sale_id'), Auth::id(), $request->input('note'));
        Customer::adjustDebt($tenantId, $id, -$amount);
        ActivityLog::record($tenantId, Auth::id(), 'customer.payment', "Recorded payment of $amount for customer #$id");

        Response::success(Customer::find($tenantId, $id), 'Payment recorded');
    }
}
