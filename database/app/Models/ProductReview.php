<?php
namespace App\Models;

class ProductReview extends BaseModel
{
    protected static function table(): string { return 'product_reviews'; }

    public static function forProduct(int $tenantId, int $productId): array
    {
        $stmt = self::db()->prepare('SELECT id, reviewer_name, review_text, created_at FROM product_reviews
                                      WHERE tenant_id = ? AND product_id = ? ORDER BY created_at DESC');
        $stmt->execute([$tenantId, $productId]);
        return $stmt->fetchAll(); // reviewer_email is intentionally never selected here — kept private
    }

    public static function create(int $tenantId, int $productId, string $name, string $email, string $reviewText): int
    {
        $stmt = self::db()->prepare('INSERT INTO product_reviews (tenant_id, product_id, reviewer_name, reviewer_email, review_text)
                                      VALUES (?,?,?,?,?)');
        $stmt->execute([$tenantId, $productId, $name, $email, $reviewText]);
        return (int) self::db()->lastInsertId();
    }
}