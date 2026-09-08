<?php
namespace App\Models;

class CampaignRecipient
{
    protected static function db(): \PDO { return \App\Core\Database::connect(); }

    /**
     * Resolves an audience selection into the actual customer rows to target,
     * always excluding customers with no email on file or who've unsubscribed.
     * $audience: all | debtors | recent | manual
     * $manualIds: only used when $audience === 'manual'
     */
    public static function resolveAudience(int $tenantId, string $audience, array $manualIds = []): array
    {
        $base = 'SELECT id, name, email FROM customers WHERE tenant_id = ? AND email IS NOT NULL AND email != "" AND unsubscribed = 0';
        $params = [$tenantId];

        switch ($audience) {
            case 'debtors':
                $base .= ' AND outstanding_debt > 0';
                break;
            case 'recent':
                $base .= ' AND created_at >= (NOW() - INTERVAL 30 DAY)';
                break;
            case 'manual':
                $manualIds = array_values(array_filter(array_map('intval', $manualIds)));
                if (empty($manualIds)) { return []; }
                $placeholders = implode(',', array_fill(0, count($manualIds), '?'));
                $base .= " AND id IN ($placeholders)";
                $params = array_merge($params, $manualIds);
                break;
            case 'all':
            default:
                break;
        }

        $stmt = self::db()->prepare($base);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Snapshots the resolved audience into pending recipient rows for a campaign. */
    public static function seed(int $campaignId, array $customers): int
    {
        if (empty($customers)) { return 0; }
        $stmt = self::db()->prepare('INSERT INTO campaign_recipients (campaign_id, customer_id, email, status) VALUES (?, ?, ?, "pending")');
        foreach ($customers as $c) {
            $stmt->execute([$campaignId, $c['id'], $c['email']]);
        }
        return count($customers);
    }

    /** Next batch of not-yet-sent recipients, for both the "send now" endpoint and the cron batch script. */
    public static function nextPending(int $campaignId, int $limit = 30): array
    {
        $stmt = self::db()->prepare('SELECT * FROM campaign_recipients WHERE campaign_id = ? AND status = "pending" LIMIT ' . (int) $limit);
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    public static function markSent(int $id): void
    {
        $stmt = self::db()->prepare('UPDATE campaign_recipients SET status = "sent", sent_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function markFailed(int $id, string $error): void
    {
        $stmt = self::db()->prepare('UPDATE campaign_recipients SET status = "failed", error = ? WHERE id = ?');
        $stmt->execute([substr($error, 0, 255), $id]);
    }

    /** All campaign_ids anywhere that still have pending recipients — used by the cron batch script. */
    public static function campaignIdsWithPending(): array
    {
        $stmt = self::db()->query(
            "SELECT DISTINCT c.id FROM campaigns c
             INNER JOIN campaign_recipients cr ON cr.campaign_id = c.id
             WHERE cr.status = 'pending' AND c.status IN ('sending', 'scheduled')
             AND (c.scheduled_at IS NULL OR c.scheduled_at <= NOW())"
        );
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}
