<?php
namespace App\Models;

class Tenant extends BaseModel
{
    protected static function table(): string { return 'tenants'; }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM tenants WHERE slug = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM tenants WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function slugExists(string $slug): bool
    {
        $stmt = self::db()->prepare('SELECT id FROM tenants WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        return (bool) $stmt->fetch();
    }

    public static function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '', $slug);
        $slug = substr($slug, 0, 40);
        if ($slug === '') $slug = 'biz' . random_int(1000, 9999);
        return $slug;
    }

    /** Guarantees a unique slug by appending a number if the base slug is taken. */
    public static function uniqueSlugFrom(string $name): string
    {
        $reserved = ['register', 'login', 'pricing', 'plans', 'platformadmin', 'payments', 'api', 'admin', 'assets', 'uploads', 'logout'];
        $base = self::slugify($name);
        if (in_array($base, $reserved, true)) $base .= 'biz';
        $slug = $base;
        $i = 1;
        while (self::slugExists($slug) || in_array($slug, $reserved, true)) {
            $i++;
            $slug = $base . $i;
        }
        return $slug;
    }

    /**
     * Creates a new business (tenant) on a 3-day free trial. Returns the new tenant id.
     */
    public static function create(array $data): int
    {
        $trialDays = 3;
        $stmt = self::db()->prepare(
            "INSERT INTO tenants (slug, business_name, owner_email, owner_phone, currency, subscription_status, trial_ends_at)
             VALUES (?, ?, ?, ?, ?, 'trial', DATE_ADD(NOW(), INTERVAL ? DAY))"
        );
        $stmt->execute([
            $data['slug'],
            $data['business_name'],
            $data['owner_email'] ?? null,
            $data['owner_phone'] ?? null,
            $data['currency'] ?? 'NGN',
            $trialDays,
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function all(): array
    {
        $stmt = self::db()->query('SELECT t.*, p.name AS plan_name, p.`key` AS plan_key, p.price_monthly AS plan_price
                                    FROM tenants t LEFT JOIN plans p ON p.id = t.plan_id
                                    ORDER BY t.created_at DESC');
        return $stmt->fetchAll();
    }

    public static function updatePlan(int $tenantId, int $planId, string $status, ?string $subscriptionEndsAt): bool
    {
        $stmt = self::db()->prepare('UPDATE tenants SET plan_id = ?, subscription_status = ?, subscription_ends_at = ? WHERE id = ?');
        return $stmt->execute([$planId, $status, $subscriptionEndsAt, $tenantId]);
    }

    public static function markReminderSent(int $tenantId): void
    {
        $stmt = self::db()->prepare('UPDATE tenants SET last_reminder_sent_at = NOW() WHERE id = ?');
        $stmt->execute([$tenantId]);
    }

    /**
     * Computes the tenant's real-time access status. This is the single
     * source of truth used at login time, by the sidebar feature-gate, and
     * by the platform admin's expiry column — so "days left" always agrees
     * everywhere.
     *
     * Returns:
     *   status: 'trial' | 'active' | 'expired'
     *   days_remaining: int (can be negative once expired)
     *   expires_at: the trial or subscription end date, whichever applies
     *   plan: the plan row (or null while on trial / expired with none)
     *   locked_features: feature_keys NOT available right now
     */
    public static function accessStatus(array $tenant): array
    {
        $now = new \DateTimeImmutable();
        $plan = $tenant['plan_id'] ? Plan::find((int) $tenant['plan_id']) : null;

        if ($tenant['subscription_status'] === 'active' && $tenant['subscription_ends_at']) {
            $expiresAt = new \DateTimeImmutable($tenant['subscription_ends_at']);
            $daysRemaining = (int) ceil(($expiresAt->getTimestamp() - $now->getTimestamp()) / 86400);
            if ($daysRemaining < 0) {
                $locked = array_map(fn ($f) => $f['feature_key'], Plan::features($plan['id'] ?? 0));
                return ['status' => 'expired', 'days_remaining' => $daysRemaining, 'expires_at' => $tenant['subscription_ends_at'], 'plan' => $plan, 'locked_features' => $locked];
            }
            $locked = $plan ? Plan::lockedFeatureKeys((int) $plan['id']) : [];
            return ['status' => 'active', 'days_remaining' => $daysRemaining, 'expires_at' => $tenant['subscription_ends_at'], 'plan' => $plan, 'locked_features' => $locked];
        }

        // Still on trial (or trial expired and never upgraded)
        $trialEnds = $tenant['trial_ends_at'] ? new \DateTimeImmutable($tenant['trial_ends_at']) : $now;
        $daysRemaining = (int) ceil(($trialEnds->getTimestamp() - $now->getTimestamp()) / 86400);
        if ($daysRemaining < 0) {
            return ['status' => 'expired', 'days_remaining' => $daysRemaining, 'expires_at' => $tenant['trial_ends_at'], 'plan' => null, 'locked_features' => ['pos','products','customers','expenses','orders','store','staff','branches','reports','ai_insights']];
        }
        // On an active trial, every feature is unlocked so businesses can evaluate the full product.
        return ['status' => 'trial', 'days_remaining' => $daysRemaining, 'expires_at' => $tenant['trial_ends_at'], 'plan' => null, 'locked_features' => []];
    }
}