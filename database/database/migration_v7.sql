-- =====================================================================
-- Migration v7 — Digital Products: a free-forever feature (not gated by
-- plan or trial expiry) letting a tenant sell downloadable products
-- (ebooks, courses, files) from their own public page at /{product-slug}.
-- =====================================================================
SET NAMES utf8mb4;

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
