<?php
namespace App\Models;

use App\Core\Database;

class StoreSettings extends BaseModel
{
    protected static function table(): string { return 'store_settings'; }

    public const THEMES = ['aurora', 'wink', 'luxora', 'marketly', 'novatrend'];
    public const STORE_TYPES = ['fashion', 'tech', 'beauty', 'grocery', 'general'];

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
        $bases = [
            'aurora' => [
                'announcement' => 'Free shipping on all orders this week',
                'hero_heading' => 'Shop the Collection',
                'hero_subheading' => 'Curated products, fair prices, fast delivery.',
            ],
            'wink' => [
                'collection_title' => 'Our Collection',
                'hero_heading' => 'Explore The Various Collection',
                'hero_subheading' => "Don't miss out on shopping with us — you won't be let down.",
            ],
            'luxora' => [
                'eyebrow' => 'NEW SEASON',
                'hero_heading' => 'Quality that speaks for you.',
                'hero_subheading' => 'Discover great products made for every moment, every mood, every you.',
                'promo_badge' => 'Up to 40% Off',
            ],
            'marketly' => [
                'announcement' => 'Mega Sale is Live! Get up to 60% off',
                'hero_heading' => 'Everything You Need, All in One Place',
                'hero_subheading' => 'Discover great products from a store you can trust. Best prices, premium quality & unbeatable service.',
            ],
            'novatrend' => [
                'eyebrow' => 'TRENDING NOW',
                'hero_heading' => "Discover Products You'll Love",
                'hero_subheading' => 'Shop the latest trending products curated for your lifestyle.',
            ],
        ];

        // Shared across every theme: branding (logo/banner uploads are set later
        // by the admin, so they start empty here), and checkout/notification
        // settings (order_channel: 'email' | 'whatsapp' | 'bank_transfer').
        $shared = [
            'logo_path' => null,
            'banner_path' => null,
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
