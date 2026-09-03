-- =====================================================================
-- Migration v6 — Header image bank (platform-admin uploads pre-made
-- header/banner photos per store category; tenants pick one for their
-- storefront hero instead of/alongside uploading their own).
-- =====================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS header_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    store_type VARCHAR(32) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    label VARCHAR(120) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_header_images_type (store_type)
) ENGINE=InnoDB;
