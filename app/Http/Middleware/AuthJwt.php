<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\JwtService;
use App\Models\User;

class AuthJwt
{
    /**
     * Valida JWT tokens em requisições
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Tentar obter o token do header Authorization
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return response()->json(['error' => 'Token não fornecido'], 401);
        }

        // Validar o token
        $jwtService = new JwtService();
        $payload = $jwtService->validate($token);

        if (!$payload) {
            return response()->json(['error' => 'Token inválido ou expirado'], 401);
        }

        // Se é um token interno, permitir sem verificar usuário
        if (isset($payload->internal) && $payload->internal === true) {
            $request->jwt_payload = $payload;
            $request->is_internal_call = true;
            return $next($request);
        }

        // Para tokens regulares, buscar o usuário pelo ID no payload
        if (!isset($payload->user_id)) {
            return response()->json(['error' => 'Token não contém user_id'], 401);
        }

        $user = User::find($payload->user_id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 401);
        }

        // Armazenar o usuário e o payload no request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $request->jwt_payload = $payload;

        // Bindar empresa_id no container para Global Scopes
        if ($user->isSuperAdmin()) {
            // Super-admin pode impersonar empresa via header X-Empresa-Id
            $empresaIdHeader = $request->header('X-Empresa-Id');
            if ($empresaIdHeader) {
                app()->instance('empresa_id', (int) $empresaIdHeader);
            }
            // Se não enviou header, não binda → super-admin vê tudo
        } else {
            // Usuário normal: sempre filtrado pela sua empresa
            if ($user->empresa_id) {
                app()->instance('empresa_id', $user->empresa_id);
            }
        }

        return $next($request);
    }

    /**
     * Extrair o token do header Authorization
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    private function getTokenFromRequest($request): ?string
    {
        $authHeader = $request->header('Authorization');

        if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
            return substr($authHeader, 7);
        }

        return null;
    }
}
