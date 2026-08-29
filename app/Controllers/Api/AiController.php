<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Models\Expense;
use App\Models\Product;

/**
 * AI Insights widget backend — pulls the tenant's own sales/inventory data,
 * builds a compact JSON context, and asks Google Gemini for a plain-language
 * business summary. Pluggable: set GEMINI_API_KEY in .env to activate; without
 * a key, a rule-based fallback summary is generated locally so the widget
 * still works out of the box.
 */
class AiController
{
    public function insights(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $context = $this->buildContext($tenantId);

        $apiKey = Env::get('GEMINI_API_KEY', '');
        $summary = $apiKey !== ''
            ? $this->callGemini($apiKey, $context)
            : $this->fallbackSummary($context);

        $pdo = Database::connect();
        $stmt = $pdo->prepare('INSERT INTO ai_insights (tenant_id, summary, raw_context) VALUES (?,?,?)');
        $stmt->execute([$tenantId, $summary, json_encode($context)]);

        Response::success([
            'summary'      => $summary,
            'generated_at' => date('c'),
            'source'       => $apiKey !== '' ? 'gemini' : 'rule_based_fallback',
            'context'      => $context,
        ]);
    }

    public function history(Request $request): void
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare('SELECT id, summary, created_at FROM ai_insights WHERE tenant_id = ? ORDER BY created_at DESC LIMIT 10');
        $stmt->execute([Auth::tenantId()]);
        Response::success($stmt->fetchAll());
    }

    /**
     * Generic short-copy generator used by the Store page (hero/banner text)
     * and the product form (descriptions). Same Gemini-or-fallback pattern
     * as the insights widget, so it always returns something usable.
     */
    public function generateText(Request $request): void
    {
        $kind = (string) $request->input('kind', 'generic');
        $context = $request->input('context', []);
        if (!is_array($context)) $context = [];

        $prompts = [
            'hero_heading'    => 'Write ONE short, punchy e-commerce homepage hero headline (max 8 words) for a ' . ($context['store_type'] ?? 'general') . ' store called "' . ($context['business_name'] ?? 'the store') . '". No quotation marks.',
            'hero_subheading' => 'Write ONE short supporting subheading (max 20 words) under a hero headline for a ' . ($context['store_type'] ?? 'general') . ' store called "' . ($context['business_name'] ?? 'the store') . '". No quotation marks.',
            'announcement'    => 'Write ONE short promotional announcement-bar line (max 12 words) for an online store, e.g. about a sale or free shipping. No quotation marks.',
            'product_description' => 'Write a persuasive, concise product description (2-3 sentences, no markdown, no quotation marks) for this product: name="' . ($context['name'] ?? '') . '", category="' . ($context['category'] ?? '') . '", price="' . ($context['price'] ?? '') . '".',
            'generic'         => (string) ($context['prompt'] ?? 'Write one short sentence of marketing copy for an online store.'),
        ];
        $prompt = $prompts[$kind] ?? $prompts['generic'];

        $apiKey = Env::get('GEMINI_API_KEY', '');
        $text = $apiKey !== '' ? $this->callGeminiText($apiKey, $prompt) : null;
        if (!$text) {
            $text = $this->fallbackText($kind, $context);
        }

        Response::success(['text' => trim($text, "\"'\n ")]);
    }

    private function callGeminiText(string $apiKey, string $prompt): ?string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($apiKey);
        $payload = json_encode(['contents' => [['parts' => [['text' => $prompt]]]]]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($response === false || $error) return null;

        $data = json_decode($response, true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    /** Deterministic fallback copy when no GEMINI_API_KEY is set (or the call fails), so the AI button never dead-ends. */
    private function fallbackText(string $kind, array $context): string
    {
        $biz = $context['business_name'] ?? 'Our Store';
        $type = $context['store_type'] ?? 'general';
        switch ($kind) {
            case 'hero_heading':
                return match ($type) {
                    'fashion' => 'Style That Fits Your Life',
                    'tech' => 'Smarter Tech, Better Prices',
                    'beauty' => 'Beauty, Simplified',
                    'grocery' => 'Fresh Picks, Delivered',
                    default => "Everything You Need at $biz",
                };
            case 'hero_subheading':
                return 'Quality products, fair prices, and fast delivery — shop the collection today.';
            case 'announcement':
                return 'Free shipping this week on all orders — shop now';
            case 'product_description':
                $name = $context['name'] ?? 'This product';
                $cat = $context['category'] ?? null;
                return trim("$name" . ($cat ? " is a top pick in our $cat range." : ' is one of our customer favorites.') . ' Great quality, reliable performance, and built to last — a smart choice for everyday use.');
            default:
                return 'Quality you can trust, prices you will love.';
        }
    }

    private function buildContext(int $tenantId): array
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) revenue, COUNT(*) count FROM sales
                                WHERE tenant_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status != 'cancelled'");
        $stmt->execute([$tenantId]);
        $thisWeek = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) revenue, COUNT(*) count FROM sales
                                WHERE tenant_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                                AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) AND status != 'cancelled'");
        $stmt->execute([$tenantId]);
        $lastWeek = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT p.name, SUM(si.quantity) qty FROM sale_items si
                                JOIN products p ON p.id = si.product_id JOIN sales s ON s.id = si.sale_id
                                WHERE si.tenant_id = ? AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                GROUP BY p.name ORDER BY qty ASC LIMIT 5");
        $stmt->execute([$tenantId]);
        $slowMovers = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT p.name,
                ROUND(AVG((si.unit_price - si.unit_cost) / NULLIF(si.unit_price,0) * 100), 1) AS avg_margin_pct
                FROM sale_items si JOIN products p ON p.id = si.product_id JOIN sales s ON s.id = si.sale_id
                WHERE si.tenant_id = ? AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY p.name HAVING avg_margin_pct < 15 ORDER BY avg_margin_pct ASC LIMIT 5");
        $stmt->execute([$tenantId]);
        $lowMargin = $stmt->fetchAll();

        return [
            'this_week_revenue' => (float) $thisWeek['revenue'],
            'this_week_sales_count' => (int) $thisWeek['count'],
            'last_week_revenue' => (float) $lastWeek['revenue'],
            'last_week_sales_count' => (int) $lastWeek['count'],
            'low_stock' => Product::lowStock($tenantId),
            'slow_moving_products' => $slowMovers,
            'below_normal_margin_products' => $lowMargin,
            'expenses_last_30_days' => Expense::totalForRange($tenantId, date('Y-m-d', strtotime('-30 days')), date('Y-m-d')),
        ];
    }

    private function callGemini(string $apiKey, array $context): string
    {
        $prompt = "You are a business analyst assistant for a small retail shop. "
            . "Using ONLY the JSON business data below, write a short, plain-language summary (5-8 bullet points) "
            . "covering: sales trend (week over week), any slow-moving stock, products sold below normal margin, "
            . "and concrete restock suggestions. Be specific and use the currency symbol ₦. Data:\n\n"
            . json_encode($context);

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($apiKey);
        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error) {
            return $this->fallbackSummary($context) . "\n\n(Note: AI service unreachable — showing a rule-based summary instead.)";
        }

        $data = json_decode($response, true);
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        return $text ?: $this->fallbackSummary($context);
    }

    /** Simple deterministic summary used when no GEMINI_API_KEY is configured, or the API call fails. */
    private function fallbackSummary(array $context): string
    {
        $lines = [];
        $wow = $context['last_week_revenue'] > 0
            ? round((($context['this_week_revenue'] - $context['last_week_revenue']) / $context['last_week_revenue']) * 100, 1)
            : null;

        $lines[] = "• This week's revenue: ₦" . number_format($context['this_week_revenue'], 2)
            . " across {$context['this_week_sales_count']} sales"
            . ($wow !== null ? " ({$wow}% vs last week)." : '.');

        if (!empty($context['low_stock'])) {
            $names = implode(', ', array_column(array_slice($context['low_stock'], 0, 5), 'name'));
            $lines[] = "• Low stock alert: $names — consider restocking soon.";
        } else {
            $lines[] = '• No products are currently below their minimum stock level.';
        }

        if (!empty($context['slow_moving_products'])) {
            $names = implode(', ', array_column($context['slow_moving_products'], 'name'));
            $lines[] = "• Slow-moving in the last 30 days: $names.";
        }

        if (!empty($context['below_normal_margin_products'])) {
            $names = implode(', ', array_column($context['below_normal_margin_products'], 'name'));
            $lines[] = "• Selling below a healthy margin (<15%): $names — review pricing or supplier cost.";
        }

        $lines[] = '• Expenses in the last 30 days: ₦' . number_format($context['expenses_last_30_days'], 2) . '.';
        $lines[] = '• Tip: add a GEMINI_API_KEY in your .env to get richer, AI-generated insights here.';

        return implode("\n", $lines);
    }
}
