<?php
namespace App\Core;

/**
 * Email sender with three backends, tried in this order based on config:
 *   1. Brevo (formerly Sendinblue) transactional email API — used when
 *      MAIL_PROVIDER=brevo and BREVO_API_KEY is set. This is what powers
 *      real deliverability for campaigns and any bulk sending; Brevo's
 *      free tier is generous enough for most tenants to start on.
 *   2. PHP's built-in mail() — used when MAIL_PROVIDER=php (or Brevo isn't
 *      configured), for environments with a real local mail server.
 *   3. storage/mail_log.txt — the always-on fallback so a message is never
 *      silently lost on a dev machine with no mail server and no Brevo key.
 *
 * Email sending is always best-effort: a failure here must never break the
 * sale/order/campaign it's attached to, so callers should wrap send() in
 * try/catch (or rely on it never throwing — it doesn't; it just returns bool).
 */
class Mailer
{
    /**
     * Send a single email. Returns true once the message has either been
     * accepted by the configured provider or safely logged as a fallback —
     * callers don't need to distinguish between the two.
     */
    public static function send(string $to, string $subject, string $htmlBody, ?string $fromName = null): bool
    {
        if ($to === '' || !str_contains($to, '@')) {
            return false;
        }

        $provider = Env::get('MAIL_PROVIDER', 'log');
        $fromAddress = Env::get('MAIL_FROM_ADDRESS', 'no-reply@example.com');
        $fromName = $fromName ?? Env::get('MAIL_FROM_NAME', 'Bizflow');
        $logOnly = Env::get('MAIL_LOG_ONLY', 'true') === 'true';

        if (!$logOnly && $provider === 'brevo') {
            $result = self::sendViaBrevo($to, $subject, $htmlBody, $fromAddress, $fromName);
            if ($result === true) { return true; }
            // Brevo call failed (bad key, network, rate limit, etc) — fall through to log so nothing is lost.
        } elseif (!$logOnly && $provider === 'php' && function_exists('mail')) {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$fromName} <{$fromAddress}>\r\n";
            if (@mail($to, $subject, $htmlBody, $headers)) { return true; }
        }

        self::logToFile($to, $subject, $htmlBody);
        return true;
    }

    /**
     * Sends via Brevo's REST API (https://api.brevo.com/v3/smtp/email) — no
     * SDK/dependency needed, just a signed HTTP POST. Returns true on a 2xx
     * response, false on anything else (network failure, invalid key, etc),
     * so the caller can fall back to logging.
     */
    private static function sendViaBrevo(string $to, string $subject, string $htmlBody, string $fromAddress, string $fromName): bool
    {
        $apiKey = Env::get('BREVO_API_KEY', '');
        if ($apiKey === '' || !function_exists('curl_init')) {
            return false;
        }

        $payload = json_encode([
            'sender' => ['name' => $fromName, 'email' => $fromAddress],
            'to' => [['email' => $to]],
            'subject' => $subject,
            'htmlContent' => $htmlBody,
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'content-type: application/json',
                'api-key: ' . $apiKey,
            ],
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);

        if ($error) {
            self::logToFile($to, '[Brevo send failed: ' . $error . '] ' . $subject, $htmlBody);
            return false;
        }
        return $status >= 200 && $status < 300;
    }

    private static function logToFile(string $to, string $subject, string $htmlBody): void
    {
        $dir = __DIR__ . '/../../storage';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $file = $dir . '/mail_log.txt';
        $entry = "===== " . date('Y-m-d H:i:s') . " =====\n"
            . "To: $to\nSubject: $subject\n\n"
            . strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody))
            . "\n\n";
        @file_put_contents($file, $entry, FILE_APPEND);
    }
}
