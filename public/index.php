<?php
/**
 * SINGLE ENTRYPOINT for the entire application.
 * Run with: php -S localhost:8009 -t public
 *
 * Handles:
 *   /api/{slug}/...     -> JSON API (backend)
 *   /{slug}portal[/...] -> Admin/staff SPA shell (server renders once, JS takes over navigation)
 *   /{slug}[/...]       -> Public online storefront
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Core\Request;
use App\Core\Router;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

Env::load(__DIR__ . '/../.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Africa/Lagos'));

// Base path support: leave APP_BASE_PATH blank when running the documented
// `php -S localhost:8009 -t public` (document root = this "public" folder).
// Only set it if your web server's document root is NOT this folder — e.g.
// the whole project was copied into an Apache/XAMPP htdocs folder instead of
// pointing the vhost at "public". Every asset/upload/API/navigation link in
// the app is built through $GLOBALS['base'] / window.APP_BASE so it works
// either way.
$GLOBALS['base'] = rtrim(Env::get('APP_BASE_PATH', ''), '/');

if (Env::get('APP_DEBUG', 'false') === 'true') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$router = new Router();
$request = new Request();

$auth = new AuthMiddleware();

// -----------------------------------------------------------------
// API ROUTES  — /api/{slug}/...
// -----------------------------------------------------------------
$router->post('/api/{slug}/auth/login', fn ($r) => (new App\Controllers\Api\AuthController())->login($r));
$router->post('/api/{slug}/auth/refresh', fn ($r) => (new App\Controllers\Api\AuthController())->refresh($r));
$router->get('/api/{slug}/auth/me', fn ($r) => (new App\Controllers\Api\AuthController())->me($r), [$auth]);

$router->get('/api/{slug}/dashboard', fn ($r) => (new App\Controllers\Api\DashboardController())->summary($r), [$auth]);

$router->get('/api/{slug}/products', fn ($r) => (new App\Controllers\Api\ProductController())->index($r), [$auth]);
$router->get('/api/{slug}/products/low-stock', fn ($r) => (new App\Controllers\Api\ProductController())->lowStock($r), [$auth]);
$router->get('/api/{slug}/products/categories', fn ($r) => (new App\Controllers\Api\ProductController())->categories($r), [$auth]);
$router->post('/api/{slug}/products/categories', fn ($r) => (new App\Controllers\Api\ProductController())->createCategory($r), [$auth]);
$router->get('/api/{slug}/products/{id}', fn ($r) => (new App\Controllers\Api\ProductController())->show($r), [$auth]);
$router->post('/api/{slug}/products', fn ($r) => (new App\Controllers\Api\ProductController())->store($r), [$auth]);
$router->put('/api/{slug}/products/{id}', fn ($r) => (new App\Controllers\Api\ProductController())->update($r), [$auth]);
$router->delete('/api/{slug}/products/{id}', fn ($r) => (new App\Controllers\Api\ProductController())->destroy($r), [$auth]);
$router->post('/api/{slug}/products/{id}/stock', fn ($r) => (new App\Controllers\Api\ProductController())->adjustStock($r), [$auth]);
$router->get('/api/{slug}/products/{id}/history', fn ($r) => (new App\Controllers\Api\ProductController())->history($r), [$auth]);
$router->post('/api/{slug}/products/{id}/image', fn ($r) => (new App\Controllers\Api\ProductController())->uploadImage($r), [$auth]);
$router->post('/api/{slug}/products/{id}/store', fn ($r) => (new App\Controllers\Api\ProductController())->toggleStore($r), [$auth]);

$router->get('/api/{slug}/sales', fn ($r) => (new App\Controllers\Api\SaleController())->index($r), [$auth]);
$router->get('/api/{slug}/sales/{id}', fn ($r) => (new App\Controllers\Api\SaleController())->show($r), [$auth]);
$router->post('/api/{slug}/sales', fn ($r) => (new App\Controllers\Api\SaleController())->store($r), [$auth]);
$router->post('/api/{slug}/sales/{id}/refund', fn ($r) => (new App\Controllers\Api\SaleController())->refund($r), [$auth]);
$router->get('/api/{slug}/sales/{id}/receipt', fn ($r) => (new App\Controllers\Api\SaleController())->receipt($r), [$auth]);

// PUBLIC shareable receipt (no auth) — for the "shareable link" on a printed/emailed receipt
$router->get('/api/{slug}/receipts/{id}/public', fn ($r) => (new App\Controllers\Api\SaleController())->publicReceipt($r));

$router->get('/api/{slug}/customers', fn ($r) => (new App\Controllers\Api\CustomerController())->index($r), [$auth]);
$router->get('/api/{slug}/customers/{id}', fn ($r) => (new App\Controllers\Api\CustomerController())->show($r), [$auth]);
$router->post('/api/{slug}/customers', fn ($r) => (new App\Controllers\Api\CustomerController())->store($r), [$auth]);
$router->put('/api/{slug}/customers/{id}', fn ($r) => (new App\Controllers\Api\CustomerController())->update($r), [$auth]);
$router->post('/api/{slug}/customers/{id}/payment', fn ($r) => (new App\Controllers\Api\CustomerController())->recordPayment($r), [$auth]);

$router->get('/api/{slug}/expenses', fn ($r) => (new App\Controllers\Api\ExpenseController())->index($r), [$auth]);
$router->post('/api/{slug}/expenses', fn ($r) => (new App\Controllers\Api\ExpenseController())->store($r), [$auth]);
$router->get('/api/{slug}/expenses/categories', fn ($r) => (new App\Controllers\Api\ExpenseController())->categories($r), [$auth]);
$router->post('/api/{slug}/expenses/categories', fn ($r) => (new App\Controllers\Api\ExpenseController())->createCategory($r), [$auth]);

$router->get('/api/{slug}/staff', fn ($r) => (new App\Controllers\Api\StaffController())->index($r), [$auth]);
$router->post('/api/{slug}/staff', fn ($r) => (new App\Controllers\Api\StaffController())->store($r), [$auth]);
$router->put('/api/{slug}/staff/{id}', fn ($r) => (new App\Controllers\Api\StaffController())->update($r), [$auth]);
$router->get('/api/{slug}/activity-log', fn ($r) => (new App\Controllers\Api\StaffController())->activityLog($r), [$auth]);

$router->get('/api/{slug}/reports/sales', fn ($r) => (new App\Controllers\Api\ReportController())->sales($r), [$auth]);
$router->get('/api/{slug}/reports/inventory', fn ($r) => (new App\Controllers\Api\ReportController())->inventory($r), [$auth]);
$router->get('/api/{slug}/reports/profit', fn ($r) => (new App\Controllers\Api\ReportController())->profit($r), [$auth]);
$router->get('/api/{slug}/reports/staff-performance', fn ($r) => (new App\Controllers\Api\ReportController())->staffPerformance($r), [$auth]);
$router->get('/api/{slug}/reports/customers', fn ($r) => (new App\Controllers\Api\ReportController())->customers($r), [$auth]);
$router->get('/api/{slug}/reports/export', fn ($r) => (new App\Controllers\Api\ReportController())->export($r), [$auth]);

$router->get('/api/{slug}/branches', fn ($r) => (new App\Controllers\Api\BranchController())->index($r), [$auth]);
$router->post('/api/{slug}/branches', fn ($r) => (new App\Controllers\Api\BranchController())->store($r), [$auth]);
$router->post('/api/{slug}/branches/transfer-stock', fn ($r) => (new App\Controllers\Api\BranchController())->transferStock($r), [$auth]);

$router->get('/api/{slug}/ai/insights', fn ($r) => (new App\Controllers\Api\AiController())->insights($r), [$auth]);
$router->get('/api/{slug}/ai/history', fn ($r) => (new App\Controllers\Api\AiController())->history($r), [$auth]);
$router->post('/api/{slug}/ai/generate-text', fn ($r) => (new App\Controllers\Api\AiController())->generateText($r), [$auth]);

$router->get('/api/{slug}/store-settings', fn ($r) => (new App\Controllers\Api\StoreSettingsController())->show($r), [$auth]);
$router->put('/api/{slug}/store-settings', fn ($r) => (new App\Controllers\Api\StoreSettingsController())->update($r), [$auth]);
$router->get('/api/{slug}/store-settings/stock-images', fn ($r) => (new App\Controllers\Api\StoreSettingsController())->stockImages($r), [$auth]);
$router->post('/api/{slug}/store-settings/upload/{kind}', fn ($r) => (new App\Controllers\Api\StoreSettingsController())->uploadAsset($r), [$auth]);

$router->get('/api/{slug}/orders', fn ($r) => (new App\Controllers\Api\OrderController())->index($r), [$auth]);
$router->get('/api/{slug}/orders/{id}', fn ($r) => (new App\Controllers\Api\OrderController())->show($r), [$auth]);
$router->post('/api/{slug}/orders/{id}/accept', fn ($r) => (new App\Controllers\Api\OrderController())->accept($r), [$auth]);
$router->put('/api/{slug}/orders/{id}/status', fn ($r) => (new App\Controllers\Api\OrderController())->updateStatus($r), [$auth]);

// PUBLIC storefront API (no auth) — reads only, tenant-scoped by slug
$router->get('/api/{slug}/store/products', fn ($r) => (new App\Controllers\Api\StoreController())->products($r));
$router->get('/api/{slug}/store/products/{id}', fn ($r) => (new App\Controllers\Api\StoreController())->product($r));
$router->get('/api/{slug}/store/categories', fn ($r) => (new App\Controllers\Api\StoreController())->categories($r));
$router->post('/api/{slug}/store/order', fn ($r) => (new App\Controllers\Api\OrderController())->place($r));
$router->post('/api/{slug}/store/order/{id}/mark-paid', fn ($r) => (new App\Controllers\Api\OrderController())->markPaid($r));

// -----------------------------------------------------------------
// ADMIN PORTAL — /{slug}portal  (server renders the SPA shell once;
// admin.js then loads each module's markup + data via fetch, no reloads)
// -----------------------------------------------------------------
$router->get('/{slug:*}portal', function (Request $r) {
    renderPortalShell($r->param('slug'));
});
$router->get('/{slug:*}portal/{sub:*}', function (Request $r) {
    // Deep-link support: reloading e.g. /ajtechportal/products still serves the SPA shell;
    // admin.js reads location.pathname itself to restore the right module.
    renderPortalShell($r->param('slug'));
});

// -----------------------------------------------------------------
// PUBLIC STOREFRONT — /{slug}[/...]
// -----------------------------------------------------------------
$router->get('/{slug}', function (Request $r) {
    renderStore($r->param('slug'));
});
$router->get('/{slug}/product/{id}', function (Request $r) {
    renderStore($r->param('slug'), 'product', ['id' => $r->param('id')]);
});
$router->get('/{slug}/cart', function (Request $r) {
    renderStore($r->param('slug'), 'cart');
});
$router->get('/{slug}/checkout', function (Request $r) {
    renderStore($r->param('slug'), 'checkout');
});
$router->get('/{slug}/receipt/{id}', function (Request $r) {
    renderStore($r->param('slug'), 'receipt', ['id' => $r->param('id')]);
});

$router->get('/', function () {
    $base = $GLOBALS['base'];
    header('Content-Type: text/html');
    echo "<h2>Sales, Inventory &amp; Business Management System</h2>
          <p>Visit <code>{$base}/{slug}</code> for a tenant's storefront, or <code>{$base}/{slug}portal</code> for the admin portal.</p>
          <p>Demo tenant: <a href='{$base}/ajtech'>{$base}/ajtech</a> &middot; <a href='{$base}/ajtechportal'>{$base}/ajtechportal</a></p>";
});

$router->get('/api/{slug}/store/products/{id}/reviews', fn ($r) => (new App\Controllers\Api\StoreController())->productReviews($r));
$router->post('/api/{slug}/store/products/{id}/reviews', fn ($r) => (new App\Controllers\Api\StoreController())->submitReview($r));

// -----------------------------------------------------------------
// helper renderers
// -----------------------------------------------------------------
function renderPortalShell(string $slug): void
{
    $tenant = \App\Models\Tenant::findBySlug($slug);
    if (!$tenant) { http_response_code(404); echo 'Business not found.'; return; }
    $base = $GLOBALS['base'];
    header('Content-Type: text/html');
    require __DIR__ . '/views/portal/layout.php';
}

function renderStore(string $slug, string $page = 'index', array $params = []): void
{
    $tenant = \App\Models\Tenant::findBySlug($slug);
    if (!$tenant) { http_response_code(404); echo 'Store not found.'; return; }
    $settings = \App\Models\StoreSettings::get((int) $tenant['id']);
    $base = $GLOBALS['base'];
    header('Content-Type: text/html');

    if ($page === 'index') {
        // The storefront homepage/product-list is rendered by whichever of the
        // 5 templates the admin picked in Store Settings.
        $theme = in_array($settings['theme'], \App\Models\StoreSettings::THEMES, true) ? $settings['theme'] : 'aurora';
        $view = __DIR__ . "/views/store/themes/$theme.php";
        if (!file_exists($view)) { $view = __DIR__ . '/views/store/themes/aurora.php'; }
        require $view;
        return;
    }

    $view = __DIR__ . "/views/store/$page.php";
    if (!file_exists($view)) { http_response_code(404); echo 'Page not found.'; return; }
    require $view;
}

$router->dispatch($request);
