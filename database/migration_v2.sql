-- =====================================================================
-- Migration v2 — run this if you already loaded the original schema.sql
-- and don't want to drop your database. Safe to run once.
--   mysql -u root sales_inventory < database/migration_v2.sql
-- =====================================================================
CREATE TABLE IF NOT EXISTS store_settings (
    tenant_id INT UNSIGNED PRIMARY KEY,
    theme VARCHAR(30) NOT NULL DEFAULT 'aurora',
    store_type VARCHAR(30) NOT NULL DEFAULT 'general',
    content JSON NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB;
