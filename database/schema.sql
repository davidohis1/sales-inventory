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

INSERT INTO header_images (store_type, image_path, label, sort_order) VALUES
('fashion', 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1400&h=1000&fit=crop&auto=format&q=80', 'Model in blazer', 0),
('fashion', 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1400&h=1000&fit=crop&auto=format&q=80', 'Street style', 1),
('fashion', 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1400&h=1000&fit=crop&auto=format&q=80', 'Boutique rack', 2),
('fashion', 'https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?w=1400&h=1000&fit=crop&auto=format&q=80', 'Studio portrait', 3),
('fashion', 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=1400&h=1000&fit=crop&auto=format&q=80', 'Runway look', 4),
('fashion', 'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=1400&h=1000&fit=crop&auto=format&q=80', 'Denim edit', 5),
('tech', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1400&h=1000&fit=crop&auto=format&q=80', 'Smartwatch macro', 0),
('tech', 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=1400&h=1000&fit=crop&auto=format&q=80', 'Wireless earbuds', 1),
('tech', 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=1400&h=1000&fit=crop&auto=format&q=80', 'Laptop workspace', 2),
('tech', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=1400&h=1000&fit=crop&auto=format&q=80', 'Camera gear', 3),
('tech', 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1400&h=1000&fit=crop&auto=format&q=80', 'Smartphone flatlay', 4),
('tech', 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=1400&h=1000&fit=crop&auto=format&q=80', 'Headphones', 5),
('beauty', 'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=1400&h=1000&fit=crop&auto=format&q=80', 'Skincare flatlay', 0),
('beauty', 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?w=1400&h=1000&fit=crop&auto=format&q=80', 'Makeup palette', 1),
('beauty', 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=1400&h=1000&fit=crop&auto=format&q=80', 'Perfume bottle', 2),
('beauty', 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=1400&h=1000&fit=crop&auto=format&q=80', 'Spa still life', 3),
('beauty', 'https://images.unsplash.com/photo-1560750588-73207b1ef5b8?w=1400&h=1000&fit=crop&auto=format&q=80', 'Lipstick macro', 4),
('beauty', 'https://images.unsplash.com/photo-1585232351009-aa87416fca90?w=1400&h=1000&fit=crop&auto=format&q=80', 'Cosmetics set', 5),
('grocery', 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1400&h=1000&fit=crop&auto=format&q=80', 'Fresh vegetables', 0),
('grocery', 'https://images.unsplash.com/photo-1610832958506-aa56368176cf?w=1400&h=1000&fit=crop&auto=format&q=80', 'Fruit basket', 1),
('grocery', 'https://images.unsplash.com/photo-1518843875459-f738682238a6?w=1400&h=1000&fit=crop&auto=format&q=80', 'Bakery shelf', 2),
('grocery', 'https://images.unsplash.com/photo-1506617420156-8e4536971650?w=1400&h=1000&fit=crop&auto=format&q=80', 'Organic produce', 3),
('grocery', 'https://images.unsplash.com/photo-1519996529931-28324d5a630e?w=1400&h=1000&fit=crop&auto=format&q=80', 'Farmers market', 4),
('grocery', 'https://images.unsplash.com/photo-1550989460-0adf9ea622e2?w=1400&h=1000&fit=crop&auto=format&q=80', 'Coffee beans', 5),
('accessories', 'https://images.unsplash.com/photo-1523293182086-7651a899d37f?w=1400&h=1000&fit=crop&auto=format&q=80', 'Handbag close-up', 0),
('accessories', 'https://images.unsplash.com/photo-1547949003-9792a18a2645?w=1400&h=1000&fit=crop&auto=format&q=80', 'Sunglasses flatlay', 1),
('accessories', 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?w=1400&h=1000&fit=crop&auto=format&q=80', 'Watch macro', 2),
('accessories', 'https://images.unsplash.com/photo-1611085583191-a3b181a88401?w=1400&h=1000&fit=crop&auto=format&q=80', 'Jewelry box', 3),
('accessories', 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=1400&h=1000&fit=crop&auto=format&q=80', 'Leather bag', 4),
('accessories', 'https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?w=1400&h=1000&fit=crop&auto=format&q=80', 'Belt & wallet', 5),
('automotive', 'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=1400&h=1000&fit=crop&auto=format&q=80', 'Car exterior', 0),
('automotive', 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=1400&h=1000&fit=crop&auto=format&q=80', 'Dashboard detail', 1),
('automotive', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=1400&h=1000&fit=crop&auto=format&q=80', 'Wheel close-up', 2),
('automotive', 'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?w=1400&h=1000&fit=crop&auto=format&q=80', 'Showroom shot', 3),
('automotive', 'https://images.unsplash.com/photo-1542362567-b07e54358753?w=1400&h=1000&fit=crop&auto=format&q=80', 'Road at dusk', 4),
('automotive', 'https://images.unsplash.com/photo-1571127236794-81c0bbfe1ce3?w=1400&h=1000&fit=crop&auto=format&q=80', 'Engine bay', 5),
('general', 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1400&h=1000&fit=crop&auto=format&q=80', 'Retail display', 0),
('general', 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=1400&h=1000&fit=crop&auto=format&q=80', 'Storefront', 1),
('general', 'https://images.unsplash.com/photo-1556740738-b6a63e27c4df?w=1400&h=1000&fit=crop&auto=format&q=80', 'Product shelf', 2),
('general', 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1400&h=1000&fit=crop&auto=format&q=80', 'Shopping bags', 3),
('general', 'https://images.unsplash.com/photo-1516762689617-e1cffcef479d?w=1400&h=1000&fit=crop&auto=format&q=80', 'Delivery boxes', 4),
('general', 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1400&h=1000&fit=crop&auto=format&q=80', 'Marketplace', 5),

('furniture', 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=1400&h=1000&fit=crop&auto=format&q=80', 'Modern living room sofa', 0),
('furniture', 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=1400&h=1000&fit=crop&auto=format&q=80', 'Bedroom furniture set', 1),
('furniture', 'https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?w=1400&h=1000&fit=crop&auto=format&q=80', 'Styled interior corner', 2),
('furniture', 'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?w=1400&h=1000&fit=crop&auto=format&q=80', 'Dining table setup', 3),
('furniture', 'https://images.unsplash.com/photo-1567016432779-094069958ea5?w=1400&h=1000&fit=crop&auto=format&q=80', 'Accent armchair', 4),
('furniture', 'https://images.unsplash.com/photo-1449247709967-d4461a6a6103?w=1400&h=1000&fit=crop&auto=format&q=80', 'Wooden shelving', 5),
('furniture', 'https://images.unsplash.com/photo-1550254478-ead40cc54513?w=1400&h=1000&fit=crop&auto=format&q=80', 'Minimalist sofa', 6),
('furniture', 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?w=1400&h=1000&fit=crop&auto=format&q=80', 'Home library shelf', 7),

('sports', 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=1400&h=1000&fit=crop&auto=format&q=80', 'Running shoes', 0),
('sports', 'https://images.unsplash.com/photo-1517649763962-0c623066013b?w=1400&h=1000&fit=crop&auto=format&q=80', 'Basketball on court', 1),
('sports', 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=1400&h=1000&fit=crop&auto=format&q=80', 'Gym dumbbells', 2),
('sports', 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=1400&h=1000&fit=crop&auto=format&q=80', 'Runner on track', 3),
('sports', 'https://images.unsplash.com/photo-1571008887538-b36bb32f4571?w=1400&h=1000&fit=crop&auto=format&q=80', 'Weightlifting plates', 4),
('sports', 'https://images.unsplash.com/photo-1517341860889-5a5aa1cef1fc?w=1400&h=1000&fit=crop&auto=format&q=80', 'Soccer ball on grass', 5),
('sports', 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=1400&h=1000&fit=crop&auto=format&q=80', 'Cyclist on road', 6),
('sports', 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=1400&h=1000&fit=crop&auto=format&q=80', 'Tennis racket & ball', 7),

('kids', 'https://images.unsplash.com/photo-1587654780291-39c9404d746b?w=1400&h=1000&fit=crop&auto=format&q=80', 'Colorful kids toys', 0),
('kids', 'https://images.unsplash.com/photo-1558877385-81a1c7e67d72?w=1400&h=1000&fit=crop&auto=format&q=80', 'Baby toys flatlay', 1),
('kids', 'https://images.unsplash.com/photo-1519689680058-324335c77eba?w=1400&h=1000&fit=crop&auto=format&q=80', 'Toddler playing', 2),
('kids', 'https://images.unsplash.com/photo-1522771930-78848d9293e8?w=1400&h=1000&fit=crop&auto=format&q=80', 'Stacked toy blocks', 3),
('kids', 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=1400&h=1000&fit=crop&auto=format&q=80', 'Kids room decor', 4),
('kids', 'https://images.unsplash.com/photo-1490312278390-ab64016e0aa9?w=1400&h=1000&fit=crop&auto=format&q=80', 'Kids clothing rack', 5),
('kids', 'https://images.unsplash.com/photo-1602934585418-f588bea4215d?w=1400&h=1000&fit=crop&auto=format&q=80', 'Child playing outdoors', 6),
('kids', 'https://images.unsplash.com/photo-1618842676088-c4d48a6a7c9d?w=1400&h=1000&fit=crop&auto=format&q=80', 'Baby essentials flatlay', 7);

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
    unsubscribed TINYINT(1) NOT NULL DEFAULT 0,
    address VARCHAR(255) NULL,
    credit_limit DECIMAL(14,2) NOT NULL DEFAULT 0,
    outstanding_debt DECIMAL(14,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_customers_tenant (tenant_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NULL,
    subject VARCHAR(200) NOT NULL,
    body_html TEXT NOT NULL,
    audience VARCHAR(30) NOT NULL DEFAULT 'all',
    audience_customer_ids TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    scheduled_at DATETIME NULL,
    total_recipients INT UNSIGNED NOT NULL DEFAULT 0,
    sent_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_campaigns_tenant (tenant_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS campaign_recipients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NULL,
    email VARCHAR(191) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    error VARCHAR(255) NULL,
    sent_at DATETIME NULL,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_recipients_campaign_status (campaign_id, status)
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
    (1, 'basic',    'Basic',    1500.00, 'Everyday selling essentials for a single-location shop.', 1),
    (2, 'advanced', 'Advanced', 2500.00, 'Adds online selling and business reporting.', 2),
    (3, 'premium',  'Premium',  3500.00, 'Everything, including team, multi-branch and AI insights.', 3);

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

INSERT IGNORE INTO plan_features (plan_id, feature_key, feature_label, enabled) VALUES
    (1, 'campaigns', 'Email Campaigns', 0),
    (2, 'campaigns', 'Email Campaigns', 1),
    (3, 'campaigns', 'Email Campaigns', 1);
