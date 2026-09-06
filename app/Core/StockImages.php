<?php
namespace App\Core;

/**
 * Curated real stock photography for storefront decoration (hero banners,
 * category tiles, lifestyle shots) — NOT product photos, which always come
 * from the admin's own uploads.
 *
 * IMPORTANT: this used to proxy through source.unsplash.com's "random by
 * keyword" redirector. That service was permanently shut down, so every URL
 * it produced now fails to load — this is why images weren't showing up.
 * There's no free, unauthenticated "random photo by keyword" API left
 * standing (LoremFlickr suffered the same fate once Flickr locked down its
 * API). The reliable fix is to link straight to specific, real photos on
 * Unsplash's own CDN (images.unsplash.com/photo-<id>), which is just static
 * file hosting and unaffected by the redirector's shutdown — and to keep a
 * bank of several per category so a slot doesn't look identical everywhere.
 */
class StockImages
{
    private const PHOTOS = [
        'fashion' => [
            '1483985988355-763728e1935b', '1490481651871-ab68de25d43d', '1441986300917-64674bd600d8',
            '1490578474895-699cd4e2cf59', '1445205170230-053b83016050', '1509631179647-0177331693ae',
            '1520975954732-35dd22299614', '1521572163474-6864f9cf17ab', '1515886657613-9f3515b0c78f',
        ],
        'tech' => [
            '1523275335684-37898b6baf30', '1526170375885-4d8ecf77b99f', '1546868871-7041f2a55e12',
            '1505740420928-5e560c06d30e', '1519389950473-47ba0277781c', '1517336714731-489689fd1ca8',
            '1491933382434-500287f9b54b', '1498049794561-7780e7231661', '1484704849700-f032a568e944',
        ],
        'beauty' => [
            '1512496015851-a90fb38ba796', '1571781926291-c477ebfd024b', '1522335789203-aabd1fc54bc9',
            '1556228720-195a672e8a03', '1560750588-73207b1ef5b8', '1585232351009-aa87416fca90',
            '1591360236480-9c6a4cb3a935', '1580870069867-74c57ee1bb07', '1608248543803-ba4f8c70ae0b',
        ],
        'grocery' => [
            '1542838132-92c53300491e', '1610832958506-aa56368176cf', '1518843875459-f738682238a6',
            '1506617420156-8e4536971650', '1519996529931-28324d5a630e', '1550989460-0adf9ea622e2',
            '1580913428735-bd3c269d6a49', '1573246123716-6b1782bfc499', '1571680322279-a226e6a4cc2a',
        ],
        'accessories' => [
            '1523293182086-7651a899d37f', '1547949003-9792a18a2645', '1524592094714-0f0654e20314',
            '1611085583191-a3b181a88401', '1590874103328-eac38a683ce7', '1556228453-efd6c1ff04f6',
            '1585386959984-a4155224a1ad', '1512496015851-a90fb38ba796', '1441984904996-e0b6ba687e04',
        ],
        'automotive' => [
            '1494976388531-d1058494cdd8', '1503376780353-7e6692767b70', '1552519507-da3b142c6e3d',
            '1503736334956-4c8f8e92946d', '1542362567-b07e54358753', '1571127236794-81c0bbfe1ce3',
            '1553440569-bcc63803a83d', '1511919884226-fd3cad34687c', '1568605117036-5fe5e7bab0b7',
        ],
        'general' => [
            '1441986300917-64674bd600d8', '1472851294608-062f824d29cc', '1556740738-b6a63e27c4df',
            '1607082348824-0a96f2a4b9da', '1516762689617-e1cffcef479d', '1489599849927-2ee91cede3ba',
            '1441985429000-7395b4ba9b18', '1441984904996-e0b6ba687e04', '1441986300917-64674bd600d8',
        ],
    ];

    /** Builds a real, stable Unsplash CDN URL for a specific photo id. */
    private static function cdn(string $photoId, int $width, int $height): string
    {
        return "https://images.unsplash.com/photo-{$photoId}?w={$width}&h={$height}&fit=crop&auto=format&q=80";
    }

    public static function url(string $storeType, int $slot, int $width = 900, int $height = 700): string
    {
        $bank = self::PHOTOS[$storeType] ?? self::PHOTOS['general'];
        $photoId = $bank[$slot % count($bank)];
        return self::cdn($photoId, $width, $height);
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
