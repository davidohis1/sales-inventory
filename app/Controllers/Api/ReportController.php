<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\Expense;

class ReportController
{
    private function range(Request $request): array
    {
        $from = (string) $request->input('from', date('Y-m-01'));
        $to   = (string) $request->input('to', date('Y-m-d'));
        return [$from, $to];
    }

    public function sales(Request $request): void
    {
        [$from, $to] = $this->range($request);
        $tenantId = Auth::tenantId();
        $pdo = Database::connect();

        $stmt = $pdo->prepare("SELECT DATE(created_at) AS day, COUNT(*) AS sales_count, COALESCE(SUM(total),0) AS revenue
                                FROM sales WHERE tenant_id = ? AND created_at BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59')
                                AND status != 'cancelled' GROUP BY DATE(created_at) ORDER BY day");
        $stmt->execute([$tenantId, $from, $to]);
        $byDay = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT COUNT(*) AS sales_count, COALESCE(SUM(total),0) AS revenue, COALESCE(SUM(discount),0) AS total_discount
                                FROM sales WHERE tenant_id = ? AND created_at BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59')
                                AND status != 'cancelled'");
        $stmt->execute([$tenantId, $from, $to]);
        $totals = $stmt->fetch();

        Response::success(['from' => $from, 'to' => $to, 'by_day' => $byDay, 'totals' => $totals]);
    }

    public function inventory(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.sku, c.name AS category_name, p.quantity, p.min_stock_level,
                                       p.buying_price, p.selling_price, (p.quantity * p.buying_price) AS stock_value
                                FROM products p LEFT JOIN categories c ON c.id = p.category_id
                                WHERE p.tenant_id = ? AND p.is_active = 1 ORDER BY p.name");
        $stmt->execute([$tenantId]);
        Response::success($stmt->fetchAll());
    }

    public function profit(Request $request): void
    {
        [$from, $to] = $this->range($request);
        $tenantId = Auth::tenantId();
        $pdo = Database::connect();

        $stmt = $pdo->prepare("SELECT p.id, p.name,
                SUM(si.quantity) AS units_sold,
                SUM(si.line_total) AS revenue,
                SUM(si.unit_cost * si.quantity) AS cost,
                (SUM(si.line_total) - SUM(si.unit_cost * si.quantity)) AS profit,
                CASE WHEN SUM(si.line_total) > 0
                     THEN ROUND(((SUM(si.line_total) - SUM(si.unit_cost * si.quantity)) / SUM(si.line_total)) * 100, 2)
                     ELSE 0 END AS margin_pct
                FROM sale_items si
                JOIN sales s ON s.id = si.sale_id
                JOIN products p ON p.id = si.product_id
                WHERE si.tenant_id = ? AND s.created_at BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59')
                    AND s.status != 'cancelled'
                GROUP BY p.id, p.name ORDER BY profit DESC");
        $stmt->execute([$tenantId, $from, $to]);
        $perProduct = $stmt->fetchAll();

        $totalRevenue = array_sum(array_column($perProduct, 'revenue'));
        $totalCost = array_sum(array_column($perProduct, 'cost'));
        $expenses = Expense::totalForRange($tenantId, $from, $to);
        $grossProfit = $totalRevenue - $totalCost;
        $netProfit = $grossProfit - $expenses;

        Response::success([
            'from' => $from, 'to' => $to, 'per_product' => $perProduct,
            'summary' => [
                'total_revenue' => $totalRevenue, 'total_cost' => $totalCost,
                'gross_profit' => $grossProfit, 'expenses' => $expenses, 'net_profit' => $netProfit,
            ],
        ]);
    }

    public function staffPerformance(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        [$from, $to] = $this->range($request);
        Response::success(ActivityLog::staffPerformance(Auth::tenantId(), $from, $to));
    }

    public function customers(Request $request): void
    {
        $tenantId = Auth::tenantId();
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT c.id, c.name, c.phone, c.outstanding_debt,
                COUNT(s.id) AS total_orders, COALESCE(SUM(s.total),0) AS lifetime_value
                FROM customers c LEFT JOIN sales s ON s.customer_id = c.id AND s.tenant_id = c.tenant_id
                WHERE c.tenant_id = ? GROUP BY c.id, c.name, c.phone, c.outstanding_debt
                ORDER BY lifetime_value DESC");
        $stmt->execute([$tenantId]);
        Response::success($stmt->fetchAll());
    }

    /** Generic CSV export for any of the above reports: ?type=sales|inventory|profit|customers */
    public function export(Request $request): void
    {
        $type = (string) $request->input('type', 'sales');
        [$from, $to] = $this->range($request);
        $tenantId = Auth::tenantId();
        $pdo = Database::connect();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $type . '_report_' . $from . '_to_' . $to . '.csv"');
        $out = fopen('php://output', 'w');

        switch ($type) {
            case 'inventory':
                $stmt = $pdo->prepare("SELECT name, sku, quantity, min_stock_level, buying_price, selling_price FROM products WHERE tenant_id = ? AND is_active = 1 ORDER BY name");
                $stmt->execute([$tenantId]);
                fputcsv($out, ['Product', 'SKU', 'Quantity', 'Min Stock', 'Buying Price', 'Selling Price']);
                foreach ($stmt->fetchAll() as $row) fputcsv($out, $row);
                break;
            case 'profit':
                $stmt = $pdo->prepare("SELECT p.name, SUM(si.quantity) qty, SUM(si.line_total) revenue, SUM(si.unit_cost*si.quantity) cost,
                        (SUM(si.line_total)-SUM(si.unit_cost*si.quantity)) profit
                        FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN products p ON p.id=si.product_id
                        WHERE si.tenant_id=? AND s.created_at BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59')
                        GROUP BY p.name ORDER BY profit DESC");
                $stmt->execute([$tenantId, $from, $to]);
                fputcsv($out, ['Product', 'Units Sold', 'Revenue', 'Cost', 'Profit']);
                foreach ($stmt->fetchAll() as $row) fputcsv($out, $row);
                break;
            case 'customers':
                $stmt = $pdo->prepare("SELECT name, phone, outstanding_debt FROM customers WHERE tenant_id = ? ORDER BY name");
                $stmt->execute([$tenantId]);
                fputcsv($out, ['Customer', 'Phone', 'Outstanding Debt']);
                foreach ($stmt->fetchAll() as $row) fputcsv($out, $row);
                break;
            default: // sales
                $stmt = $pdo->prepare("SELECT receipt_no, created_at, total, payment_method, status FROM sales WHERE tenant_id = ? AND created_at BETWEEN CONCAT(?, ' 00:00:00') AND CONCAT(?, ' 23:59:59') ORDER BY created_at");
                $stmt->execute([$tenantId, $from, $to]);
                fputcsv($out, ['Receipt No', 'Date', 'Total', 'Payment Method', 'Status']);
                foreach ($stmt->fetchAll() as $row) fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
