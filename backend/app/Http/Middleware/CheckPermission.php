<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
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

        if (!method_exists($user, 'loadMissing') || !method_exists($user, 'hasPermission')) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => "Forbidden. You do not have the '{$permission}' permission.",
                'errors' => null,
            ], 403);
        }

        $user->loadMissing('roles.permissions');

        if (!$user->hasPermission($permission)) {
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
