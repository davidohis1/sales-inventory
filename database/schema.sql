-- =====================================================================
-- Sales, Inventory & Business Management System - Multi-Tenant Schema
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- TENANTS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(64) NOT NULL UNIQUE,
    business_name VARCHAR(191) NOT NULL,
    owner_email VARCHAR(191) NULL,
    owner_phone VARCHAR(40) NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
    logo_path VARCHAR(255) NULL,
    ai_api_key VARCHAR(255) NULL COMMENT 'Optional per-tenant Gemini API key override',
    plan_id INT UNSIGNED NULL COMMENT 'NULL while on trial / after expiry with no active plan',
    subscription_status ENUM('trial','active','expired') NOT NULL DEFAULT 'trial',
    trial_ends_at DATETIME NULL,
    subscription_ends_at DATETIME NULL,
    last_reminder_sent_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PLANS + PLAN FEATURES (subscription tiers, feature gating)
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

CREATE TABLE IF NOT EXISTS plan_features (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id INT UNSIGNED NOT NULL,
    feature_key VARCHAR(64) NOT NULL,
    feature_label VARCHAR(120) NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE,
    UNIQUE KEY uq_plan_feature (plan_id, feature_key)
) ENGINE=InnoDB;

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

CREATE TABLE IF NOT EXISTS platform_admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS withdrawals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    source ENUM('store','digital_product') NOT NULL DEFAULT 'store',
    amount DECIMAL(14,2) NOT NULL,
    fee_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    fee_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    net_amount DECIMAL(14,2) NOT NULL,
    bank_name VARCHAR(120) NULL,
    account_name VARCHAR(120) NULL,
    account_number VARCHAR(40) NULL,
    status ENUM('requested','processing','paid','rejected') NOT NULL DEFAULT 'requested',
    admin_notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_withdrawals_tenant (tenant_id, source)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS header_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_type VARCHAR(32) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    label VARCHAR(120) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_header_images_type (store_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS digital_products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    name VARCHAR(191) NOT NULL,
    price DECIMAL(14,2) NOT NULL,
    compare_price DECIMAL(14,2) NULL,
    category VARCHAR(100) NULL,
    description TEXT NULL COMMENT 'rich-text HTML from the admin editor',
    video_url VARCHAR(255) NULL,
    images JSON NULL COMMENT 'array of /uploads relative paths, in display order',
    file_path VARCHAR(255) NULL COMMENT 'the downloadable deliverable buyers receive',
    file_name VARCHAR(191) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    sales_count INT UNSIGNED NOT NULL DEFAULT 0,
    views_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_dp_tenant (tenant_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS digital_product_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    tenant_id INT UNSIGNED NOT NULL,
    buyer_name VARCHAR(150) NOT NULL,
    buyer_email VARCHAR(191) NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    tx_ref VARCHAR(100) NOT NULL UNIQUE,
    flw_transaction_id VARCHAR(100) NULL,
    status ENUM('pending','successful','failed') NOT NULL DEFAULT 'pending',
    download_token VARCHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES digital_products(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_dpo_tenant (tenant_id),
    INDEX idx_dpo_product (product_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- BRANCHES (Phase 2 - Multi-Branch, scaffolded now)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS branches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NULL,
    is_main TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_branches_tenant (tenant_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- USERS / STAFF (Owner, Manager, Sales Staff)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('owner','manager','staff') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_tenant_email (tenant_id, email),
    INDEX idx_users_tenant (tenant_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS refresh_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATEGORIES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_categories_tenant (tenant_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PRODUCTS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    branch_id INT UNSIGNED NULL COMMENT 'NULL = shared across branches',
    name VARCHAR(191) NOT NULL,
    sku VARCHAR(80) NOT NULL,
    description TEXT NULL,
    buying_price DECIMAL(14,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(14,2) NOT NULL DEFAULT 0,
    quantity INT NOT NULL DEFAULT 0,
    min_stock_level INT NOT NULL DEFAULT 5,
    is_on_store TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Phase 2: visible on public storefront',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_tenant_sku (tenant_id, sku),
    INDEX idx_products_tenant (tenant_id),
    INDEX idx_products_name (name)
) ENGINE=InnoDB;

-- Product images (required before a product can be toggled onto the store)
CREATE TABLE IF NOT EXISTS product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_pimg_product (product_id)
) ENGINE=InnoDB;

-- Stock adjustment / history log
CREATE TABLE IF NOT EXISTS stock_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    change_qty INT NOT NULL COMMENT 'positive = stock in, negative = stock out',
    reason ENUM('restock','sale','return','adjustment','transfer_in','transfer_out','initial') NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_stocklog_tenant (tenant_id),
    INDEX idx_stocklog_product (product_id)
) ENGINE=InnoDB;

-- Stock transfers between branches (Phase 2)
CREATE TABLE IF NOT EXISTS stock_transfers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    from_branch_id INT UNSIGNED NOT NULL,
    to_branch_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    user_id INT UNSIGNED NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CUSTOMERS & DEBT MANAGEMENT
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(191) NULL,
    address VARCHAR(255) NULL,
    credit_limit DECIMAL(14,2) NOT NULL DEFAULT 0,
    outstanding_debt DECIMAL(14,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_customers_tenant (tenant_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customer_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    sale_id INT UNSIGNED NULL,
    amount DECIMAL(14,2) NOT NULL,
    method ENUM('cash','transfer','pos') NOT NULL DEFAULT 'cash',
    note VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_custpay_tenant (tenant_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SALES / POS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NULL,
    customer_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL COMMENT 'staff who made the sale',
    receipt_no VARCHAR(40) NOT NULL,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    discount DECIMAL(14,2) NOT NULL DEFAULT 0,
    total DECIMAL(14,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0,
    balance_due DECIMAL(14,2) NOT NULL DEFAULT 0,
    payment_method ENUM('cash','transfer','pos','split','credit') NOT NULL DEFAULT 'cash',
    sale_type ENUM('in_store','online') NOT NULL DEFAULT 'in_store',
    status ENUM('completed','refunded','partial_refund','cancelled') NOT NULL DEFAULT 'completed',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_tenant_receipt (tenant_id, receipt_no),
    INDEX idx_sales_tenant (tenant_id),
    INDEX idx_sales_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0 COMMENT 'buying price snapshot',
    unit_price DECIMAL(14,2) NOT NULL DEFAULT 0 COMMENT 'selling price snapshot',
    discount DECIMAL(14,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(14,2) NOT NULL DEFAULT 0,
    returned_qty INT NOT NULL DEFAULT 0,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_saleitems_tenant (tenant_id),
    INDEX idx_saleitems_sale (sale_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sale_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    sale_id INT UNSIGNED NOT NULL,
    method ENUM('cash','transfer','pos') NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Supports split payments per sale';

CREATE TABLE IF NOT EXISTS sale_returns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    sale_id INT UNSIGNED NOT NULL,
    sale_item_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    reason VARCHAR(255) NULL,
    refund_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_item_id) REFERENCES sale_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EXPENSES
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS expense_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NULL,
    category_id INT UNSIGNED NULL,
    user_id INT UNSIGNED NULL,
    title VARCHAR(191) NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    note VARCHAR(255) NULL,
    expense_date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE SET NULL,
    INDEX idx_expenses_tenant (tenant_id),
    INDEX idx_expenses_date (expense_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ONLINE ORDERS (Phase 2 - Online Store)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS online_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    order_no VARCHAR(40) NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    customer_phone VARCHAR(30) NULL,
    customer_email VARCHAR(191) NULL,
    delivery_address VARCHAR(255) NULL,
    subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
    total DECIMAL(14,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(14,2) NOT NULL DEFAULT 0,
    status ENUM('ordered','accepted','on_delivery','delivered','cancelled') NOT NULL DEFAULT 'ordered',
    sale_id INT UNSIGNED NULL COMMENT 'linked once converted to a sale on acceptance',
    customer_marked_paid TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'customer clicked "I Have Paid" on a bank-transfer checkout',
    customer_marked_paid_at DATETIME NULL,
    flw_tx_ref VARCHAR(100) NULL,
    flw_transaction_id VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_tenant_order (tenant_id, order_no)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS online_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(14,2) NOT NULL,
    line_total DECIMAL(14,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES online_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- AI INSIGHTS CACHE (Phase 2)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ai_insights (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    summary TEXT NOT NULL,
    raw_context JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_ai_tenant (tenant_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ACTIVITY LOG (audit trail - who did what)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL COMMENT 'e.g. sale.create, product.edit, sale.discount, sale.refund',
    description VARCHAR(255) NULL,
    meta JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_activity_tenant (tenant_id),
    INDEX idx_activity_created (created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- STORE SETTINGS (added in v2) — theme, store type, and editable text
-- content for the public storefront, per tenant.
-- =====================================================================
CREATE TABLE IF NOT EXISTS store_settings (
    tenant_id INT UNSIGNED PRIMARY KEY,
    theme VARCHAR(30) NOT NULL DEFAULT 'aurora' COMMENT 'aurora|wink|luxora|marketly|novatrend',
    store_type VARCHAR(30) NOT NULL DEFAULT 'general' COMMENT 'fashion|tech|beauty|grocery|general — drives stock imagery',
    content JSON NULL COMMENT 'editable header/hero/banner/footer text, keyed per theme',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- DEFAULT PLANS (added in v4) — Basic / Advanced / Premium + per-plan
-- feature gates. feature_key matches the SPA route paths in admin.js.
-- =====================================================================
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
