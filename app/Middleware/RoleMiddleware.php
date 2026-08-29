<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/**
 * Factory for role-scoped middleware, e.g. RoleMiddleware::allow('owner','manager')
 */
class RoleMiddleware
{
    public static function allow(string ...$roles): callable
    {
        return function (Request $request) use ($roles) {
            if (!Auth::hasRole($roles)) {
                Response::error('Forbidden: insufficient permissions', 403);
                return false;
            }
            return true;
        };
    }
}
