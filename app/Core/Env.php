<?php
namespace App\Core;

/**
 * Minimal .env loader — no composer dependency required.
 */
class Env
{
    private static array $vars = [];
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) return;
        if (!file_exists($path)) {
            self::$loaded = true;
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // strip surrounding quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            self::$vars[$key] = $value;
            if (getenv($key) === false) {
                putenv("$key=$value");
            }
        }
        self::$loaded = true;
    }

    public static function get(string $key, $default = null)
    {
        if (isset(self::$vars[$key]) && self::$vars[$key] !== '') return self::$vars[$key];
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        return $default;
    }
}
