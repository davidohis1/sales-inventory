-- =====================================================================
-- Migration v3 — adds "I Have Paid" tracking for bank-transfer online
-- orders. Run this ONCE (MariaDB/older MySQL don't all support
-- "ADD COLUMN IF NOT EXISTS", so this is plain ALTER TABLE).
--   mysql -u root sales_inventory < database/migration_v3.sql
-- =====================================================================
ALTER TABLE online_orders
    ADD COLUMN customer_marked_paid TINYINT(1) NOT NULL DEFAULT 0 AFTER sale_id,
    ADD COLUMN customer_marked_paid_at DATETIME NULL AFTER customer_marked_paid;
