-- =====================================================================
-- Migration v8 — Store theming: seed a bank of ready-to-use header/hero
-- images per store category (previously empty, so tenants had nothing
-- to pick from besides a now-dead stock-photo redirector), and extend
-- store_type to cover accessories & automotive storefronts.
-- =====================================================================
SET NAMES utf8mb4;

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
('general', 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1400&h=1000&fit=crop&auto=format&q=80', 'Marketplace', 5);
