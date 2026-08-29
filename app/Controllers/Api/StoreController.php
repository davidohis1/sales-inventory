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

    /** PUBLIC — list reviews for a product (reviewer email is never included). */
    public function productReviews(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Store not found', 404); return; }

        $productId = (int) $request->param('id');
        $product = Product::find((int) $tenant['id'], $productId);
        if (!$product || !$product['is_on_store']) { Response::error('Product not found', 404); return; }

        Response::success(\App\Models\ProductReview::forProduct((int) $tenant['id'], $productId));
    }

    /** PUBLIC — anyone can leave a review with just their name, email, and review text. */
    public function submitReview(Request $request): void
    {
        $slug = $request->param('slug');
        $tenant = Tenant::findBySlug($slug);
        if (!$tenant) { Response::error('Store not found', 404); return; }

        $productId = (int) $request->param('id');
        $product = Product::find((int) $tenant['id'], $productId);
        if (!$product || !$product['is_on_store']) { Response::error('Product not found', 404); return; }

        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $reviewText = trim((string) $request->input('review', ''));

        if ($name === '' || $email === '' || $reviewText === '') {
            Response::error('Name, email, and a review are required', 422);
            return;
        }
        if (!str_contains($email, '@')) {
            Response::error('Please enter a valid email address', 422);
            return;
        }

        $id = \App\Models\ProductReview::create((int) $tenant['id'], $productId, $name, $email, $reviewText);
        Response::success(['id' => $id], 'Thanks for your review!', 201);
    }
}