<?php
namespace App\Core;

/**
 * Minimal email sender using PHP's built-in mail(). No external dependency,
 * so it works out of the box — but most local dev environments (XAMPP,
 * plain `php -S`) have no real mail server configured, so:
 *   - if MAIL_LOG_ONLY=true in .env, or
 *   - if mail() fails / is unavailable,
 * the message is written to storage/mail_log.txt instead of being silently
 * lost, so you can always see exactly what would have been sent.
 *
 * Email sending is always best-effort: a failure here must never break the
 * sale/order it's attached to, so callers should wrap send() in try/catch
 * (or rely on it never throwing — it doesn't; it just returns bool).
 */
class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        if ($to === '' || !str_contains($to, '@')) {
            return false;
        }

        $fromAddress = Env::get('MAIL_FROM_ADDRESS', 'no-reply@example.com');
        $fromName = Env::get('MAIL_FROM_NAME', 'Sales & Inventory System');
        $logOnly = Env::get('MAIL_LOG_ONLY', 'true') === 'true';

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromAddress}>\r\n";

        $sent = false;
        if (!$logOnly && function_exists('mail')) {
            $sent = @mail($to, $subject, $htmlBody, $headers);
        }

        if (!$sent) {
            self::logToFile($to, $subject, $htmlBody);
        }

        return true; // caller doesn't need to care whether it was a real send or a logged fallback
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
