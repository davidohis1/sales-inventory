-- =====================================================================
-- Migration v4 — Subscription plans, trial/expiry, Flutterwave payments,
-- and the platform-admin (super-admin) side of the app.
-- Safe to run on an existing database; only adds new tables/columns.
-- =====================================================================
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PLANS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(32) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    price_monthly DECIMAL(14,2) NOT NULL DEFAULT 0,
    description VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Every gateable module in the portal sidebar is a feature_key row per plan.
-- feature_key values match the SPA route paths in admin.js:
--   pos, products, customers, expenses, orders, store, staff, branches, reports, ai_insights
-- (the dashboard itself is never gated).
CREATE TABLE IF NOT EXISTS plan_features (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id INT UNSIGNED NOT NULL,
    feature_key VARCHAR(64) NOT NULL,
    feature_label VARCHAR(120) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
    UNIQUE KEY uq_plan_feature (plan_id, feature_key)
) ENGINE=InnoDB;

INSERT IGNORE INTO plans (id, `key`, name, price_monthly, description, sort_order) VALUES
    (1, 'basic',    'Basic',    3500.00, 'Everyday selling essentials for a single-location shop.', 1),
    (2, 'advanced', 'Advanced', 5500.00, 'Adds online selling and business reporting.', 2),
    (3, 'premium',  'Premium',  7500.00, 'Everything, including team, multi-branch and AI insights.', 3);

INSERT IGNORE INTO plan_features (plan_id, feature_key, feature_label, enabled) VALUES
    (1, 'pos',        'Sales / POS',             1),
    (1, 'products',   'Products & Inventory',    1),
    (1, 'customers',  'Customers & Debt',        1),
    (1, 'expenses',   'Expenses',                1),
    (1, 'orders',     'Online Orders',           0),
    (1, 'store',      'Online Store',            0),
    (1, 'reports',    'Reports',                 0),
    (1, 'staff',      'Staff Management',        0),
    (1, 'branches',   'Multi-Branch',            0),
    (1, 'ai_insights','AI Insights',             0),

    (2, 'pos',        'Sales / POS',             1),
    (2, 'products',   'Products & Inventory',    1),
    (2, 'customers',  'Customers & Debt',        1),
    (2, 'expenses',   'Expenses',                1),
    (2, 'orders',     'Online Orders',           1),
    (2, 'store',      'Online Store',            1),
    (2, 'reports',    'Reports',                 1),
    (2, 'staff',      'Staff Management',        0),
    (2, 'branches',   'Multi-Branch',            0),
    (2, 'ai_insights','AI Insights',             0),

    (3, 'pos',        'Sales / POS',             1),
    (3, 'products',   'Products & Inventory',    1),
    (3, 'customers',  'Customers & Debt',        1),
    (3, 'expenses',   'Expenses',                1),
    (3, 'orders',     'Online Orders',           1),
    (3, 'store',      'Online Store',            1),
    (3, 'reports',    'Reports',                 1),
    (3, 'staff',      'Staff Management',        1),
    (3, 'branches',   'Multi-Branch',            1),
    (3, 'ai_insights','AI Insights',             1);

-- ---------------------------------------------------------------------
-- TENANTS — trial + subscription tracking
-- ---------------------------------------------------------------------
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'plan_id');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE tenants ADD COLUMN plan_id INT UNSIGNED NULL AFTER currency', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'subscription_status');
SET @sql := IF(@col_exists = 0, "ALTER TABLE tenants ADD COLUMN subscription_status ENUM('trial','active','expired') NOT NULL DEFAULT 'trial' AFTER plan_id", 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'trial_ends_at');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE tenants ADD COLUMN trial_ends_at DATETIME NULL AFTER subscription_status', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'subscription_ends_at');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE tenants ADD COLUMN subscription_ends_at DATETIME NULL AFTER trial_ends_at', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'owner_email');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE tenants ADD COLUMN owner_email VARCHAR(191) NULL AFTER business_name', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'owner_phone');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE tenants ADD COLUMN owner_phone VARCHAR(40) NULL AFTER owner_email', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'last_reminder_sent_at');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE tenants ADD COLUMN last_reminder_sent_at DATETIME NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------
-- PAYMENTS (Flutterwave subscription payments)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
    tx_ref VARCHAR(100) NOT NULL UNIQUE,
    flw_transaction_id VARCHAR(100) NULL,
    status ENUM('pending','successful','failed') NOT NULL DEFAULT 'pending',
    raw_response TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id),
    INDEX idx_payments_tenant (tenant_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PLATFORM ADMINS (super-admin of the whole SaaS, not tenant-scoped)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS platform_admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Online-store order payment/status columns (Flutterwave + fulfillment workflow)
SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'online_orders' AND COLUMN_NAME = 'amount_paid');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE online_orders ADD COLUMN amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER total', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'online_orders' AND COLUMN_NAME = 'flw_tx_ref');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE online_orders ADD COLUMN flw_tx_ref VARCHAR(100) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_exists := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'online_orders' AND COLUMN_NAME = 'flw_transaction_id');
SET @sql := IF(@col_exists = 0, 'ALTER TABLE online_orders ADD COLUMN flw_transaction_id VARCHAR(100) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Widen the order status enum to the 4-stage fulfillment workflow requested:
-- ordered -> accepted -> on_delivery -> delivered  (plus cancelled).
-- Map the old values forward BEFORE changing the enum definition, so no
-- data is silently blanked out by MySQL when the old value no longer exists.
ALTER TABLE online_orders MODIFY COLUMN status ENUM('pending','accepted','fulfilled','cancelled','ordered','on_delivery','delivered') NOT NULL DEFAULT 'pending';
UPDATE online_orders SET status = 'ordered' WHERE status = 'pending';
UPDATE online_orders SET status = 'delivered' WHERE status = 'fulfilled';
ALTER TABLE online_orders MODIFY COLUMN status ENUM('ordered','accepted','on_delivery','delivered','cancelled') NOT NULL DEFAULT 'ordered';

-- Backfill: any tenant created before this migration gets a 3-day trial from now
UPDATE tenants SET trial_ends_at = DATE_ADD(NOW(), INTERVAL 3 DAY), subscription_status = 'trial'
    WHERE trial_ends_at IS NULL AND plan_id IS NULL;
