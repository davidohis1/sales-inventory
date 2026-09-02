<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Plan;
use App\Models\Tenant;

class PlanController
{
    /** Public — used by the pricing page and the register/upgrade flow. No auth required. */
    public function index(Request $request): void
    {
        Response::success(Plan::withFeatures(true));
    }

    /** Tenant-scoped — current plan/trial status + locked feature keys, for sidebar gating. */
    public function status(Request $request): void
    {
        $tenant = Tenant::findById((int) Auth::tenantId());
        if (!$tenant) { Response::error('Business not found', 404); return; }
        $status = Tenant::accessStatus($tenant);
        Response::success([
            'status' => $status['status'],
            'days_remaining' => $status['days_remaining'],
            'expires_at' => $status['expires_at'],
            'plan' => $status['plan'] ? ['id' => (int) $status['plan']['id'], 'key' => $status['plan']['key'], 'name' => $status['plan']['name']] : null,
            'locked_features' => $status['locked_features'],
        ]);
    }
}
