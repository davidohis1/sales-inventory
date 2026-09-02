<?php
/**
 * Demo data seeder — run from the project root:
 *   php database/seed.php
 *
 * Creates one demo tenant ("ajtech") with branches, staff, products,
 * customers, expenses, and a couple of sample sales, so the app is
 * testable immediately after setup. Safe to re-run: it clears any
 * existing "ajtech" tenant data first (cascades via foreign keys).
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Core\Database;

Env::load(__DIR__ . '/../.env');
$pdo = Database::connect();

echo "Seeding demo data for tenant 'ajtech'...\n";

// Wipe any existing demo tenant (cascades to all child tables via FKs)
$pdo->exec("DELETE FROM tenants WHERE slug = 'ajtech'");

$pdo->exec("INSERT INTO tenants (slug, business_name, owner_email, currency, plan_id, subscription_status, subscription_ends_at, is_active)
            VALUES ('ajtech', 'AJ Tech Gadgets', 'owner@ajtech.com', 'NGN', 3, 'active', DATE_ADD(NOW(), INTERVAL 365 DAY), 1)");
$tenantId = (int) $pdo->lastInsertId();

$pdo->prepare("INSERT INTO branches (tenant_id, name, address, is_main, is_active) VALUES (?, 'Main Branch (Suleja)', 'Suleja, Niger State', 1, 1)")->execute([$tenantId]);
$mainBranchId = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO branches (tenant_id, name, address, is_main, is_active) VALUES (?, 'Abuja Branch', 'Wuse, Abuja', 0, 1)")->execute([$tenantId]);

$staff = [
    ['Ada Johnson', 'owner@ajtech.com', 'owner'],
    ['Musa Bello', 'manager@ajtech.com', 'manager'],
    ['Chidinma Eze', 'staff@ajtech.com', 'staff'],
];
$userIds = [];
foreach ($staff as [$name, $email, $role]) {
    $hash = password_hash('password123', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (tenant_id, branch_id, full_name, email, password_hash, role, is_active) VALUES (?,?,?,?,?,?,1)")
        ->execute([$tenantId, $mainBranchId, $name, $email, $hash, $role]);
    $userIds[$role] = (int) $pdo->lastInsertId();
}

$categories = ['Phones' => null, 'Accessories' => null, 'Laptops' => null];
foreach (array_keys($categories) as $name) {
    $pdo->prepare("INSERT INTO categories (tenant_id, name) VALUES (?,?)")->execute([$tenantId, $name]);
    $categories[$name] = (int) $pdo->lastInsertId();
}

$products = [
    ['Tecno Spark 20', 'TEC-SPK20', 'Phones', 95000, 120000, 25, 5],
    ['Infinix Note 40', 'INF-NT40', 'Phones', 130000, 165000, 14, 5],
    ['Type-C Fast Charger', 'ACC-TC-CHG', 'Accessories', 3500, 6000, 60, 10],
    ['Wireless Earbuds', 'ACC-WEB-01', 'Accessories', 8000, 15000, 3, 5],
    ['HP Probook 450', 'LAP-HP450', 'Laptops', 320000, 385000, 6, 3],
    ['Phone Case (Universal)', 'ACC-CASE-U', 'Accessories', 500, 1500, 0, 10],
];
$productIds = [];
foreach ($products as [$name, $sku, $cat, $buy, $sell, $qty, $min]) {
    $pdo->prepare("INSERT INTO products (tenant_id, category_id, branch_id, name, sku, buying_price, selling_price, quantity, min_stock_level, is_active)
                   VALUES (?,?,?,?,?,?,?,?,?,1)")
        ->execute([$tenantId, $categories[$cat], $mainBranchId, $name, $sku, $buy, $sell, $qty, $min]);
    $id = (int) $pdo->lastInsertId();
    $productIds[$sku] = $id;
    if ($qty > 0) {
        $pdo->prepare("INSERT INTO stock_logs (tenant_id, product_id, branch_id, user_id, change_qty, reason, note) VALUES (?,?,?,?,?, 'initial', 'Opening stock')")
            ->execute([$tenantId, $id, $mainBranchId, $userIds['owner'], $qty]);
    }
}

$pdo->prepare("INSERT INTO customers (tenant_id, name, phone, email, credit_limit, outstanding_debt) VALUES (?, 'Walk-in Customer', NULL, NULL, 0, 0)")->execute([$tenantId]);
$pdo->prepare("INSERT INTO customers (tenant_id, name, phone, email, credit_limit, outstanding_debt) VALUES (?, 'Ibrahim Sule', '08033333333', 'ibrahim@example.com', 50000, 12000)")->execute([$tenantId]);
$customer2Id = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO customers (tenant_id, name, phone, email, credit_limit, outstanding_debt) VALUES (?, 'Grace Okafor', '08044444444', 'grace@example.com', 30000, 0)")->execute([$tenantId]);

$pdo->prepare("INSERT INTO expense_categories (tenant_id, name) VALUES (?, 'Rent')")->execute([$tenantId]);
$rentCatId = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO expense_categories (tenant_id, name) VALUES (?, 'Transport')")->execute([$tenantId]);
$transportCatId = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO expense_categories (tenant_id, name) SELECT $tenantId, 'Utilities'");

$pdo->prepare("INSERT INTO expenses (tenant_id, branch_id, category_id, user_id, title, amount, expense_date) VALUES (?,?,?,?, 'August Shop Rent', 45000, CURDATE())")
    ->execute([$tenantId, $mainBranchId, $rentCatId, $userIds['owner']]);
$pdo->prepare("INSERT INTO expenses (tenant_id, branch_id, category_id, user_id, title, amount, expense_date) VALUES (?,?,?,?, 'Fuel for delivery', 5000, CURDATE())")
    ->execute([$tenantId, $mainBranchId, $transportCatId, $userIds['owner']]);

// Sample sale #1: Tecno Spark 20, cash, walk-in customer
$pdo->prepare("INSERT INTO sales (tenant_id, branch_id, customer_id, user_id, receipt_no, subtotal, discount, total, amount_paid, balance_due, payment_method, sale_type, status)
               VALUES (?,?,NULL,?, 'RCT-0001', 121500, 1500, 120000, 120000, 0, 'cash', 'in_store', 'completed')")
    ->execute([$tenantId, $mainBranchId, $userIds['staff']]);
$sale1Id = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO sale_items (tenant_id, sale_id, product_id, quantity, unit_cost, unit_price, discount, line_total) VALUES (?,?,?,1,95000,120000,1500,120000)")
    ->execute([$tenantId, $sale1Id, $productIds['TEC-SPK20']]);
$pdo->prepare("INSERT INTO sale_payments (tenant_id, sale_id, method, amount) VALUES (?,?, 'cash', 120000)")->execute([$tenantId, $sale1Id]);
$pdo->prepare("UPDATE products SET quantity = quantity - 1 WHERE id = ?")->execute([$productIds['TEC-SPK20']]);

// Sample sale #2: charger + earbuds, partial credit sale for Ibrahim
$pdo->prepare("INSERT INTO sales (tenant_id, branch_id, customer_id, user_id, receipt_no, subtotal, discount, total, amount_paid, balance_due, payment_method, sale_type, status)
               VALUES (?,?,?,?, 'RCT-0002', 21000, 0, 21000, 9000, 12000, 'credit', 'in_store', 'completed')")
    ->execute([$tenantId, $mainBranchId, $customer2Id, $userIds['manager']]);
$sale2Id = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO sale_items (tenant_id, sale_id, product_id, quantity, unit_cost, unit_price, discount, line_total) VALUES (?,?,?,1,3500,6000,0,6000)")
    ->execute([$tenantId, $sale2Id, $productIds['ACC-TC-CHG']]);
$pdo->prepare("INSERT INTO sale_items (tenant_id, sale_id, product_id, quantity, unit_cost, unit_price, discount, line_total) VALUES (?,?,?,1,8000,15000,0,15000)")
    ->execute([$tenantId, $sale2Id, $productIds['ACC-WEB-01']]);
$pdo->prepare("INSERT INTO sale_payments (tenant_id, sale_id, method, amount) VALUES (?,?, 'transfer', 9000)")->execute([$tenantId, $sale2Id]);
$pdo->prepare("UPDATE products SET quantity = quantity - 1 WHERE id = ?")->execute([$productIds['ACC-TC-CHG']]);
$pdo->prepare("UPDATE products SET quantity = quantity - 1 WHERE id = ?")->execute([$productIds['ACC-WEB-01']]);

$pdo->prepare("INSERT INTO activity_logs (tenant_id, user_id, action, description) VALUES (?,?, 'sale.create', 'Created sale RCT-0001')")->execute([$tenantId, $userIds['staff']]);
$pdo->prepare("INSERT INTO activity_logs (tenant_id, user_id, action, description) VALUES (?,?, 'sale.create', 'Created sale RCT-0002 (credit)')")->execute([$tenantId, $userIds['manager']]);

// Default storefront settings — NovaTrend theme, tech store type (fits "AJ Tech Gadgets")
$pdo->prepare("INSERT INTO store_settings (tenant_id, theme, store_type, content) VALUES (?, 'novatrend', 'tech', ?)")
    ->execute([$tenantId, json_encode([
        'eyebrow' => 'TRENDING NOW',
        'hero_heading' => "Discover Gadgets You'll Love",
        'hero_subheading' => 'Shop the latest phones, laptops, and accessories at AJ Tech Gadgets.',
        'order_channel' => 'email',
        'notification_email' => 'owner@ajtech.com',
    ])]);

// Platform admin (the SaaS operator's own login) — created once, idempotent
$pdo->exec("DELETE FROM platform_admins WHERE email = 'admin@platform.com'");
$pdo->prepare("INSERT INTO platform_admins (full_name, email, password_hash) VALUES (?,?,?)")
    ->execute(['Platform Admin', 'admin@platform.com', password_hash('admin12345', PASSWORD_BCRYPT)]);

echo "Done!\n\n";
echo "Storefront:   http://localhost:8009/ajtech\n";
echo "Admin portal: http://localhost:8009/ajtechportal\n\n";
echo "Login accounts (password: password123):\n";
echo "  Owner:   owner@ajtech.com\n";
echo "  Manager: manager@ajtech.com\n";
echo "  Staff:   staff@ajtech.com\n\n";
echo "Platform admin: http://localhost:8009/platformadmin\n";
echo "  Email:    admin@platform.com\n";
echo "  Password: admin12345\n";
