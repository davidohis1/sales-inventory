<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Product;

class DashboardController
{
    public function summary(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $pdo = Database::connect();

        $today = date('Y-m-d');

        // Today's sales & profit
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) AS revenue, COUNT(*) AS count
                                FROM sales WHERE tenant_id = ? AND DATE(created_at) = ? AND status != 'cancelled'");
        $stmt->execute([$tenantId, $today]);
        $todaySales = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(si.line_total),0) AS revenue, COALESCE(SUM(si.unit_cost * si.quantity),0) AS cost
                                FROM sale_items si JOIN sales s ON s.id = si.sale_id
                                WHERE si.tenant_id = ? AND DATE(s.created_at) = ? AND s.status != 'cancelled'");
        $stmt->execute([$tenantId, $today]);
        $todayMargin = $stmt->fetch();
        $todayExpenses = \App\Models\Expense::totalForRange($tenantId, $today, $today);
        $todayProfit = ((float) $todayMargin['revenue'] - (float) $todayMargin['cost']) - $todayExpenses;

        // payment method breakdown today
        $stmt = $pdo->prepare("SELECT sp.method, COALESCE(SUM(sp.amount),0) AS total FROM sale_payments sp
                                JOIN sales s ON s.id = sp.sale_id
                                WHERE sp.tenant_id = ? AND DATE(sp.created_at) = ?
                                GROUP BY sp.method");
        $stmt->execute([$tenantId, $today]);
        $paymentBreakdown = $stmt->fetchAll();

        // best-selling products (last 30 days)
        $stmt = $pdo->prepare("SELECT p.id, p.name, SUM(si.quantity) AS total_qty, SUM(si.line_total) AS total_revenue
                                FROM sale_items si
                                JOIN products p ON p.id = si.product_id
                                JOIN sales s ON s.id = si.sale_id
                                WHERE si.tenant_id = ? AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                GROUP BY p.id, p.name ORDER BY total_qty DESC LIMIT 5");
        $stmt->execute([$tenantId]);
        $bestSellers = $stmt->fetchAll();

        Response::success([
            'today_revenue'    => (float) $todaySales['revenue'],
            'today_sales_count'=> (int) $todaySales['count'],
            'today_profit'     => round($todayProfit, 2),
            'stock_value'      => Product::stockValue($tenantId),
            'low_stock_count'  => Product::lowStockCount($tenantId),
            'out_of_stock_count' => Product::outOfStockCount($tenantId),
            'payment_breakdown'=> $paymentBreakdown,
            'best_sellers'     => $bestSellers,
        ]);
    }
}
