<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;

class ProfileController extends Controller
{
    /**
     * Obter usuário a partir do token JWT
     * O token é enviado via JavaScript do localStorage
     */
    private function getUserFromToken()
    {
        // Obter o token do header Authorization
        $authHeader = request()->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        // Extrair o token (remover "Bearer ")
        $token = substr($authHeader, 7);

        try {
            $jwtService = new JwtService();
            $payload = $jwtService->validate($token);

            if (!$payload || !isset($payload->user_id)) {
                return null;
            }

            return User::find($payload->user_id);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Exibir página de profile do usuário logado
     */
    public function show()
    {
        $user = $this->getUserFromToken();

        if (!$user) {
            return redirect()->route('auth.login');
        }

        return view('profile.show', compact('user'));
    }

    /**
     * Página de edição de profile
     */
    public function edit()
    {
        $user = $this->getUserFromToken();

        if (!$user) {
            return redirect()->route('auth.login');
        }

        return view('profile.edit', compact('user'));
    }

    /**
     * Atualizar informações do profile
     */
    public function update()
    {
        $user = $this->getUserFromToken();

        if (!$user) {
            return redirect()->route('auth.login');
        }

        $this->validate(request(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update(request()->only(['name', 'email']));

        return redirect()->route('profile.show')->with('success', 'Perfil atualizado com sucesso!');
    }
}
