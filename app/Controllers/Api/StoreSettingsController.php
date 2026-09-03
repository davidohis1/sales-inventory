<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\StockImages;
use App\Models\ActivityLog;
use App\Models\StoreSettings;
use App\Models\Tenant;

class StoreSettingsController
{
    /** Static metadata about the 5 available templates, for the admin's picker UI. */
    private function themeCatalog(): array
    {
        return [
            ['id' => 'aurora', 'name' => 'Aurora', 'description' => 'Minimal grid with a category & filter sidebar. Great for focused catalogs.', 'accent' => '#4f46e5'],
            ['id' => 'wink', 'name' => 'Wink', 'description' => 'Clean product-list layout with brand/category filters and a promo strip.', 'accent' => '#111827'],
            ['id' => 'luxora', 'name' => 'Luxora', 'description' => 'Elegant, editorial fashion storefront with a full hero and testimonials.', 'accent' => '#8a6d3b'],
            ['id' => 'marketly', 'name' => 'Marketly', 'description' => 'Bold marketplace layout with flash deals and category tiles.', 'accent' => '#2563eb'],
            ['id' => 'novatrend', 'name' => 'NovaTrend', 'description' => 'Trendy lifestyle storefront with a model hero and floating product cards.', 'accent' => '#ea580c'],
        ];
    }

    public function show(Request $request): void
    {
        $settings = StoreSettings::get(Auth::tenantId());
        Response::success([
            'theme' => $settings['theme'],
            'store_type' => $settings['store_type'],
            'content' => $settings['content'],
            'themes' => $this->themeCatalog(),
            'store_types' => StoreSettings::STORE_TYPES,
        ]);
    }

    public function update(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();

        $theme = (string) $request->input('theme', 'aurora');
        $storeType = (string) $request->input('store_type', 'general');
        $content = $request->input('content', []);

        if (!in_array($theme, StoreSettings::THEMES, true)) { Response::error('Invalid theme', 422); return; }
        if (!in_array($storeType, StoreSettings::STORE_TYPES, true)) { Response::error('Invalid store type', 422); return; }
        if (!is_array($content)) { $content = []; }

        StoreSettings::upsert($tenantId, $theme, $storeType, $content);
        ActivityLog::record($tenantId, Auth::id(), 'store.settings_update', "Updated store settings (theme: $theme)");

        Response::success(StoreSettings::get($tenantId), 'Store settings saved');
    }

    /** PUBLIC (no auth) — used by the storefront + for live-previewing stock imagery in the admin editor. */
    public function stockImages(Request $request): void
    {
        $storeType = (string) $request->input('store_type', 'general');
        Response::success(StockImages::bank($storeType, 10));
    }

    /** Pre-uploaded header images for a category, for the tenant's header picker (Theme tab). */
    public function headerImages(Request $request): void
    {
        $storeType = (string) $request->input('store_type', 'general');
        if (!in_array($storeType, StoreSettings::STORE_TYPES, true)) { Response::error('Invalid category', 422); return; }
        Response::success(\App\Models\HeaderImage::forCategory($storeType));
    }

    /** Sets the storefront banner to one of the pre-uploaded header images (instead of a custom upload). */
    public function selectHeaderImage(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $tenantId = Auth::tenantId();
        $imageId = (int) $request->input('header_image_id', 0);
        $image = \App\Models\HeaderImage::find($imageId);
        if (!$image) { Response::error('Header image not found', 404); return; }

        $settings = StoreSettings::get($tenantId);
        $content = $settings['content'];
        $content['banner_path'] = $image['image_path'];
        StoreSettings::upsert($tenantId, $settings['theme'], $settings['store_type'], $content);
        ActivityLog::record($tenantId, Auth::id(), 'store.header_select', 'Selected a pre-uploaded header image');

        Response::success(StoreSettings::get($tenantId), 'Header image applied');
    }

    /** Uploads the store's logo or hero banner image (kind = logo|banner) and saves its path into content. */
    public function uploadAsset(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $kind = (string) $request->param('kind');
        if (!in_array($kind, ['logo', 'banner'], true)) { Response::error('Invalid asset type', 422); return; }

        $file = $request->file('image');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) { Response::error('No valid image uploaded', 422); return; }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) { Response::error('Only JPG, PNG, or WEBP images are allowed', 422); return; }

        $tenantId = Auth::tenantId();
        $uploadDir = rtrim(\App\Core\Env::get('UPLOAD_DIR', __DIR__ . '/../../../public/uploads'), '/');
        $storeDir = "$uploadDir/tenant_$tenantId/store";
        if (!is_dir($storeDir)) mkdir($storeDir, 0775, true);

        $filename = $kind . '_' . uniqid() . '.' . $allowed[$mime];
        move_uploaded_file($file['tmp_name'], "$storeDir/$filename");
        $relativePath = "/uploads/tenant_$tenantId/store/$filename";

        $settings = StoreSettings::get($tenantId);
        $content = $settings['content'];
        $content[$kind . '_path'] = $relativePath;
        StoreSettings::upsert($tenantId, $settings['theme'], $settings['store_type'], $content);

        Response::success(['path' => $relativePath], ucfirst($kind) . ' uploaded');
    }
}
