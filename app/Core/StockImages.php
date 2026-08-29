<?php
namespace App\Core;

/**
 * Curated real stock photography for storefront decoration (hero banners,
 * category tiles, lifestyle shots) — NOT product photos, which always come
 * from the admin's own uploads. Backed by Unsplash Source, keyed by a
 * fixed signature so the same "slot" always gets the same image instead of
 * a random one on every page load.
 */
class StockImages
{
    private const KEYWORDS = [
        'fashion' => ['fashion,model', 'clothing,rack', 'fashion,street-style', 'boutique,clothing', 'sneakers', 'handbag', 'sunglasses,fashion', 'denim,jeans'],
        'tech'    => ['technology,gadget', 'laptop', 'smartphone', 'headphones', 'smartwatch', 'camera,gear', 'electronics,store', 'earbuds'],
        'beauty'  => ['cosmetics', 'skincare', 'makeup', 'perfume', 'beauty,product', 'spa', 'lipstick', 'haircare'],
        'grocery' => ['grocery,vegetables', 'fresh,fruit', 'supermarket', 'organic,food', 'bakery', 'farmers-market', 'coffee,beans', 'spices'],
        'general' => ['shopping,retail', 'storefront', 'product,display', 'warehouse,boxes', 'delivery,package', 'shopping,bags', 'marketplace', 'ecommerce'],
    ];

    public static function url(string $storeType, int $slot, int $width = 900, int $height = 700): string
    {
        $type = self::KEYWORDS[$storeType] ?? self::KEYWORDS['general'];
        $keyword = $type[$slot % count($type)];
        // sig= pins a stable image per slot instead of a new random one every request
        return "https://source.unsplash.com/{$width}x{$height}/?{$keyword}&sig=" . ($slot + 1);
    }

    /** A ready-made bank of N slot URLs for a store type, for templates that need several at once. */
    public static function bank(string $storeType, int $count = 8, int $width = 900, int $height = 700): array
    {
        $urls = [];
        for ($i = 0; $i < $count; $i++) {
            $urls[] = self::url($storeType, $i, $width, $height);
        }
        return $urls;
    }
}
