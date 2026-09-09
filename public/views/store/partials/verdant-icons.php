<?php
/**
 * Verdant theme icon helpers — thin-line SVGs (stroke=currentColor) instead
 * of emoji, so icon color/weight follows the theme instead of the platform's
 * emoji font. Included by verdant.php and theme-header.php (verdant case).
 */

function vd_icon(string $name): string
{
    $icons = [
        'quality' => '<path d="M20 6L9 17l-5-5"/>',
        'chat'    => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        'truck'   => '<path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="1.8"/><circle cx="18.5" cy="18.5" r="1.8"/>',
        'returns' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v5h5"/>',
        'shield'  => '<path d="M12 2l8 4v6c0 5-3.4 8.4-8 10-4.6-1.6-8-5-8-10V6l8-4z"/>',
        'headset' => '<path d="M3 14v-2a9 9 0 0 1 18 0v2"/><path d="M21 14a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2v2z"/><path d="M3 14a2 2 0 0 0 2 2h1v-6H5a2 2 0 0 0-2 2v2z"/>',
        // category icons
        'phone'   => '<rect x="7" y="2" width="10" height="20" rx="2"/><line x1="11" y1="18" x2="13" y2="18"/>',
        'laptop'  => '<rect x="3" y="4" width="18" height="12" rx="1"/><path d="M1 20h22"/>',
        'headphones' => '<path d="M3 13a9 9 0 0 1 18 0"/><rect x="2" y="13" width="5" height="7" rx="1.5"/><rect x="17" y="13" width="5" height="7" rx="1.5"/>',
        'watch'   => '<circle cx="12" cy="12" r="6"/><path d="M12 9v3l2 2"/><path d="M9 2h6v3H9zM9 19h6v3H9z"/>',
        'camera'  => '<path d="M4 8h3l2-2h6l2 2h3v11H4z"/><circle cx="12" cy="13.5" r="3.5"/>',
        'droplet' => '<path d="M12 2s6 7 6 11a6 6 0 0 1-12 0c0-4 6-11 6-11z"/>',
        'jar'     => '<rect x="6" y="8" width="12" height="13" rx="2"/><rect x="8" y="3" width="8" height="5" rx="1"/>',
        'tube'    => '<path d="M9 2h6l1 4-2 2v13a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1V8L8 6z"/>',
        'bottle'  => '<path d="M10 2h4v4l2 2v13a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V8l2-2z"/>',
        'sun'     => '<circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2 12h2M20 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/>',
        'eye'     => '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>',
        'shirt'   => '<path d="M8 3L2 6l2 4 2-1v12h12V9l2 1 2-4-6-3-2 2h-4z"/>',
        'shoe'    => '<path d="M2 18v-4c3-1 4-3 6-3l3 3h5a4 4 0 0 1 4 4v0H2z"/>',
        'bag'     => '<path d="M6 8h12l1 13H5z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'food'    => '<path d="M12 2c1 2 1 3 0 4-2 1-3 3-3 5a5 5 0 0 0 10 0c0-2-1-4-3-5-1-1-1-2 0-4"/>',
        'sofa'    => '<path d="M4 12V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v4"/><rect x="2" y="12" width="20" height="6" rx="1.5"/><path d="M4 18v3M20 18v3"/>',
        'dumbbell' => '<path d="M2 9v6M22 9v6"/><rect x="4" y="7" width="3" height="10" rx="1"/><rect x="17" y="7" width="3" height="10" rx="1"/><path d="M7 12h10"/>',
        'baby'    => '<circle cx="12" cy="7" r="4"/><path d="M5 21c0-4 3-7 7-7s7 3 7 7"/>',
        'box'     => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
    ];
    $d = $icons[$name] ?? $icons['box'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
}

/** Picks a contextually relevant icon for a real category name instead of
 *  cycling through an unrelated fixed emoji list. Falls back to a generic
 *  box icon for anything that doesn't match a known keyword. */
function vd_category_icon(string $categoryName): string
{
    $n = strtolower($categoryName);
    $map = [
        'phone' => 'phone', 'mobile' => 'phone', 'tablet' => 'phone',
        'laptop' => 'laptop', 'computer' => 'laptop', 'pc' => 'laptop', 'elitebook' => 'laptop', 'macbook' => 'laptop',
        'headphone' => 'headphones', 'earphone' => 'headphones', 'earbud' => 'headphones', 'audio' => 'headphones',
        'watch' => 'watch',
        'camera' => 'camera',
        'serum' => 'droplet', 'oil' => 'droplet',
        'moistur' => 'jar', 'cream' => 'jar',
        'cleans' => 'tube',
        'toner' => 'bottle', 'lotion' => 'bottle',
        'sun' => 'sun', 'spf' => 'sun',
        'eye' => 'eye',
        'shirt' => 'shirt', 'cloth' => 'shirt', 'wear' => 'shirt', 'dress' => 'shirt', 'apparel' => 'shirt',
        'shoe' => 'shoe', 'sneaker' => 'shoe', 'footwear' => 'shoe',
        'bag' => 'bag', 'wallet' => 'bag',
        'food' => 'food', 'grocery' => 'food', 'snack' => 'food', 'drink' => 'food',
        'furnit' => 'sofa', 'sofa' => 'sofa', 'chair' => 'sofa', 'table' => 'sofa',
        'sport' => 'dumbbell', 'fitness' => 'dumbbell', 'gym' => 'dumbbell',
        'baby' => 'baby', 'kid' => 'baby', 'toy' => 'baby',
        'accessor' => 'box', 'charger' => 'box', 'cable' => 'box', 'storage' => 'box', 'hdd' => 'box',
    ];
    foreach ($map as $keyword => $icon) {
        if (str_contains($n, $keyword)) return vd_icon($icon);
    }
    return vd_icon('box');
}

/** Short, real (store_type-driven) subtitle for the two-line logo lockup —
 *  not a fabricated per-tenant tagline, just a category label. */
function vd_store_tagline(string $storeType): string
{
    $map = [
        'fashion' => 'Fashion & Style', 'tech' => 'Tech & Gadgets', 'beauty' => 'Natural Skincare',
        'grocery' => 'Fresh & Everyday', 'accessories' => 'Accessories & More', 'automotive' => 'Auto Parts & Care',
        'furniture' => 'Home & Furniture', 'sports' => 'Sports & Fitness', 'kids' => 'Kids & Baby',
    ];
    return $map[$storeType] ?? 'Quality & Care';
}
