-- =====================================================================
-- Demo seed data (SQL-only version — no PHP required).
-- Login accounts (password for all three: password123):
--   owner@ajtech.com / manager@ajtech.com / staff@ajtech.com
-- Safe to re-run: it deletes any existing "ajtech" tenant first.
-- =====================================================================

DELETE FROM tenants WHERE slug = 'ajtech';

INSERT INTO tenants (slug, business_name, currency, is_active)
VALUES ('ajtech', 'AJ Tech Gadgets', 'NGN', 1);
SET @tenant_id = LAST_INSERT_ID();

INSERT INTO branches (tenant_id, name, address, is_main, is_active)
VALUES (@tenant_id, 'Main Branch (Suleja)', 'Suleja, Niger State', 1, 1);
SET @main_branch_id = LAST_INSERT_ID();

INSERT INTO branches (tenant_id, name, address, is_main, is_active)
VALUES (@tenant_id, 'Abuja Branch', 'Wuse, Abuja', 0, 1);

-- Password hash below is bcrypt for "password123" (verified compatible with PHP's password_verify())
INSERT INTO users (tenant_id, branch_id, full_name, email, password_hash, role, is_active)
VALUES (@tenant_id, @main_branch_id, 'Ada Johnson', 'owner@ajtech.com', '$2b$10$TjfnA9mZiWWfU4H/v9ELV.635oHoiX0z9R4g0ILKa/hIZvj1nVLFq', 'owner', 1);
SET @owner_id = LAST_INSERT_ID();

INSERT INTO users (tenant_id, branch_id, full_name, email, password_hash, role, is_active)
VALUES (@tenant_id, @main_branch_id, 'Musa Bello', 'manager@ajtech.com', '$2b$10$TjfnA9mZiWWfU4H/v9ELV.635oHoiX0z9R4g0ILKa/hIZvj1nVLFq', 'manager', 1);
SET @manager_id = LAST_INSERT_ID();

INSERT INTO users (tenant_id, branch_id, full_name, email, password_hash, role, is_active)
VALUES (@tenant_id, @main_branch_id, 'Chidinma Eze', 'staff@ajtech.com', '$2b$10$TjfnA9mZiWWfU4H/v9ELV.635oHoiX0z9R4g0ILKa/hIZvj1nVLFq', 'staff', 1);
SET @staff_id = LAST_INSERT_ID();

INSERT INTO categories (tenant_id, name) VALUES (@tenant_id, 'Phones');
SET @cat_phones = LAST_INSERT_ID();
INSERT INTO categories (tenant_id, name) VALUES (@tenant_id, 'Accessories');
SET @cat_accessories = LAST_INSERT_ID();
INSERT INTO categories (tenant_id, name) VALUES (@tenant_id, 'Laptops');
SET @cat_laptops = LAST_INSERT_ID();

INSERT INTO products (tenant_id, category_id, branch_id, name, sku, buying_price, selling_price, quantity, min_stock_level, is_active)
VALUES (@tenant_id, @cat_phones, @main_branch_id, 'Tecno Spark 20', 'TEC-SPK20', 95000, 120000, 25, 5, 1);
SET @p_tecno = LAST_INSERT_ID();

INSERT INTO products (tenant_id, category_id, branch_id, name, sku, buying_price, selling_price, quantity, min_stock_level, is_active)
VALUES (@tenant_id, @cat_phones, @main_branch_id, 'Infinix Note 40', 'INF-NT40', 130000, 165000, 14, 5, 1);
SET @p_infinix = LAST_INSERT_ID();

INSERT INTO products (tenant_id, category_id, branch_id, name, sku, buying_price, selling_price, quantity, min_stock_level, is_active)
VALUES (@tenant_id, @cat_accessories, @main_branch_id, 'Type-C Fast Charger', 'ACC-TC-CHG', 3500, 6000, 60, 10, 1);
SET @p_charger = LAST_INSERT_ID();

INSERT INTO products (tenant_id, category_id, branch_id, name, sku, buying_price, selling_price, quantity, min_stock_level, is_active)
VALUES (@tenant_id, @cat_accessories, @main_branch_id, 'Wireless Earbuds', 'ACC-WEB-01', 8000, 15000, 3, 5, 1);
SET @p_earbuds = LAST_INSERT_ID();

INSERT INTO products (tenant_id, category_id, branch_id, name, sku, buying_price, selling_price, quantity, min_stock_level, is_active)
VALUES (@tenant_id, @cat_laptops, @main_branch_id, 'HP Probook 450', 'LAP-HP450', 320000, 385000, 6, 3, 1);
SET @p_hp = LAST_INSERT_ID();

INSERT INTO products (tenant_id, category_id, branch_id, name, sku, buying_price, selling_price, quantity, min_stock_level, is_active)
VALUES (@tenant_id, @cat_accessories, @main_branch_id, 'Phone Case (Universal)', 'ACC-CASE-U', 500, 1500, 0, 10, 1);
SET @p_case = LAST_INSERT_ID();

INSERT INTO stock_logs (tenant_id, product_id, branch_id, user_id, change_qty, reason, note) VALUES
(@tenant_id, @p_tecno, @main_branch_id, @owner_id, 25, 'initial', 'Opening stock'),
(@tenant_id, @p_infinix, @main_branch_id, @owner_id, 14, 'initial', 'Opening stock'),
(@tenant_id, @p_charger, @main_branch_id, @owner_id, 60, 'initial', 'Opening stock'),
(@tenant_id, @p_earbuds, @main_branch_id, @owner_id, 3, 'initial', 'Opening stock'),
(@tenant_id, @p_hp, @main_branch_id, @owner_id, 6, 'initial', 'Opening stock');

INSERT INTO customers (tenant_id, name, phone, email, credit_limit, outstanding_debt)
VALUES (@tenant_id, 'Walk-in Customer', NULL, NULL, 0, 0);

INSERT INTO customers (tenant_id, name, phone, email, credit_limit, outstanding_debt)
VALUES (@tenant_id, 'Ibrahim Sule', '08033333333', 'ibrahim@example.com', 50000, 12000);
SET @customer2_id = LAST_INSERT_ID();

INSERT INTO customers (tenant_id, name, phone, email, credit_limit, outstanding_debt)
VALUES (@tenant_id, 'Grace Okafor', '08044444444', 'grace@example.com', 30000, 0);

INSERT INTO expense_categories (tenant_id, name) VALUES (@tenant_id, 'Rent');
SET @cat_rent = LAST_INSERT_ID();
INSERT INTO expense_categories (tenant_id, name) VALUES (@tenant_id, 'Transport');
SET @cat_transport = LAST_INSERT_ID();
INSERT INTO expense_categories (tenant_id, name) VALUES (@tenant_id, 'Utilities');

INSERT INTO expenses (tenant_id, branch_id, category_id, user_id, title, amount, expense_date)
VALUES (@tenant_id, @main_branch_id, @cat_rent, @owner_id, 'August Shop Rent', 45000, CURDATE());

INSERT INTO expenses (tenant_id, branch_id, category_id, user_id, title, amount, expense_date)
VALUES (@tenant_id, @main_branch_id, @cat_transport, @owner_id, 'Fuel for delivery', 5000, CURDATE());

-- Sample sale #1: Tecno Spark 20, cash, walk-in customer
INSERT INTO sales (tenant_id, branch_id, customer_id, user_id, receipt_no, subtotal, discount, total, amount_paid, balance_due, payment_method, sale_type, status)
VALUES (@tenant_id, @main_branch_id, NULL, @staff_id, 'RCT-0001', 121500, 1500, 120000, 120000, 0, 'cash', 'in_store', 'completed');
SET @sale1_id = LAST_INSERT_ID();

INSERT INTO sale_items (tenant_id, sale_id, product_id, quantity, unit_cost, unit_price, discount, line_total)
VALUES (@tenant_id, @sale1_id, @p_tecno, 1, 95000, 120000, 1500, 120000);

INSERT INTO sale_payments (tenant_id, sale_id, method, amount) VALUES (@tenant_id, @sale1_id, 'cash', 120000);

UPDATE products SET quantity = quantity - 1 WHERE id = @p_tecno;

-- Sample sale #2: charger + earbuds, partial credit sale for Ibrahim
INSERT INTO sales (tenant_id, branch_id, customer_id, user_id, receipt_no, subtotal, discount, total, amount_paid, balance_due, payment_method, sale_type, status)
VALUES (@tenant_id, @main_branch_id, @customer2_id, @manager_id, 'RCT-0002', 21000, 0, 21000, 9000, 12000, 'credit', 'in_store', 'completed');
SET @sale2_id = LAST_INSERT_ID();

INSERT INTO sale_items (tenant_id, sale_id, product_id, quantity, unit_cost, unit_price, discount, line_total)
VALUES (@tenant_id, @sale2_id, @p_charger, 1, 3500, 6000, 0, 6000);

INSERT INTO sale_items (tenant_id, sale_id, product_id, quantity, unit_cost, unit_price, discount, line_total)
VALUES (@tenant_id, @sale2_id, @p_earbuds, 1, 8000, 15000, 0, 15000);

INSERT INTO sale_payments (tenant_id, sale_id, method, amount) VALUES (@tenant_id, @sale2_id, 'transfer', 9000);

UPDATE products SET quantity = quantity - 1 WHERE id = @p_charger;
UPDATE products SET quantity = quantity - 1 WHERE id = @p_earbuds;

INSERT INTO activity_logs (tenant_id, user_id, action, description)
VALUES (@tenant_id, @staff_id, 'sale.create', 'Created sale RCT-0001');

INSERT INTO activity_logs (tenant_id, user_id, action, description)
VALUES (@tenant_id, @manager_id, 'sale.create', 'Created sale RCT-0002 (credit)');

-- Default storefront settings — NovaTrend theme, tech store type
INSERT INTO store_settings (tenant_id, theme, store_type, content)
VALUES (@tenant_id, 'novatrend', 'tech', JSON_OBJECT(
    'eyebrow', 'TRENDING NOW',
    'hero_heading', 'Discover Gadgets You''ll Love',
    'hero_subheading', 'Shop the latest phones, laptops, and accessories at AJ Tech Gadgets.',
    'order_channel', 'email',
    'notification_email', 'owner@ajtech.com'
));