<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * يسمح بالمرور فقط إذا كان للمستخدم أحد الأدوار المحددة.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'You are not allowed to access this resource'], 403);
        }

        return $next($request);
    }
}

