<?php
namespace App\Models;

use App\Core\Database;

class StoreSettings extends BaseModel
{
    protected static function table(): string { return 'store_settings'; }

    public const THEMES = ['aurora', 'wink', 'luxora', 'marketly', 'novatrend', 'verdant', 'blossom', 'amara', 'radiance'];
    public const STORE_TYPES = ['fashion', 'tech', 'beauty', 'grocery', 'accessories', 'automotive', 'furniture', 'sports', 'kids', 'general'];

    public static function get(int $tenantId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM store_settings WHERE tenant_id = ?');
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch();

        if (!$row) {
            $defaults = self::defaultsFor('aurora', 'general');
            self::upsert($tenantId, 'aurora', 'general', $defaults);
            return ['tenant_id' => $tenantId, 'theme' => 'aurora', 'store_type' => 'general', 'content' => $defaults];
        }

        $saved = json_decode($row['content'] ?? '{}', true) ?: [];
        // Merge over defaults so tenants saved before new fields existed (e.g. logo_path,
        // order_channel) still get sane defaults instead of missing keys.
        $row['content'] = array_merge(self::defaultsFor($row['theme'], $row['store_type']), $saved);
        return $row;
    }

    public static function upsert(int $tenantId, string $theme, string $storeType, array $content): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT tenant_id FROM store_settings WHERE tenant_id = ?');
        $stmt->execute([$tenantId]);
        $exists = $stmt->fetch();

        $json = json_encode($content);
        if ($exists) {
            $pdo->prepare('UPDATE store_settings SET theme = ?, store_type = ?, content = ? WHERE tenant_id = ?')
                ->execute([$theme, $storeType, $json, $tenantId]);
        } else {
            $pdo->prepare('INSERT INTO store_settings (tenant_id, theme, store_type, content) VALUES (?,?,?,?)')
                ->execute([$tenantId, $theme, $storeType, $json]);
        }
    }

    /** Sensible default text content per theme, so a brand-new store never looks empty. */
    public static function defaultsFor(string $theme, string $storeType): array
    {
        // These mirror each theme's own hardcoded fallback copy exactly, so a
        // brand-new store's Text Content tab is pre-filled with the real text
        // that's actually showing on the storefront (not a generic placeholder),
        // and the storefront itself displays the same well-written, on-brand
        // copy from day one. Fields that embed the tenant's currency symbol
        // (announcement/topbar1/topbar_text below) are intentionally left out
        // here — each template already builds a currency-correct fallback at
        // render time, and seeding a fixed default would freeze it to "$" for
        // every tenant regardless of their actual currency.
        $bases = [
            'aurora' => [
                'eyebrow' => 'New Arrival',
                'hero_heading' => 'New Collection 2024',
                'hero_subheading' => 'Discover the latest arrivals with innovative features and premium designs.',
                'popular_heading' => 'Popular Products',
                'deal_heading' => "Grab It Before It's Gone!",
                'newsletter_heading' => 'Subscribe To Our Newsletter',
                'newsletter_subheading' => 'Get the latest updates on new arrivals, offers & more.',
            ],
            'wink' => [
                'hero_heading' => 'Shop More, Save More!',
                'hero_subheading' => 'Discover amazing deals on your favorite products.',
                'deal_heading' => "Grab It Before It's Gone!",
                'deal_product_name' => 'Featured Product',
                'deal_category' => 'Trending pick',
                'arrivals_heading' => 'New Arrivals',
                'newsletter_heading' => 'Get Exclusive Offers & Updates',
                'newsletter_subheading' => 'Sign up now and get 10% off on your first order!',
            ],
            'luxora' => [
                'eyebrow' => 'NEW COLLECTION',
                'hero_heading' => 'Elevate Your Everyday Style',
                'hero_subheading' => 'Discover timeless pieces crafted for comfort, designed for elegance, made for you.',
                'find_style_heading' => 'Find Your Perfect Style',
                'promo1_heading' => 'Spring Sale Up to 50% Off',
                'promo2_heading' => 'Fresh Styles Just Landed',
                'bestsellers_heading' => 'Our Most Loved Picks',
                'newsletter_heading' => 'Join Our Style List',
                'newsletter_subheading' => 'Sign up for exclusive offers, new arrivals, and style inspiration.',
            ],
            'marketly' => [
                'eyebrow' => 'AUTUMN LUXURY COLLECTION',
                'hero_heading' => 'Elevate Your Style',
                'hero_subheading' => 'Explore our curated selection of seasonal and trending essentials.',
                'categories_heading' => 'Featured Category Grid',
                'flash_heading' => 'Deals Ending Soon',
                'newsletter_heading' => 'Stay in the loop',
                'newsletter_subheading' => 'Get AI-curated picks and offers straight to your inbox.',
            ],
            'novatrend' => [
                'announcement' => 'Summer Sale Up to 70% Off',
                'eyebrow' => 'TRENDING NOW',
                'hero_heading' => "Discover Products You'll Love",
                'hero_subheading' => 'Shop the latest trending products curated for modern lifestyles.',
                'customer_count' => '50,000+',
                'arrivals_heading' => 'New Arrivals',
                'promo1_tag' => 'Flash Sale',
                'promo1_heading' => 'Up to 70% Off',
                'promo_heading' => 'Summer 2025',
            ],
            'verdant' => [
                'eyebrow' => 'Naturally Radiant',
                'hero_heading' => 'Quality that cares, service that shines.',
                'hero_subheading' => 'Discover the perfect blend of care and quality for a better everyday experience.',
                'trust1_heading' => 'Quality Guaranteed', 'trust1_text' => 'Checked before it ships',
                'trust2_heading' => 'Trusted Service', 'trust2_text' => 'Here whenever you need us',
                'trust3_heading' => 'Fast Delivery', 'trust3_text' => 'Straight to your door',
                'trust4_heading' => 'Easy Returns', 'trust4_text' => 'Hassle-free, every time',
                'products_heading' => 'Featured Products',
                'promo_heading' => 'Get 20% Off Your First Order',
                'promo_subheading' => 'Join our club and unlock exclusive offers and tips.',
            ],
            'blossom' => [
                'eyebrow' => 'Radiate Confidence Every Day',
                'hero_heading' => 'Beauty & Wellness for a Better You',
                'hero_subheading' => 'Discover premium products for a healthier, more radiant everyday routine.',
                'badge_percent' => '100%',
                'badge_text' => 'Original Products',
                'trust1_heading' => '100% Genuine', 'trust1_text' => 'Authentic & trusted',
                'trust2_heading' => 'Carefully Vetted', 'trust2_text' => 'Quality checked',
                'trust3_heading' => 'Fast Delivery', 'trust3_text' => 'Straight to your door',
                'trust4_heading' => 'Easy Returns', 'trust4_text' => 'Hassle-free process',
                'promo_tag' => 'Limited Time Offer',
                'promo_heading' => 'Up to 30% Off',
                'promo_subheading' => 'On top brands, for a limited time only.',
                'products_heading' => 'Best Sellers',
            ],
            'amara' => [
                'topbar3' => 'Worldwide Delivery',
                'eyebrow' => 'New Collection',
                'hero_heading' => 'Elegance',
                'hero_heading_2' => 'Redefined',
                'hero_subheading' => 'Modern silhouettes. Quality materials. Designed for every unforgettable moment.',
                'season_badge' => 'New Season',
                'products_heading' => 'Best Sellers',
                'promo1_heading' => 'Timeless Pieces, Endless Possibilities',
                'promo1_subheading' => 'Elevate your wardrobe with versatile staples.',
                'promo2_heading' => 'Enjoy 15% Off Your First Order',
                'promo2_subheading' => 'Sign up and be the first to know about new arrivals and offers.',
                'quote1' => 'A go-to for elevated essentials. The quality and attention to detail are unmatched.',
                'quote2' => 'Every piece feels so refined. I always get compliments when I wear it.',
                'quote3' => 'Beautiful designs and amazing customer service, every time.',
                'newsletter_heading' => 'Stay in the Know',
                'newsletter_subheading' => 'Subscribe for 15% off your first order and new arrivals.',
            ],
            'radiance' => [
                'eyebrow' => 'New Collection',
                'hero_heading' => 'Glow From Within & Without',
                'hero_subheading' => 'Discover premium products crafted for a healthier, more radiant everyday routine.',
                'badge_text' => 'Loved By Thousands',
                'trust1_heading' => 'Free Delivery', 'trust1_text' => 'On qualifying orders',
                'trust2_heading' => 'Authentic Products', 'trust2_text' => '100% genuine, always',
                'trust3_heading' => 'Easy Returns', 'trust3_text' => 'Hassle-free process',
                'trust4_heading' => '24/7 Support', 'trust4_text' => "We're here to help",
                'categories_heading' => 'Shop by Category',
                'arrivals_heading' => 'New Arrivals',
                'promo_heading' => 'Discover Your Perfect Match',
                'promo_subheading' => 'Curated picks chosen for quality, for a limited time only.',
                'quote1' => 'My skin has never looked this good! I noticed a difference within weeks.',
                'quote2' => 'Finally found products that actually work for me. Will definitely reorder.',
                'quote3' => 'The quality and attention to detail are unmatched. Highly recommend!',
                'newsletter_heading' => 'Get Tips & Exclusive Deals',
                'newsletter_subheading' => 'Join our list for weekly tips, early access and exclusive offers.',
            ],
        ];

        // Shared across every theme: branding (logo/banner uploads are set later
        // by the admin, so they start empty here), and checkout/notification
        // settings (order_channel: 'email' | 'whatsapp' | 'bank_transfer').
        $shared = [
            'logo_path' => null,
            'banner_path' => null,
            'shop_heading' => 'Shop All Products',
            'whatsapp_number' => null,
            'social_facebook' => null,
            'social_instagram' => null,
            'social_twitter' => null,
            'social_tiktok' => null,
            'order_channel' => 'email',
            'notification_email' => null,
            'bank_name' => null,
            'bank_account_name' => null,
            'bank_account_number' => null,
        ];

        return array_merge($shared, $bases[$theme] ?? $bases['aurora']);
    }
}
