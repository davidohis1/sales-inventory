-- =====================================================================
-- Migration v5 — Withdrawals (store earnings payouts; also used by the
-- upcoming Digital Products feature). Safe to run on an existing DB.
-- =====================================================================
SET NAMES utf8mb4;

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
