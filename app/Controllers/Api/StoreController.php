<?php
namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Models\Product;
use App\Models\Tenant;

/**
 * PUBLIC (unauthenticated) storefront API — read-only product listing for a tenant's public store.
 */
class StoreController
{
    public function products(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Store not found', 404); return; }

        $q = (string) $request->input('q', '');
        $categoryId = $request->input('category_id') ?: null;
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');

        $products = Product::search(
            (int) $tenant['id'], $q, null, true, true, 100,
            $categoryId ? (int) $categoryId : null,
            $minPrice !== null && $minPrice !== '' ? (float) $minPrice : null,
            $maxPrice !== null && $maxPrice !== '' ? (float) $maxPrice : null
        );
        Response::success(['tenant' => ['business_name' => $tenant['business_name'], 'currency' => $tenant['currency']], 'products' => $products]);
    }

    public function product(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Store not found', 404); return; }

        $product = Product::findWithImages((int) $tenant['id'], (int) $request->param('id'));
        if (!$product || !$product['is_on_store']) { Response::error('Product not found', 404); return; }
        Response::success($product);
    }

    /** PUBLIC — categories used for the storefront's filter sidebar/pills. */
    public function categories(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Store not found', 404); return; }
        Response::success(\App\Models\Category::allForTenant((int) $tenant['id']));
    }
}
