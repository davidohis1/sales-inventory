<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\DigitalProduct;
use App\Models\DigitalProductOrder;
use App\Models\Tenant;
use App\Models\Withdrawal;

class DigitalProductController
{
    public function dashboard(Request $request): void
    {
        $tenantId = (int) Auth::tenantId();
        $from = $request->input('from') ?: null;
        $to = $request->input('to') ?: null;

        $summary = DigitalProductOrder::summaryForTenant($tenantId, $from, $to);
        $products = DigitalProduct::listForTenant($tenantId);

        Response::success([
            'revenue' => $summary['revenue'],
            'sales' => $summary['sales'],
            'product_count' => count($products),
            'recent_orders' => DigitalProductOrder::recentForTenant($tenantId, 20),
            'products' => $products,
        ]);
    }

    public function index(Request $request): void
    {
        Response::success(DigitalProduct::listForTenant((int) Auth::tenantId()));
    }

    public function show(Request $request): void
    {
        $product = DigitalProduct::findForTenant((int) Auth::tenantId(), (int) $request->param('id'));
        if (!$product) { Response::error('Product not found', 404); return; }
        Response::success($product);
    }

    public function store(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = (int) Auth::tenantId();

        $name = trim((string) $request->input('name', ''));
        $price = (float) $request->input('price', 0);
        if ($name === '') { Response::error('Product name is required', 422); return; }
        if ($price <= 0) { Response::error('Enter a valid price', 422); return; }

        $slug = DigitalProduct::uniqueSlugFrom($name);
        $id = DigitalProduct::create($tenantId, [
            'slug' => $slug,
            'name' => $name,
            'price' => $price,
            'compare_price' => $request->input('compare_price') ? (float) $request->input('compare_price') : null,
            'category' => trim((string) $request->input('category', '')) ?: null,
            'description' => (string) $request->input('description', ''),
            'video_url' => trim((string) $request->input('video_url', '')) ?: null,
            'images' => [],
            'is_published' => 1,
        ]);

        ActivityLog::record($tenantId, Auth::id(), 'digital_product.create', "Added digital product: $name");
        Response::success(DigitalProduct::findForTenant($tenantId, $id), 'Product created', 201);
    }

    public function update(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = (int) Auth::tenantId();
        $id = (int) $request->param('id');
        if (!DigitalProduct::findForTenant($tenantId, $id)) { Response::error('Product not found', 404); return; }

        $data = [];
        foreach (['name', 'category', 'description', 'video_url'] as $f) {
            if ($request->input($f) !== null) $data[$f] = trim((string) $request->input($f));
        }
        if ($request->input('price') !== null) $data['price'] = (float) $request->input('price');
        if ($request->input('compare_price') !== null) $data['compare_price'] = $request->input('compare_price') === '' ? null : (float) $request->input('compare_price');
        if ($request->input('is_published') !== null) $data['is_published'] = $request->input('is_published') ? 1 : 0;

        DigitalProduct::update($tenantId, $id, $data);
        Response::success(DigitalProduct::findForTenant($tenantId, $id), 'Product updated');
    }

    public function destroy(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = (int) Auth::tenantId();
        $id = (int) $request->param('id');
        if (!DigitalProduct::findForTenant($tenantId, $id)) { Response::error('Product not found', 404); return; }
        \App\Core\Database::connect()->prepare('DELETE FROM digital_products WHERE id = ? AND tenant_id = ?')->execute([$id, $tenantId]);
        Response::success(null, 'Product deleted');
    }

    private static function uploadDir(int $tenantId, int $productId, string $sub): string
    {
        $root = rtrim((string) Env::get('UPLOAD_DIR', __DIR__ . '/../../../public/uploads'), '/');
        $dir = "$root/tenant_$tenantId/digital-products/$productId/$sub";
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        return $dir;
    }

    public function uploadImage(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = (int) Auth::tenantId();
        $id = (int) $request->param('id');
        $product = DigitalProduct::findForTenant($tenantId, $id);
        if (!$product) { Response::error('Product not found', 404); return; }

        $file = $request->file('image');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) { Response::error('No valid image uploaded', 422); return; }
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) { Response::error('Only JPG, PNG, or WEBP images are allowed', 422); return; }
        if (count($product['images']) >= 8) { Response::error('Maximum 8 images per product', 422); return; }

        $dir = self::uploadDir($tenantId, $id, 'images');
        $filename = uniqid('img_') . '.' . $allowed[$mime];
        move_uploaded_file($file['tmp_name'], "$dir/$filename");
        $relative = "/uploads/tenant_$tenantId/digital-products/$id/images/$filename";

        $images = $product['images'];
        $images[] = $relative;
        DigitalProduct::update($tenantId, $id, ['images' => $images]);

        Response::success(DigitalProduct::findForTenant($tenantId, $id), 'Image added');
    }

    public function removeImage(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = (int) Auth::tenantId();
        $id = (int) $request->param('id');
        $product = DigitalProduct::findForTenant($tenantId, $id);
        if (!$product) { Response::error('Product not found', 404); return; }

        $path = (string) $request->input('path', '');
        $images = array_values(array_filter($product['images'], fn ($p) => $p !== $path));
        DigitalProduct::update($tenantId, $id, ['images' => $images]);

        $root = rtrim((string) Env::get('UPLOAD_DIR', __DIR__ . '/../../../public/uploads'), '/');
        $absolute = str_replace('/uploads', $root, $path);
        if (is_file($absolute)) @unlink($absolute);

        Response::success(DigitalProduct::findForTenant($tenantId, $id), 'Image removed');
    }

    /** The downloadable deliverable itself (what a buyer receives). */
    public function uploadFile(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = (int) Auth::tenantId();
        $id = (int) $request->param('id');
        if (!DigitalProduct::findForTenant($tenantId, $id)) { Response::error('Product not found', 404); return; }

        $file = $request->file('file');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) { Response::error('No valid file uploaded', 422); return; }
        if ($file['size'] > 200 * 1024 * 1024) { Response::error('File too large (max 200MB)', 422); return; }

        $dir = self::uploadDir($tenantId, $id, 'file');
        $originalName = $file['name'];
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = uniqid('file_') . ($ext ? ".$ext" : '');
        move_uploaded_file($file['tmp_name'], "$dir/$filename");
        $relative = "/uploads/tenant_$tenantId/digital-products/$id/file/$filename";

        DigitalProduct::update($tenantId, $id, ['file_path' => $relative, 'file_name' => $originalName]);
        Response::success(DigitalProduct::findForTenant($tenantId, $id), 'File uploaded');
    }

    /** Earnings summary for digital products (separate balance from store earnings). */
    public function earnings(Request $request): void
    {
        $tenantId = (int) Auth::tenantId();
        $totalEarned = DigitalProductOrder::totalEarnings($tenantId);
        $claimed = Withdrawal::claimedTotal($tenantId, 'digital_product');

        Response::success([
            'total_earned' => $totalEarned,
            'available_balance' => round($totalEarned - $claimed, 2),
            'fee_percent' => Withdrawal::FEE_PERCENT['digital_product'],
            'recent_payments' => DigitalProductOrder::recentForTenant($tenantId),
            'withdrawals' => Withdrawal::historyForTenant($tenantId, 'digital_product'),
        ]);
    }

    public function requestWithdrawal(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = (int) Auth::tenantId();
        $amount = (float) $request->input('amount', 0);
        $bank = [
            'bank_name' => trim((string) $request->input('bank_name', '')),
            'account_name' => trim((string) $request->input('account_name', '')),
            'account_number' => trim((string) $request->input('account_number', '')),
        ];
        if ($amount <= 0) { Response::error('Enter a valid amount', 422); return; }
        if ($bank['bank_name'] === '' || $bank['account_name'] === '' || $bank['account_number'] === '') {
            Response::error('Bank name, account name and account number are all required', 422);
            return;
        }

        $available = DigitalProductOrder::totalEarnings($tenantId) - Withdrawal::claimedTotal($tenantId, 'digital_product');
        if ($amount > $available) { Response::error('That amount is more than your available balance (' . number_format($available, 2) . ')', 422); return; }

        $id = Withdrawal::create($tenantId, 'digital_product', $amount, $bank);
        $tenant = Tenant::findById($tenantId);
        $user = Auth::user();
        EarningsController::notify($tenant, $user, 'digital_product', $id, $amount, $bank);
        ActivityLog::record($tenantId, Auth::id(), 'earnings.withdraw', "Requested withdrawal of {$amount} from digital product earnings");

        Response::success(['id' => $id], 'Withdrawal requested — it will be processed within 3 hours. A 5% fee applies.');
    }
}
