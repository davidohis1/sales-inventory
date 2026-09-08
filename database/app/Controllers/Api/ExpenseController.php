<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Expense;

class ExpenseController
{
    public function index(Request $request): void
    {
        $filters = ['from' => $request->input('from'), 'to' => $request->input('to')];
        Response::success(Expense::listForTenant(Auth::tenantId(), $filters));
    }

    public function store(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $title = trim((string) $request->input('title', ''));
        $amount = (float) $request->input('amount', 0);
        if ($title === '' || $amount <= 0) { Response::error('Title and a positive amount are required', 422); return; }

        $id = Expense::create([
            'tenant_id' => Auth::tenantId(), 'branch_id' => Auth::user()['branch_id'] ?? null,
            'category_id' => $request->input('category_id') ?: null, 'user_id' => Auth::id(),
            'title' => $title, 'amount' => $amount, 'note' => $request->input('note'),
            'expense_date' => $request->input('expense_date', date('Y-m-d')),
        ]);
        ActivityLog::record(Auth::tenantId(), Auth::id(), 'expense.create', "Logged expense: $title");
        Response::success(Expense::find(Auth::tenantId(), $id), 'Expense recorded', 201);
    }

    public function categories(Request $request): void
    {
        Response::success(Expense::categories(Auth::tenantId()));
    }

    public function createCategory(Request $request): void
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') { Response::error('Name required', 422); return; }
        $id = Expense::createCategory(Auth::tenantId(), $name);
        Response::success(['id' => $id, 'name' => $name], 'Category created', 201);
    }
}
