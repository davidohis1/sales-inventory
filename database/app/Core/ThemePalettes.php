<?php
namespace App\Core;

/**
 * A "UI Template" (aurora, wink, luxora, ...) is the page LAYOUT. A "Color
 * Palette" is just a couple of CSS custom-property overrides applied on
 * <body> — same template, different colors. Each template already defines
 * its 2 core color variables in its own CSS file; here we just supply
 * alternate hex values for those SAME variable names per template.
 */
class ThemePalettes
{
    private const CATALOG = [
        'luxora' => [
            'vars' => ['--lx-ink', '--lx-accent'],
            'palettes' => [
                'signature' => ['name' => 'Signature',  'values' => null],
                'rose'      => ['name' => 'Rose',       'values' => ['#3b1f2b', '#b3466c']],
                'sage'      => ['name' => 'Sage',        'values' => ['#1f2b23', '#5c8a6d']],
                'charcoal'  => ['name' => 'Charcoal',    'values' => ['#17181a', '#6b7280']],
            ],
        ],
        'wink' => [
            'vars' => ['--wk-green', '--wk-orange'],
            'palettes' => [
                'signature' => ['name' => 'Signature', 'values' => null],
                'berry'     => ['name' => 'Berry',     'values' => ['#3a1530', '#e0457a']],
                'ocean'     => ['name' => 'Ocean',      'values' => ['#0d2a3a', '#2196c9']],
                'amber'     => ['name' => 'Amber',      'values' => ['#2a2110', '#d9a441']],
            ],
        ],
        'novatrend' => [
            'vars' => ['--nv-black', '--nv-orange'],
            'palettes' => [
                'signature' => ['name' => 'Signature', 'values' => null],
                'cobalt'    => ['name' => 'Cobalt',    'values' => ['#16181d', '#2563eb']],
                'emerald'   => ['name' => 'Emerald',    'values' => ['#16181d', '#17a672']],
                'magenta'   => ['name' => 'Magenta',    'values' => ['#16181d', '#d6357a']],
            ],
        ],
        'aurora' => [
            'vars' => ['--ar-blue', '--ar-blue-dark'],
            'palettes' => [
                'signature' => ['name' => 'Signature', 'values' => null],
                'teal'      => ['name' => 'Teal',      'values' => ['#0d6d5c', '#084c40']],
                'plum'      => ['name' => 'Plum',       'values' => ['#6b21a8', '#4c1d7a']],
                'crimson'   => ['name' => 'Crimson',    'values' => ['#b91c1c', '#7f1414']],
            ],
        ],
        'marketly' => [
            'vars' => ['--mk-purple', '--mk-purple-dark'],
            'palettes' => [
                'signature' => ['name' => 'Signature', 'values' => null],
                'ocean'     => ['name' => 'Ocean',     'values' => ['#1a3fa0', '#12296e']],
                'emerald'   => ['name' => 'Emerald',    'values' => ['#0d6d5c', '#084c40']],
                'sunset'    => ['name' => 'Sunset',     'values' => ['#ea580c', '#9a3412']],
            ],
        ],
        'verdant' => [
            'vars' => ['--vd-teal', '--vd-teal-dark'],
            'palettes' => [
                'signature' => ['name' => 'Signature', 'values' => null],
                'blush'     => ['name' => 'Blush',     'values' => ['#a91f5c', '#6e1240']],
                'ocean'     => ['name' => 'Ocean',      'values' => ['#1a3fa0', '#12296e']],
                'plum'      => ['name' => 'Plum',       'values' => ['#5b21b6', '#3b0f80']],
            ],
        ],
        'blossom' => [
            'vars' => ['--bl-pink', '--bl-pink-dark'],
            'palettes' => [
                'signature' => ['name' => 'Signature', 'values' => null],
                'lavender'  => ['name' => 'Lavender',  'values' => ['#6c5ce7', '#4a3bb0']],
                'sunset'    => ['name' => 'Sunset',     'values' => ['#ea580c', '#9a3412']],
                'teal'      => ['name' => 'Teal',       'values' => ['#0d6d5c', '#084c40']],
            ],
        ],
        'amara' => [
            'vars' => ['--am-rust', '--am-rust-dark'],
            'palettes' => [
                'signature' => ['name' => 'Signature', 'values' => null],
                'forest'    => ['name' => 'Forest',    'values' => ['#2f5233', '#1f3822']],
                'navy'      => ['name' => 'Navy',       'values' => ['#1e3a5f', '#142944']],
                'plum'      => ['name' => 'Plum',       'values' => ['#5b2c6f', '#3d1d4a']],
            ],
        ],
    ];

    /** For the admin's Theme tab: [key => ['name'=>.., 'swatch'=>'#hex-used-for-the-preview-dot']] */
    public static function forTemplate(string $themeKey): array
    {
        $entry = self::CATALOG[$themeKey] ?? null;
        if (!$entry) return [];
        $out = [];
        foreach ($entry['palettes'] as $key => $p) {
            $out[$key] = ['name' => $p['name'], 'swatch' => $p['values'][1] ?? $p['values'][0] ?? '#999'];
        }
        return $out;
    }

    /** Returns a ready-to-echo `style="--var:#hex; --var2:#hex;"` attribute, or '' for the default/unknown palette. */
    public static function styleAttr(string $themeKey, string $paletteKey): string
    {
        $entry = self::CATALOG[$themeKey] ?? null;
        if (!$entry) return '';
        $palette = $entry['palettes'][$paletteKey] ?? null;
        if (!$palette || !$palette['values']) return '';

        $pairs = [];
        foreach ($entry['vars'] as $i => $varName) {
            if (isset($palette['values'][$i])) {
                $pairs[] = $varName . ':' . $palette['values'][$i];
            }
        }
        return $pairs ? ' style="' . htmlspecialchars(implode('; ', $pairs)) . '"' : '';
    }
}
