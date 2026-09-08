<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLog;

class BranchController
{
    public function index(Request $request): void
    {
        Response::success(Branch::allForTenant(Auth::tenantId()));
    }

    public function store(Request $request): void
    {
        if (!Auth::hasRole(['owner'])) { Response::error('Only the owner can add branches', 403); return; }
        $name = trim((string) $request->input('name', ''));
        if ($name === '') { Response::error('Branch name is required', 422); return; }
        $id = Branch::create(Auth::tenantId(), $name, $request->input('address'));
        ActivityLog::record(Auth::tenantId(), Auth::id(), 'branch.create', "Added branch $name");
        Response::success(Branch::find(Auth::tenantId(), $id), 'Branch created', 201);
    }

    /** Transfer stock of a product from one branch to another. */
    public function transferStock(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $productId = (int) $request->input('product_id');
        $fromBranch = (int) $request->input('from_branch_id');
        $toBranch = (int) $request->input('to_branch_id');
        $qty = (int) $request->input('quantity', 0);

        if ($qty <= 0 || $fromBranch === $toBranch) { Response::error('Invalid transfer request', 422); return; }
        $product = Product::find($tenantId, $productId);
        if (!$product) { Response::error('Product not found', 404); return; }
        if ((int) $product['quantity'] < $qty) { Response::error('Insufficient stock to transfer', 422); return; }

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO stock_transfers (tenant_id, product_id, from_branch_id, to_branch_id, quantity, user_id, note) VALUES (?,?,?,?,?,?,?)')
                ->execute([$tenantId, $productId, $fromBranch, $toBranch, $qty, Auth::id(), $request->input('note')]);
            StockLog::log($tenantId, $productId, -$qty, 'transfer_out', Auth::id(), $fromBranch, "Transfer to branch #$toBranch");
            StockLog::log($tenantId, $productId, $qty, 'transfer_in', Auth::id(), $toBranch, "Transfer from branch #$fromBranch");
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Response::error('Transfer failed: ' . $e->getMessage(), 500);
            return;
        }

        ActivityLog::record($tenantId, Auth::id(), 'stock.transfer', "Transferred $qty of product #$productId from branch #$fromBranch to #$toBranch");
        Response::success(Product::find($tenantId, $productId), 'Stock transferred');
    }
}
