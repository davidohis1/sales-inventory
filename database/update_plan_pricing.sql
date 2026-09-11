-- =====================================================================
-- One-off: set your 3 plans' monthly prices to ₦1,999 / ₦3,999 / ₦5,999.
--
-- Pricing is stored in the `plans` table (managed from Platform Admin →
-- Plans) and the landing page's pricing section pulls it live from
-- /api/plans — so this is the single place to change it; you don't need
-- to touch any landing-page code, and it'll stay consistent with /plans
-- and checkout too.
--
-- This updates plans in order of `sort_order` (then id) ascending, i.e.
-- cheapest tier first, so plan #1 → ₦1,999, plan #2 → ₦3,999, plan #3 →
-- ₦5,999. If you have more or fewer than 3 active plans, only the first
-- three (by that ordering) are touched — adjust the CASE below if your
-- plan order should be different.
-- Safe to run on an existing database.
-- =====================================================================
SET NAMES utf8mb4;

UPDATE plans p
JOIN (
    SELECT id, ROW_NUMBER() OVER (ORDER BY sort_order ASC, id ASC) AS rn
    FROM plans
) ranked ON ranked.id = p.id
SET p.price_monthly = CASE ranked.rn
    WHEN 1 THEN 1999.00
    WHEN 2 THEN 3999.00
    WHEN 3 THEN 5999.00
    ELSE p.price_monthly
END;
