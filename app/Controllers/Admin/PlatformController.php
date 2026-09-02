<?php
namespace App\Controllers\Admin;

use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Tenant;

class PlatformController
{
    /** Page 1: stats overview. */
    public function stats(Request $request): void
    {
        $tenants = Tenant::all();
        $total = count($tenants);
        $counts = ['trial' => 0, 'active' => 0, 'expired' => 0];
        $planCounts = [];
        $revenue = 0.0;

        foreach ($tenants as $t) {
            $status = Tenant::accessStatus($t);
            $counts[$status['status']] = ($counts[$status['status']] ?? 0) + 1;
            if ($status['plan']) {
                $key = $status['plan']['key'];
                $planCounts[$key] = ($planCounts[$key] ?? 0) + 1;
            }
        }

        $pdo = \App\Core\Database::connect();
        $revenue = (float) $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'successful' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
        $recentPayments = $pdo->query("SELECT p.*, t.business_name, pl.name AS plan_name FROM payments p
                                        JOIN tenants t ON t.id = p.tenant_id JOIN plans pl ON pl.id = p.plan_id
                                        WHERE p.status = 'successful' ORDER BY p.created_at DESC LIMIT 8")->fetchAll();

        Response::success([
            'total_businesses' => $total,
            'trial_count' => $counts['trial'],
            'active_count' => $counts['active'],
            'expired_count' => $counts['expired'],
            'plan_breakdown' => $planCounts,
            'revenue_last_30_days' => $revenue,
            'recent_payments' => $recentPayments,
        ]);
    }

    /** Page 2: every business, its plan, and days-to-expiry (for the reminder workflow + color-coded date). */
    public function businesses(Request $request): void
    {
        $tenants = Tenant::all();
        $out = [];
        foreach ($tenants as $t) {
            $status = Tenant::accessStatus($t);
            $out[] = [
                'id' => (int) $t['id'],
                'slug' => $t['slug'],
                'business_name' => $t['business_name'],
                'owner_email' => $t['owner_email'],
                'owner_phone' => $t['owner_phone'],
                'plan_name' => $status['plan']['name'] ?? null,
                'plan_key' => $status['plan']['key'] ?? null,
                'status' => $status['status'],
                'days_remaining' => $status['days_remaining'],
                'expires_at' => $status['expires_at'],
                'last_reminder_sent_at' => $t['last_reminder_sent_at'],
                'created_at' => $t['created_at'],
            ];
        }
        Response::success($out);
    }

    /** Sends a "your plan expires soon" reminder email to the business's contact address. */
    public function remind(Request $request): void
    {
        $id = (int) $request->param('id');
        $tenant = Tenant::findById($id);
        if (!$tenant) { Response::error('Business not found', 404); return; }
        if (!$tenant['owner_email']) { Response::error('This business has no contact email on file', 422); return; }

        $status = Tenant::accessStatus($tenant);
        $days = $status['days_remaining'];
        $planLabel = $status['plan']['name'] ?? 'free trial';

        if ($days < 0) {
            $subject = "Your {$planLabel} has expired";
            $bodyLine = "your access expired " . abs($days) . " day(s) ago.";
        } elseif ($days === 0) {
            $subject = "Your {$planLabel} expires today";
            $bodyLine = "your access expires today.";
        } else {
            $subject = "Your {$planLabel} expires in {$days} day(s)";
            $bodyLine = "you have {$days} day(s) left before your access expires.";
        }

        $html = "<p>Hi " . htmlspecialchars($tenant['business_name']) . ",</p>"
              . "<p>This is a friendly reminder that {$bodyLine}</p>"
              . "<p>Renew or upgrade any time from your dashboard's Plans page to avoid any interruption.</p>"
              . "<p>— The team</p>";

        Mailer::send($tenant['owner_email'], $subject, $html);
        Tenant::markReminderSent($id);

        Response::success(['sent_to' => $tenant['owner_email'], 'days_remaining' => $days], 'Reminder email sent');
    }

    /** Plan + price + per-plan feature management (shown as a section on the admin dashboard). */
    public function plans(Request $request): void
    {
        Response::success(Plan::withFeatures(false));
    }

    public function updatePlan(Request $request): void
    {
        $id = (int) $request->param('id');
        $plan = Plan::find($id);
        if (!$plan) { Response::error('Plan not found', 404); return; }

        $data = [];
        if ($request->input('name') !== null) $data['name'] = trim((string) $request->input('name'));
        if ($request->input('price_monthly') !== null) $data['price_monthly'] = (float) $request->input('price_monthly');
        if ($request->input('description') !== null) $data['description'] = trim((string) $request->input('description'));
        Plan::updateDetails($id, $data);

        $features = $request->input('features'); // { pos: true, store: false, ... }
        if (is_array($features)) {
            Plan::setFeatures($id, $features);
        }

        $updated = Plan::find($id);
        $updated['features'] = Plan::features($id);
        Response::success($updated, 'Plan updated');
    }
}
