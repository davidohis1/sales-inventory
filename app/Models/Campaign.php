<?php
namespace App\Models;

class Campaign extends BaseModel
{
    protected static function table(): string { return 'campaigns'; }

    public static function listForTenant(int $tenantId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM campaigns WHERE tenant_id = ? ORDER BY created_at DESC');
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO campaigns (tenant_id, created_by, subject, body_html, audience, audience_customer_ids, status, scheduled_at, total_recipients)
             VALUES (:tenant_id, :created_by, :subject, :body_html, :audience, :audience_customer_ids, :status, :scheduled_at, :total_recipients)'
        );
        $stmt->execute([
            'tenant_id' => $data['tenant_id'],
            'created_by' => $data['created_by'] ?? null,
            'subject' => $data['subject'],
            'body_html' => $data['body_html'],
            'audience' => $data['audience'] ?? 'all',
            'audience_customer_ids' => $data['audience_customer_ids'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'total_recipients' => $data['total_recipients'] ?? 0,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = self::db()->prepare('UPDATE campaigns SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    /** Recompute sent/failed counters from campaign_recipients and flip to 'sent' once nothing is pending. */
    public static function refreshCounts(int $id): array
    {
        $stmt = self::db()->prepare(
            "SELECT
                SUM(status = 'sent') AS sent_count,
                SUM(status = 'failed') AS failed_count,
                SUM(status = 'pending') AS pending_count,
                COUNT(*) AS total
             FROM campaign_recipients WHERE campaign_id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $sent = (int) ($row['sent_count'] ?? 0);
        $failed = (int) ($row['failed_count'] ?? 0);
        $pending = (int) ($row['pending_count'] ?? 0);

        $status = $pending > 0 ? 'sending' : ($failed > 0 && $sent === 0 ? 'failed' : 'sent');
        $stmt = self::db()->prepare('UPDATE campaigns SET sent_count = ?, failed_count = ?, status = ? WHERE id = ?');
        $stmt->execute([$sent, $failed, $status, $id]);

        return ['sent_count' => $sent, 'failed_count' => $failed, 'pending_count' => $pending, 'status' => $status];
    }
}
