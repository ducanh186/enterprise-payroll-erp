<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('role:hr_staff,system_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
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

        if (!$userRole || !in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Forbidden. Insufficient role privileges.',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
