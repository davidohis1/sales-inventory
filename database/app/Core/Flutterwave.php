<?php
namespace App\Core;

/**
 * Thin wrapper around Flutterwave's v3 REST API using cURL (the codebase has
 * no Composer packages installed, so no official SDK). Covers exactly what
 * this app needs: creating a "Standard" hosted-checkout payment link, and
 * verifying a transaction after the customer returns from it.
 *
 * Docs: https://developer.flutterwave.com/docs/collecting-payments/standard
 *
 * Configure in .env:
 *   FLW_SECRET_KEY=FLWSECK_TEST-xxxxx
 *   FLW_PUBLIC_KEY=FLWPUBK_TEST-xxxxx
 *   FLW_WEBHOOK_HASH=some-long-random-string   (set the SAME value as the
 *                                                "secret hash" in your
 *                                                Flutterwave dashboard webhook settings)
 */
class Flutterwave
{
    private const BASE_URL = 'https://api.flutterwave.com/v3';

    private static function secretKey(): string
    {
        return (string) Env::get('FLW_SECRET_KEY', '');
    }

    public static function isConfigured(): bool
    {
        return self::secretKey() !== '';
    }

    private static function request(string $method, string $path, array $body = []): array
    {
        $ch = curl_init(self::BASE_URL . $path);
        $headers = [
            'Authorization: Bearer ' . self::secretKey(),
            'Content-Type: application/json',
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 25,
        ]);
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['status' => 'error', 'message' => $err ?: 'Network error contacting Flutterwave'];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : ['status' => 'error', 'message' => 'Invalid response from Flutterwave'];
    }

    /**
     * Creates a hosted payment link. $payload should include tx_ref, amount,
     * currency, redirect_url, customer (email, name, phonenumber), and
     * customizations (title/description/logo).
     * Returns ['status' => 'success', 'data' => ['link' => 'https://checkout.flutterwave.com/...']] on success.
     */
    public static function initializePayment(array $payload): array
    {
        if (!self::isConfigured()) {
            return ['status' => 'error', 'message' => 'Flutterwave is not configured (set FLW_SECRET_KEY in .env)'];
        }
        return self::request('POST', '/payments', $payload);
    }

    /** Verifies a completed transaction by its Flutterwave transaction id. */
    public static function verifyTransaction(string $transactionId): array
    {
        if (!self::isConfigured()) {
            return ['status' => 'error', 'message' => 'Flutterwave is not configured (set FLW_SECRET_KEY in .env)'];
        }
        return self::request('GET', "/transactions/{$transactionId}/verify");
    }

    /** Compares the `verif-hash` header on incoming webhooks against your configured secret hash. */
    public static function verifyWebhookSignature(string $signatureHeader): bool
    {
        $expected = (string) Env::get('FLW_WEBHOOK_HASH', '');
        if ($expected === '') return false;
        return hash_equals($expected, $signatureHeader);
    }
}
