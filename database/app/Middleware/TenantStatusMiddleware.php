<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Tenant;

class TenantStatusMiddleware
{
    public function __invoke(Request $request)
    {
        $tenant = Tenant::findById((int) Auth::tenantId());
        if (!$tenant) { Response::error('Business not found', 404); return false; }

        $status = Tenant::accessStatus($tenant);
        if ($status['status'] === 'expired') {
            Response::json([
                'success' => false,
                'message' => 'Your free trial has expired. Choose a plan to continue.',
                'trial_expired' => true,
                'redirect' => '/plans',
            ], 402);
            return false;
        }

        return true;
    }
}
