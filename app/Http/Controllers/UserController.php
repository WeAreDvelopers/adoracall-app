<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Lista todos os usuários
     * GET /api/users
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filtro por role
        if ($request->has('role')) {
            $query->byRole($request->role);
        }

        // Filtro por status
        if ($request->has('active')) {
            $query->where('active', $request->active === 'true');
        }

        // Busca por nome ou email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($users);
    }

    /**
     * Cria um novo usuário
     * POST /api/users
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,supervisor,operador',
            'active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'active' => $request->active ?? true,
            ]);

            $user->generateApiToken();


            return response()->json([
                'message' => 'Usuário criado com sucesso',
                'user' => $user->makeHidden(['api_token']),
            ], 201);

        } catch (\Exception $e) {
            Log::error("❌ Erro ao criar usuário: " . $e->getMessage());

            return response()->json([
                'error' => 'Erro ao criar usuário',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe detalhes de um usuário
     * GET /api/users/{id}
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        return response()->json([
            'user' => $user->makeHidden(['api_token']),
            'permissions' => $user->getRolePermissions(),
        ]);
    }

    /**
     * Atualiza um usuário
     * PUT /api/users/{id}
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:6',
            'role' => 'sometimes|required|in:admin,supervisor,operador',
            'active' => 'sometimes|nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $data = $request->only(['name', 'email', 'role', 'active']);

            if ($request->has('password') && !empty($request->password)) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);


            return response()->json([
                'message' => 'Usuário atualizado com sucesso',
                'user' => $user->fresh()->makeHidden(['api_token']),
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erro ao atualizar usuário: " . $e->getMessage());

            return response()->json([
                'error' => 'Erro ao atualizar usuário',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deleta um usuário
     * DELETE /api/users/{id}
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        // Não permite deletar a si mesmo
        if ($user->id === auth()->id()) {
            return response()->json([
                'error' => 'Você não pode deletar sua própria conta'
            ], 400);
        }

        try {
            $user->delete();


            return response()->json([
                'message' => 'Usuário deletado com sucesso',
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erro ao deletar usuário: " . $e->getMessage());

            return response()->json([
                'error' => 'Erro ao deletar usuário',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ativa/desativa um usuário
     * PUT /api/users/{id}/toggle
     */
    public function toggle($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        // Não permite desativar a si mesmo
        if ($user->id === auth()->id()) {
            return response()->json([
                'error' => 'Você não pode desativar sua própria conta'
            ], 400);
        }

        try {
            $user->active = !$user->active;
            $user->save();

            $status = $user->active ? 'ativado' : 'desativado';


            return response()->json([
                'message' => "Usuário {$status} com sucesso",
                'user' => $user->makeHidden(['api_token']),
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erro ao alterar status do usuário: " . $e->getMessage());

            return response()->json([
                'error' => 'Erro ao alterar status do usuário',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
