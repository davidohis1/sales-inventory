-- =====================================================================
-- Migration v9 — New pricing (Basic ₦1,500 / Advanced ₦2,500 / Premium
-- ₦3,500) and a longer 7-day free trial, plus three new store categories
-- (furniture, sports, kids) with a curated bank of header images so new
-- tenants in those categories aren't stuck with generic photos.
-- Safe to run on an existing database.
-- =====================================================================
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- New pricing
-- ---------------------------------------------------------------------
UPDATE plans SET price_monthly = 1500.00 WHERE `key` = 'basic';
UPDATE plans SET price_monthly = 2500.00 WHERE `key` = 'advanced';
UPDATE plans SET price_monthly = 3500.00 WHERE `key` = 'premium';

-- ---------------------------------------------------------------------
-- Extend every tenant currently on trial to the new 7-day length,
-- measured from when their trial actually started (created_at) rather
-- than from now, so someone 2 days into their old 3-day trial correctly
-- lands on day 2 of 7 — not a fresh 7 days from today.
-- ---------------------------------------------------------------------
UPDATE tenants SET trial_ends_at = DATE_ADD(created_at, INTERVAL 7 DAY)
WHERE subscription_status = 'trial';

-- ---------------------------------------------------------------------
-- Three new store categories, each with a bank of header images.
-- ---------------------------------------------------------------------
INSERT INTO header_images (store_type, image_path, label, sort_order) VALUES
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
