<?php
/**
 * SINGLE ENTRYPOINT for the entire application.
 * Run with: php -S localhost:8009 -t public
 *
 * Handles:
 *   /                    -> marketing landing page
 *   /register, /login    -> business self-signup + platform login
 *   /plans (or /pricing) -> subscription plans / upgrade page
 *   /payments/callback   -> Flutterwave return URL (subscriptions + store orders)
 *   /platformadmin[/...] -> platform (super-admin) dashboard, 2 pages
 *   /api/{slug}/...      -> JSON API (backend)
 *   /{slug}portal[/...]  -> Admin/staff SPA shell (server renders once, JS takes over navigation)
 *   /{slug}[/...]        -> Public online storefront
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
} else {
    ini_set('display_errors', '0');
}

// Any uncaught PHP error/exception (e.g. a query against a column that
// doesn't exist yet on an un-migrated database) was previously left to
// PHP's default handler, which prints raw HTML ("<br />\n<b>Fatal
// error</b>...") straight into the response body. On an /api/* request
// that breaks every fetch()'s response.json() with "Unexpected token '<'"
// even though the underlying bug had nothing to do with the frontend.
// Convert both into a clean JSON error response instead, so the real
// cause is visible in the message rather than swallowed by a JSON parse
// failure.
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function (\Throwable $e) {
    $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', rtrim(($GLOBALS['base'] ?? '') . '/api', '/'));
    $debug = Env::get('APP_DEBUG', 'false') === 'true';
    if (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    if ($isApi) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $debug ? $e->getMessage() : 'Something went wrong on the server. Please try again.',
            'errors'  => $debug ? [$e->getFile() . ':' . $e->getLine()] : null,
        ]);
    } else {
        echo $debug ? '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>' : 'Something went wrong.';
    }
    exit;
});

$router = new Router();
$request = new Request();

$auth = new AuthMiddleware();
$tenantStatus = new App\Middleware\TenantStatusMiddleware();
$platformAdmin = new App\Middleware\PlatformAdminMiddleware();

// -----------------------------------------------------------------
// PLATFORM-LEVEL AUTH  — no tenant slug (public marketing site's
// /register and /login pages call these directly)
// -----------------------------------------------------------------
$router->post('/api/auth/register', fn ($r) => (new App\Controllers\Api\PlatformAuthController())->register($r));
$router->post('/api/auth/login', fn ($r) => (new App\Controllers\Api\PlatformAuthController())->login($r));

// Plans (public pricing list + tenant-scoped current status for sidebar gating)
$router->get('/api/plans', fn ($r) => (new App\Controllers\Api\PlanController())->index($r));
$router->get('/api/plan-status', fn ($r) => (new App\Controllers\Api\PlanController())->status($r), [$auth]);

// Subscription payments (Flutterwave)
$router->post('/api/payments/initialize', fn ($r) => (new App\Controllers\Api\PaymentController())->initialize($r), [$auth]);
$router->post('/api/payments/verify', fn ($r) => (new App\Controllers\Api\PaymentController())->verify($r), [$auth]);
$router->get('/api/payments/history', fn ($r) => (new App\Controllers\Api\PaymentController())->history($r), [$auth]);
$router->post('/api/payments/webhook', fn ($r) => (new App\Controllers\Api\PaymentController())->webhook($r));

// Tenant-scoped aliases of the above (so the portal SPA's api.js, which always
// prefixes /api/{slug}, can call these with the same Api.get/Api.post helpers).
// Deliberately NOT behind $tenantStatus — an expired tenant must still be able
// to check its own status and pay.
$router->get('/api/{slug}/plan-status', fn ($r) => (new App\Controllers\Api\PlanController())->status($r), [$auth]);
$router->post('/api/{slug}/payments/initialize', fn ($r) => (new App\Controllers\Api\PaymentController())->initialize($r), [$auth]);
$router->post('/api/{slug}/payments/verify', fn ($r) => (new App\Controllers\Api\PaymentController())->verify($r), [$auth]);
$router->get('/api/{slug}/payments/history', fn ($r) => (new App\Controllers\Api\PaymentController())->history($r), [$auth]);

// -----------------------------------------------------------------
// PLATFORM ADMIN API  — /api/platformadmin/...  (separate auth space)
// -----------------------------------------------------------------
$router->post('/api/platformadmin/auth/login', fn ($r) => (new App\Controllers\Admin\AdminAuthController())->login($r));
$router->get('/api/platformadmin/stats', fn ($r) => (new App\Controllers\Admin\PlatformController())->stats($r), [$platformAdmin]);
$router->get('/api/platformadmin/businesses', fn ($r) => (new App\Controllers\Admin\PlatformController())->businesses($r), [$platformAdmin]);
$router->post('/api/platformadmin/businesses/{id}/remind', fn ($r) => (new App\Controllers\Admin\PlatformController())->remind($r), [$platformAdmin]);
$router->get('/api/platformadmin/plans', fn ($r) => (new App\Controllers\Admin\PlatformController())->plans($r), [$platformAdmin]);
$router->put('/api/platformadmin/plans/{id}', fn ($r) => (new App\Controllers\Admin\PlatformController())->updatePlan($r), [$platformAdmin]);
$router->get('/api/platformadmin/header-images', fn ($r) => (new App\Controllers\Admin\HeaderImageAdminController())->index($r), [$platformAdmin]);
$router->post('/api/platformadmin/header-images', fn ($r) => (new App\Controllers\Admin\HeaderImageAdminController())->upload($r), [$platformAdmin]);
$router->delete('/api/platformadmin/header-images/{id}', fn ($r) => (new App\Controllers\Admin\HeaderImageAdminController())->delete($r), [$platformAdmin]);

// -----------------------------------------------------------------
// API ROUTES  — /api/{slug}/...
// -----------------------------------------------------------------
$router->post('/api/{slug}/auth/login', fn ($r) => (new App\Controllers\Api\AuthController())->login($r));
$router->post('/api/{slug}/auth/refresh', fn ($r) => (new App\Controllers\Api\AuthController())->refresh($r));
$router->get('/api/{slug}/auth/me', fn ($r) => (new App\Controllers\Api\AuthController())->me($r), [$auth, $tenantStatus]);

$router->get('/api/{slug}/dashboard', fn ($r) => (new App\Controllers\Api\DashboardController())->summary($r), [$auth, $tenantStatus]);

$router->get('/api/{slug}/products', fn ($r) => (new App\Controllers\Api\ProductController())->index($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/products/low-stock', fn ($r) => (new App\Controllers\Api\ProductController())->lowStock($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/products/categories', fn ($r) => (new App\Controllers\Api\ProductController())->categories($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/products/categories', fn ($r) => (new App\Controllers\Api\ProductController())->createCategory($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/products/{id}', fn ($r) => (new App\Controllers\Api\ProductController())->show($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/products', fn ($r) => (new App\Controllers\Api\ProductController())->store($r), [$auth, $tenantStatus]);
$router->put('/api/{slug}/products/{id}', fn ($r) => (new App\Controllers\Api\ProductController())->update($r), [$auth, $tenantStatus]);
$router->delete('/api/{slug}/products/{id}', fn ($r) => (new App\Controllers\Api\ProductController())->destroy($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/products/{id}/stock', fn ($r) => (new App\Controllers\Api\ProductController())->adjustStock($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/products/{id}/history', fn ($r) => (new App\Controllers\Api\ProductController())->history($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/products/{id}/image', fn ($r) => (new App\Controllers\Api\ProductController())->uploadImage($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/products/{id}/store', fn ($r) => (new App\Controllers\Api\ProductController())->toggleStore($r), [$auth, $tenantStatus]);

$router->get('/api/{slug}/sales', fn ($r) => (new App\Controllers\Api\SaleController())->index($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/sales/{id}', fn ($r) => (new App\Controllers\Api\SaleController())->show($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/sales', fn ($r) => (new App\Controllers\Api\SaleController())->store($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/sales/{id}/refund', fn ($r) => (new App\Controllers\Api\SaleController())->refund($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/sales/{id}/receipt', fn ($r) => (new App\Controllers\Api\SaleController())->receipt($r), [$auth, $tenantStatus]);

// PUBLIC shareable receipt (no auth) — for the "shareable link" on a printed/emailed receipt
$router->get('/api/{slug}/receipts/{id}/public', fn ($r) => (new App\Controllers\Api\SaleController())->publicReceipt($r));

$router->get('/api/{slug}/customers', fn ($r) => (new App\Controllers\Api\CustomerController())->index($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/customers/{id}', fn ($r) => (new App\Controllers\Api\CustomerController())->show($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/customers', fn ($r) => (new App\Controllers\Api\CustomerController())->store($r), [$auth, $tenantStatus]);
$router->put('/api/{slug}/customers/{id}', fn ($r) => (new App\Controllers\Api\CustomerController())->update($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/customers/{id}/payment', fn ($r) => (new App\Controllers\Api\CustomerController())->recordPayment($r), [$auth, $tenantStatus]);

$router->get('/api/{slug}/expenses', fn ($r) => (new App\Controllers\Api\ExpenseController())->index($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/expenses', fn ($r) => (new App\Controllers\Api\ExpenseController())->store($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/expenses/categories', fn ($r) => (new App\Controllers\Api\ExpenseController())->categories($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/expenses/categories', fn ($r) => (new App\Controllers\Api\ExpenseController())->createCategory($r), [$auth, $tenantStatus]);

$router->get('/api/{slug}/staff', fn ($r) => (new App\Controllers\Api\StaffController())->index($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/staff', fn ($r) => (new App\Controllers\Api\StaffController())->store($r), [$auth, $tenantStatus]);
$router->put('/api/{slug}/staff/{id}', fn ($r) => (new App\Controllers\Api\StaffController())->update($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/activity-log', fn ($r) => (new App\Controllers\Api\StaffController())->activityLog($r), [$auth, $tenantStatus]);

$router->get('/api/{slug}/reports/sales', fn ($r) => (new App\Controllers\Api\ReportController())->sales($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/reports/inventory', fn ($r) => (new App\Controllers\Api\ReportController())->inventory($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/reports/profit', fn ($r) => (new App\Controllers\Api\ReportController())->profit($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/reports/staff-performance', fn ($r) => (new App\Controllers\Api\ReportController())->staffPerformance($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/reports/customers', fn ($r) => (new App\Controllers\Api\ReportController())->customers($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/reports/export', fn ($r) => (new App\Controllers\Api\ReportController())->export($r), [$auth, $tenantStatus]);

$router->get('/api/{slug}/branches', fn ($r) => (new App\Controllers\Api\BranchController())->index($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/branches', fn ($r) => (new App\Controllers\Api\BranchController())->store($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/branches/transfer-stock', fn ($r) => (new App\Controllers\Api\BranchController())->transferStock($r), [$auth, $tenantStatus]);

$router->get('/api/{slug}/ai/insights', fn ($r) => (new App\Controllers\Api\AiController())->insights($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/ai/history', fn ($r) => (new App\Controllers\Api\AiController())->history($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/ai/generate-text', fn ($r) => (new App\Controllers\Api\AiController())->generateText($r), [$auth, $tenantStatus]);

$router->get('/api/{slug}/store-settings', fn ($r) => (new App\Controllers\Api\StoreSettingsController())->show($r), [$auth, $tenantStatus]);
$router->put('/api/{slug}/store-settings', fn ($r) => (new App\Controllers\Api\StoreSettingsController())->update($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/store-settings/stock-images', fn ($r) => (new App\Controllers\Api\StoreSettingsController())->stockImages($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/store-settings/upload/{kind}', fn ($r) => (new App\Controllers\Api\StoreSettingsController())->uploadAsset($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/store-settings/header-images', fn ($r) => (new App\Controllers\Api\StoreSettingsController())->headerImages($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/store-settings/header-image', fn ($r) => (new App\Controllers\Api\StoreSettingsController())->selectHeaderImage($r), [$auth, $tenantStatus]);

$router->get('/api/{slug}/orders', fn ($r) => (new App\Controllers\Api\OrderController())->index($r), [$auth, $tenantStatus]);
$router->get('/api/{slug}/orders/{id}', fn ($r) => (new App\Controllers\Api\OrderController())->show($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/orders/{id}/accept', fn ($r) => (new App\Controllers\Api\OrderController())->accept($r), [$auth, $tenantStatus]);
$router->put('/api/{slug}/orders/{id}/status', fn ($r) => (new App\Controllers\Api\OrderController())->updateStatus($r), [$auth, $tenantStatus]);

// PUBLIC storefront API (no auth) — reads only, tenant-scoped by slug
$router->get('/api/{slug}/store/products', fn ($r) => (new App\Controllers\Api\StoreController())->products($r));
$router->get('/api/{slug}/store/products/{id}', fn ($r) => (new App\Controllers\Api\StoreController())->product($r));
$router->get('/api/{slug}/store/categories', fn ($r) => (new App\Controllers\Api\StoreController())->categories($r));
$router->post('/api/{slug}/store/order', fn ($r) => (new App\Controllers\Api\OrderController())->place($r));
$router->post('/api/{slug}/store/order/{id}/mark-paid', fn ($r) => (new App\Controllers\Api\OrderController())->markPaid($r));
$router->post('/api/{slug}/store/order/{id}/pay', fn ($r) => (new App\Controllers\Api\OrderController())->payInit($r));
$router->post('/api/{slug}/store/order/{id}/pay/verify', fn ($r) => (new App\Controllers\Api\OrderController())->verifyPayment($r));
$router->get('/api/{slug}/earnings', fn ($r) => (new App\Controllers\Api\EarningsController())->summary($r), [$auth, $tenantStatus]);
$router->post('/api/{slug}/earnings/withdraw', fn ($r) => (new App\Controllers\Api\EarningsController())->requestWithdrawal($r), [$auth, $tenantStatus]);

// -----------------------------------------------------------------
// PUBLIC MARKETING SITE + AUTH PAGES — must be registered before the
// generic /{slug} storefront catch-all below so these literal paths
// always win (slugs like "register" are also blocked at signup time).
// -----------------------------------------------------------------
$router->get('/', function () {
    header('Content-Type: text/html');
    require __DIR__ . '/views/marketing/index.php';
});
$router->get('/register', function () {
    header('Content-Type: text/html');
    require __DIR__ . '/views/marketing/register.php';
});
$router->get('/login', function () {
    header('Content-Type: text/html');
    require __DIR__ . '/views/marketing/login.php';
});
$router->get('/plans', function () {
    header('Content-Type: text/html');
    require __DIR__ . '/views/marketing/plans.php';
});
$router->get('/pricing', function () {
    header('Content-Type: text/html');
    require __DIR__ . '/views/marketing/plans.php';
});
$router->get('/payments/callback', function (Request $r) {
    header('Content-Type: text/html');
    require __DIR__ . '/views/marketing/payment-callback.php';
});

// -----------------------------------------------------------------
// PLATFORM ADMIN — /platformadmin (stats + plan mgmt) and
// /platformadmin/businesses (business list + reminders)
// -----------------------------------------------------------------
$router->get('/platformadmin', function () {
    header('Content-Type: text/html');
    require __DIR__ . '/views/platformadmin/layout.php';
});
$router->get('/platformadmin/businesses', function () {
    header('Content-Type: text/html');
    require __DIR__ . '/views/platformadmin/layout.php';
});

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
