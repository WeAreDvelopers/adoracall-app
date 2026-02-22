<?php

namespace App\Http\Middleware;

use Closure;

class SuperAdminMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['error' => 'Acesso restrito a super administradores'], 403);
        }

        return $next($request);
    }
}
