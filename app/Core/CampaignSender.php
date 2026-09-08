<?php
namespace App\Core;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Tenant;

/**
 * Sends one batch of pending recipients for a campaign via Mailer, updating
 * each recipient's status as it goes. Kept deliberately small (default 30
 * per call) so a single web request never times out — the portal's "Send"
 * button calls this once immediately for instant feedback on small lists,
 * and scripts/process_campaigns.php calls it repeatedly for anything larger
 * or scheduled for later.
 */
class CampaignSender
{
    public static function sendBatch(int $campaignId, int $limit = 30): array
    {
        $recipients = CampaignRecipient::nextPending($campaignId, $limit);
        if (empty($recipients)) {
            return Campaign::refreshCounts($campaignId);
        }

        $campaign = self::findCampaign($campaignId);
        $tenant = $campaign ? Tenant::findById((int) $campaign['tenant_id']) : null;
        $fromName = $tenant['business_name'] ?? Env::get('MAIL_FROM_NAME', 'Bizflow');
        $body = $campaign['body_html'] ?? '';

        foreach ($recipients as $r) {
            $html = $body . self::unsubscribeFooter((int) $r['customer_id'], (int) $campaign['tenant_id']);
            try {
                $ok = Mailer::send($r['email'], $campaign['subject'], $html, $fromName);
                $ok ? CampaignRecipient::markSent((int) $r['id']) : CampaignRecipient::markFailed((int) $r['id'], 'Send returned false');
            } catch (\Throwable $e) {
                CampaignRecipient::markFailed((int) $r['id'], $e->getMessage());
            }
        }

        return Campaign::refreshCounts($campaignId);
    }

    private static function findCampaign(int $id): ?array
    {
        $stmt = \App\Core\Database::connect()->prepare('SELECT * FROM campaigns WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function unsubscribeToken(int $customerId, int $tenantId): string
    {
        $secret = Env::get('JWT_SECRET', 'change-me');
        return hash_hmac('sha256', "$tenantId:$customerId", $secret);
    }

    public static function verifyUnsubscribeToken(int $customerId, int $tenantId, string $token): bool
    {
        return hash_equals(self::unsubscribeToken($customerId, $tenantId), $token);
    }

    private static function unsubscribeFooter(int $customerId, int $tenantId): string
    {
        if ($customerId <= 0) { return ''; }
        $token = self::unsubscribeToken($customerId, $tenantId);
        $url = rtrim(Env::get('APP_URL', ''), '/') . "/unsubscribe?t={$tenantId}&c={$customerId}&token={$token}";
        return "<p style=\"margin-top:28px; padding-top:14px; border-top:1px solid #eee; font-size:11px; color:#999;\">"
            . "You're receiving this because you're a customer of this business. "
            . "<a href=\"{$url}\" style=\"color:#999;\">Unsubscribe from marketing emails</a></p>";
    }
}
