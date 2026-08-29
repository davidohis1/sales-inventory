<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\StockLog;

class ProductController
{
    public function index(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $q = (string) $request->input('q', '');
        $products = Product::search($tenantId, $q, Auth::user()['branch_id'] ?? null, false, true, 100);
        Response::success($products);
    }

    public function show(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $product = Product::findWithImages($tenantId, (int) $request->param('id'));
        if (!$product) { Response::error('Product not found', 404); return; }
        Response::success($product);
    }

    public function store(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $name = trim((string) $request->input('name', ''));
        $sku = trim((string) $request->input('sku', ''));
        if ($name === '' || $sku === '') { Response::error('Name and SKU are required', 422); return; }

        $id = Product::create([
            'tenant_id'       => $tenantId,
            'category_id'     => $request->input('category_id') ?: null,
            'branch_id'       => $request->input('branch_id') ?: Auth::user()['branch_id'],
            'name'            => $name,
            'sku'             => $sku,
            'description'     => $request->input('description'),
            'buying_price'    => (float) $request->input('buying_price', 0),
            'selling_price'   => (float) $request->input('selling_price', 0),
            'quantity'        => (int) $request->input('quantity', 0),
            'min_stock_level' => (int) $request->input('min_stock_level', 5),
        ]);

        $qty = (int) $request->input('quantity', 0);
        if ($qty > 0) {
            StockLog::log($tenantId, $id, $qty, 'initial', Auth::id(), null, 'Product created');
        }
        ActivityLog::record($tenantId, Auth::id(), 'product.create', "Created product $name");

        Response::success(Product::find($tenantId, $id), 'Product created', 201);
    }

    public function update(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        if (!Product::find($tenantId, $id)) { Response::error('Product not found', 404); return; }

        $data = [];
        foreach (['category_id', 'branch_id', 'name', 'sku', 'description', 'buying_price', 'selling_price', 'min_stock_level', 'is_active'] as $f) {
            if ($request->input($f) !== null) $data[$f] = $request->input($f);
        }
        Product::update($tenantId, $id, $data);
        ActivityLog::record($tenantId, Auth::id(), 'product.edit', "Edited product #$id");
        Response::success(Product::find($tenantId, $id), 'Product updated');
    }

    public function destroy(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        Product::update($tenantId, $id, ['is_active' => 0]);
        ActivityLog::record($tenantId, Auth::id(), 'product.deactivate', "Deactivated product #$id");
        Response::success(null, 'Product deactivated');
    }

    public function adjustStock(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        $product = Product::find($tenantId, $id);
        if (!$product) { Response::error('Product not found', 404); return; }

        $delta = (int) $request->input('change_qty', 0);
        $reason = (string) $request->input('reason', 'adjustment');
        $note = $request->input('note');
        if ($delta === 0) { Response::error('change_qty cannot be zero', 422); return; }
        if (($product['quantity'] + $delta) < 0) { Response::error('Resulting stock cannot be negative', 422); return; }

        Product::adjustStock($tenantId, $id, $delta);
        StockLog::log($tenantId, $id, $delta, in_array($reason, ['restock','adjustment','transfer_in','transfer_out']) ? $reason : 'adjustment', Auth::id(), Auth::user()['branch_id'] ?? null, $note);
        ActivityLog::record($tenantId, Auth::id(), 'stock.adjust', "Adjusted stock for #$id by $delta");

        Response::success(Product::find($tenantId, $id), 'Stock updated');
    }

    public function history(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        Response::success(StockLog::historyForProduct($tenantId, $id));
    }

    public function lowStock(Request $request): void
    {
        Response::success(Product::lowStock(Auth::tenantId()));
    }

    public function categories(Request $request): void
    {
        Response::success(Category::allForTenant(Auth::tenantId()));
    }

    public function createCategory(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $name = trim((string) $request->input('name', ''));
        if ($name === '') { Response::error('Name required', 422); return; }
        $id = Category::create(Auth::tenantId(), $name);
        Response::success(['id' => $id, 'name' => $name], 'Category created', 201);
    }

    /**
     * Upload one or more product images. Required before a product can go on the store.
     */
    public function uploadImage(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $productId = (int) $request->param('id');
        $product = Product::find($tenantId, $productId);
        if (!$product) { Response::error('Product not found', 404); return; }

        $file = $request->file('image');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) { Response::error('No valid image uploaded', 422); return; }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) { Response::error('Only JPG, PNG, or WEBP images are allowed', 422); return; }

        $uploadDir = rtrim(Env::get('UPLOAD_DIR', __DIR__ . '/../../../public/uploads'), '/');
        $tenantDir = "$uploadDir/tenant_$tenantId";
        if (!is_dir($tenantDir)) mkdir($tenantDir, 0775, true);

        $filename = 'prod_' . $productId . '_' . uniqid() . '.' . $allowed[$mime];
        $destination = "$tenantDir/$filename";
        move_uploaded_file($file['tmp_name'], $destination);

        $relativePath = "/uploads/tenant_$tenantId/$filename";
        $isPrimary = ProductImage::countForProduct($tenantId, $productId) === 0;
        ProductImage::add($tenantId, $productId, $relativePath, $isPrimary);

        Response::success(['path' => $relativePath], 'Image uploaded', 201);
    }

    /**
     * Toggle a product's visibility on the public online store.
     * RULE: requires at least one product image, per spec.
     */
    public function toggleStore(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        $product = Product::find($tenantId, $id);
        if (!$product) { Response::error('Product not found', 404); return; }

        $onStore = (bool) $request->input('on_store', true);
        if ($onStore && ProductImage::countForProduct($tenantId, $id) === 0) {
            Response::error('This product needs at least one image before it can be listed on the online store. Please upload a product image first.', 422);
            return;
        }

        Product::setOnStore($tenantId, $id, $onStore);
        ActivityLog::record($tenantId, Auth::id(), 'product.store_toggle', ($onStore ? 'Enabled' : 'Disabled') . " store listing for #$id");
        Response::success(Product::find($tenantId, $id), $onStore ? 'Product listed on store' : 'Product removed from store');
    }
}
