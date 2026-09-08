<?php
namespace App\Models;

/**
 * Subscription plans (Basic / Advanced / Premium) and their per-feature
 * gates. feature_key values match the SPA route paths used in admin.js's
 * ROUTES table (pos, products, customers, expenses, orders, store, staff,
 * branches, reports) plus the virtual key 'ai_insights' for the dashboard
 * AI widget. The dashboard itself is never gated.
 */
class Plan
{
    protected static function db(): \PDO { return \App\Core\Database::connect(); }

    public static function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM plans' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order ASC, price_monthly ASC';
        return self::db()->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM plans WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByKey(string $key): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM plans WHERE `key` = ? LIMIT 1');
        $stmt->execute([$key]);
        return $stmt->fetch() ?: null;
    }

    /** All feature rows for a plan, e.g. [['feature_key'=>'pos','feature_label'=>'Sales / POS','enabled'=>1], ...] */
    public static function features(int $planId): array
    {
        $stmt = self::db()->prepare('SELECT feature_key, feature_label, enabled FROM plan_features WHERE plan_id = ? ORDER BY id ASC');
        $stmt->execute([$planId]);
        return $stmt->fetchAll();
    }

    /** Just the enabled feature_keys, e.g. ['pos','products',...] */
    public static function enabledFeatureKeys(int $planId): array
    {
        return array_values(array_map(
            fn ($f) => $f['feature_key'],
            array_filter(self::features($planId), fn ($f) => (int) $f['enabled'] === 1)
        ));
    }

    /** The feature_keys NOT enabled for this plan — used to disable/lock sidebar items. */
    public static function lockedFeatureKeys(int $planId): array
    {
        return array_values(array_map(
            fn ($f) => $f['feature_key'],
            array_filter(self::features($planId), fn ($f) => (int) $f['enabled'] === 0)
        ));
    }

    public static function withFeatures(bool $activeOnly = false): array
    {
        $plans = self::all($activeOnly);
        foreach ($plans as &$p) {
            $p['features'] = self::features((int) $p['id']);
        }
        return $plans;
    }

    public static function updateDetails(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        foreach (['name', 'price_monthly', 'description', 'is_active'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }
        if (empty($fields)) return true;
        $params[] = $id;
        $stmt = self::db()->prepare('UPDATE plans SET ' . implode(', ', $fields) . ' WHERE id = ?');
        return $stmt->execute($params);
    }

    /** $features = ['pos' => true, 'store' => false, ...] */
    public static function setFeatures(int $planId, array $features): void
    {
        $stmt = self::db()->prepare('UPDATE plan_features SET enabled = ? WHERE plan_id = ? AND feature_key = ?');
        foreach ($features as $key => $enabled) {
            $stmt->execute([$enabled ? 1 : 0, $planId, $key]);
        }
    }
}
