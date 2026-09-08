-- =====================================================================
-- Migration v10 — Email Campaigns
--   - customers.unsubscribed: lets a customer opt out of marketing email
--     (order/receipt emails are unaffected — this only governs campaigns).
--   - campaigns: one row per campaign a tenant composes.
--   - campaign_recipients: one row per customer targeted by a campaign,
--     so sending can happen in safe batches and progress/results are
--     trackable ("1,204 sent, 3 failed") instead of firing-and-forgetting.
--   - plan_features: gates the feature to Advanced + Premium (matches the
--     'reports'/'store' pattern already used for other paid features).
-- Safe to run on an existing database.
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE customers
    ADD COLUMN unsubscribed TINYINT(1) NOT NULL DEFAULT 0 AFTER email;

CREATE TABLE IF NOT EXISTS campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NULL,
    subject VARCHAR(200) NOT NULL,
    body_html TEXT NOT NULL,
    audience VARCHAR(30) NOT NULL DEFAULT 'all', -- all | debtors | recent | manual
    audience_customer_ids TEXT NULL,             -- JSON array, only used when audience = 'manual'
    status VARCHAR(20) NOT NULL DEFAULT 'draft',  -- draft | scheduled | sending | sent | failed
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
    status VARCHAR(20) NOT NULL DEFAULT 'pending', -- pending | sent | failed
    error VARCHAR(255) NULL,
    sent_at DATETIME NULL,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_recipients_campaign_status (campaign_id, status)
) ENGINE=InnoDB;

INSERT IGNORE INTO plan_features (plan_id, feature_key, feature_label, enabled) VALUES
    (1, 'campaigns', 'Email Campaigns', 0),
    (2, 'campaigns', 'Email Campaigns', 1),
    (3, 'campaigns', 'Email Campaigns', 1);
