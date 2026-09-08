<?php
namespace App\Core;

/**
 * Minimal, dependency-free JWT (HS256) implementation.
 * Handles both access and refresh tokens.
 */
class JWT
{
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode(array $payload, ?string $secret = null, int $ttlSeconds = 3600): string
    {
        $secret = $secret ?? Env::get('JWT_SECRET', 'change-me');
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $now = time();
        $payload['iat'] = $now;
        $payload['exp'] = $now + $ttlSeconds;

        $segments = [];
        $segments[] = self::base64UrlEncode(json_encode($header));
        $segments[] = self::base64UrlEncode(json_encode($payload));
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Returns decoded payload array on success, or null on failure (invalid sig / expired / malformed).
     */
    public static function decode(string $token, ?string $secret = null): ?array
    {
        $secret = $secret ?? Env::get('JWT_SECRET', 'change-me');
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;

        [$headerB64, $payloadB64, $sigB64] = $parts;
        $signingInput = "$headerB64.$payloadB64";
        $expectedSig = self::base64UrlEncode(hash_hmac('sha256', $signingInput, $secret, true));

        if (!hash_equals($expectedSig, $sigB64)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!is_array($payload)) return null;

        if (isset($payload['exp']) && time() >= $payload['exp']) {
            return null; // expired
        }

        return $payload;
    }
}
