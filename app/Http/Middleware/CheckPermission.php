<?php

namespace App\Http\Middleware;

use Closure;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle($request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Não autenticado'
            ], 401);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'error' => 'Acesso negado. Você não tem permissão para esta ação.',
                'required_permission' => $permission,
                'your_role' => $user->role,
            ], 403);
        }

        return $next($request);
    }
}
