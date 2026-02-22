<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;

class AuthenticateWeb
{
    /**
     * Verifica autenticação via token API
     * Para rotas WEB (HTML), redireciona para login se não autenticado
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = null;

        // Tentar obter o token de várias fontes (em ordem de prioridade)

        // 1. Header Authorization (Bearer token)
        if ($request->hasHeader('Authorization')) {
            $authHeader = $request->header('Authorization');
            if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        // 2. Cookie HTTP-Only (mais seguro)
        if (!$token && $request->hasCookie('api_token')) {
            $token = $request->cookie('api_token');
        }

        // 3. Query parameter (fallback, menos seguro)
        if (!$token && $request->has('token')) {
            $token = $request->input('token');
        }

        // Validar o token
        if (!$token) {
            // Redirecionar para login (HTTP 302 Found)
            return response('', 302)
                ->header('Location', '/login');
        }

        // Buscar o usuário com esse token
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            // Redirecionar para login se token inválido (HTTP 302 Found)
            return response('', 302)
                ->header('Location', '/login');
        }

        // Armazenar o usuário no request para uso posterior
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}
