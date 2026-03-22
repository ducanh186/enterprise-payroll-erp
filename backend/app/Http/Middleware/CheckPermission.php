<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Permission matrix mapping module.action to allowed roles.
     */
    private const PERMISSIONS = [
        'employee.read' => ['hr_staff', 'accountant', 'system_admin', 'management'],
        'employee.write' => ['hr_staff', 'system_admin'],
        'attendance.read' => ['hr_staff', 'accountant', 'system_admin', 'management'],
        'attendance.write' => ['hr_staff', 'system_admin'],
        'payroll.read' => ['hr_staff', 'accountant', 'system_admin', 'management'],
        'payroll.write' => ['accountant', 'system_admin'],
        'reports.read' => ['hr_staff', 'accountant', 'system_admin', 'management'],
        'reports.write' => ['hr_staff', 'accountant', 'system_admin', 'management'],
        'admin.read' => ['system_admin'],
        'admin.write' => ['system_admin'],
        'reference.read' => ['hr_staff', 'accountant', 'system_admin', 'management'],
        'reference.write' => ['system_admin'],
    ];

    /**
     * Handle an incoming request.
     * Usage: ->middleware('permission:attendance.write')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Unauthenticated.',
                'errors' => null,
            ], 401);
        }

        $userRole = $user->role ?? ($user['role'] ?? null);
        $allowedRoles = self::PERMISSIONS[$permission] ?? [];

        if (!in_array($userRole, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => "Forbidden. You do not have the '{$permission}' permission.",
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
