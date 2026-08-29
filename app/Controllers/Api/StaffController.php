<?php
namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\ActivityLog;
use App\Models\User;

class StaffController
{
    public function index(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        Response::success(User::allForTenant(Auth::tenantId(), (string) $request->input('q', '')));
    }

    public function store(Request $request): void
    {
        if (!Auth::hasRole(['owner'])) { Response::error('Only the owner can add staff accounts', 403); return; }
        $tenantId = Auth::tenantId();
        $name = trim((string) $request->input('full_name', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $role = (string) $request->input('role', 'staff');

        if ($name === '' || $email === '' || strlen($password) < 6) {
            Response::error('Full name, email, and a password of at least 6 characters are required', 422);
            return;
        }
        if (!in_array($role, ['owner', 'manager', 'staff'], true)) { Response::error('Invalid role', 422); return; }
        if (User::findByEmail($tenantId, $email)) { Response::error('A staff member with this email already exists', 422); return; }

        $id = User::create([
            'tenant_id' => $tenantId, 'branch_id' => $request->input('branch_id') ?: null,
            'full_name' => $name, 'email' => $email, 'phone' => $request->input('phone'),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT), 'role' => $role,
        ]);
        ActivityLog::record($tenantId, Auth::id(), 'staff.create', "Added staff member $name ($role)");
        Response::success(User::find($tenantId, $id), 'Staff account created', 201);
    }

    public function update(Request $request): void
    {
        if (!Auth::hasRole(['owner'])) { Response::error('Only the owner can edit staff accounts', 403); return; }
        $tenantId = Auth::tenantId();
        $id = (int) $request->param('id');
        if (!User::find($tenantId, $id)) { Response::error('Staff member not found', 404); return; }

        $data = [];
        foreach (['full_name', 'phone', 'role', 'branch_id', 'is_active'] as $f) {
            if ($request->input($f) !== null) $data[$f] = $request->input($f);
        }
        if ($request->input('password')) {
            $data['password_hash'] = password_hash((string) $request->input('password'), PASSWORD_BCRYPT);
        }
        User::update($tenantId, $id, $data);
        ActivityLog::record($tenantId, Auth::id(), 'staff.edit', "Edited staff #$id");
        Response::success(User::find($tenantId, $id), 'Staff account updated');
    }

    public function activityLog(Request $request): void
    {
        if (!Auth::hasRole(['owner', 'manager'])) { Response::error('Forbidden', 403); return; }
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);
        Response::success(ActivityLog::listForTenant(Auth::tenantId(), $page, $perPage));
    }
}
