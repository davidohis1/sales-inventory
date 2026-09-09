-- =====================================================================
-- Migration v11 — Product Specifications & Variants
--   - products.specifications: JSON array of {label, value} pairs, e.g.
--     [{"label":"RAM","value":"8GB"},{"label":"Storage","value":"128GB"}].
--     Optional — most useful for gadgets/electronics.
--   - products.variants: JSON array of {name, values[]} attribute groups,
--     e.g. [{"name":"Color","values":["Red","Blue"]},{"name":"Size","values":["S","M","L"]}].
--     Optional — most useful for fashion/apparel. Presentation-only in v1:
--     shoppers can see and pick an option, but each combination doesn't
--     carry its own separate stock/price/SKU (that's a bigger feature —
--     see README for the note on this trade-off).
-- Safe to run on an existing database.
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE products
    ADD COLUMN specifications TEXT NULL AFTER description,
    ADD COLUMN variants TEXT NULL AFTER specifications;

-- Records which variant option(s) a customer picked at checkout (e.g. "Color: Red, Size: M"),
-- so the store owner can see it when fulfilling the order. Presentation-only in v1 — see the
-- note above about combinations not carrying separate stock/price.
ALTER TABLE online_order_items
    ADD COLUMN variant_label VARCHAR(255) NULL AFTER quantity;
