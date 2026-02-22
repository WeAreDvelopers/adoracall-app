<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\User;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmpresaController extends Controller
{
    // ========== VIEWS ==========

    public function indexView()
    {
        return view('admin.empresas.index');
    }

    public function criarView()
    {
        return view('admin.empresas.criar');
    }

    public function editarView($id)
    {
        return view('admin.empresas.editar', ['empresaId' => $id]);
    }

    public function usuariosView()
    {
        return view('admin.usuarios.index');
    }

    // ========== API ==========

    public function index(Request $request)
    {
        $query = Empresa::withoutGlobalScopes()->withCount('users');

        if ($request->has('busca')) {
            $busca = $request->input('busca');
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'ilike', "%{$busca}%")
                  ->orWhere('cnpj', 'like', "%{$busca}%")
                  ->orWhere('email', 'ilike', "%{$busca}%");
            });
        }

        if ($request->has('ativa')) {
            $query->where('ativa', $request->boolean('ativa'));
        }

        $empresas = $query->orderBy('nome')->get();

        return ApiResponseService::success(['empresas' => $empresas]);
    }

    public function show($id)
    {
        $empresa = Empresa::withoutGlobalScopes()
            ->withCount('users')
            ->findOrFail($id);

        return ApiResponseService::success(['empresa' => $empresa]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:18|unique:empresas,cnpj',
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'nullable|string',
            'max_usuarios' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->toArray());
        }

        $empresa = Empresa::create([
            'uuid' => (string) Str::uuid(),
            'nome' => $request->input('nome'),
            'slug' => Str::slug($request->input('nome')),
            'cnpj' => $request->input('cnpj'),
            'email' => $request->input('email'),
            'telefone' => $request->input('telefone'),
            'endereco' => $request->input('endereco'),
            'max_usuarios' => $request->input('max_usuarios', 10),
            'ativa' => true,
        ]);

        return ApiResponseService::success(['empresa' => $empresa], 'Empresa criada com sucesso', 201);
    }

    public function update(Request $request, $id)
    {
        $empresa = Empresa::withoutGlobalScopes()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|string|max:255',
            'cnpj' => 'nullable|string|max:18|unique:empresas,cnpj,' . $id,
            'email' => 'nullable|email|max:255',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'nullable|string',
            'ativa' => 'sometimes|boolean',
            'max_usuarios' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->toArray());
        }

        $empresa->update($request->only([
            'nome', 'cnpj', 'email', 'telefone', 'endereco', 'ativa', 'max_usuarios'
        ]));

        if ($request->has('nome')) {
            $empresa->slug = Str::slug($request->input('nome'));
            $empresa->save();
        }

        return ApiResponseService::success(['empresa' => $empresa], 'Empresa atualizada com sucesso');
    }

    public function destroy($id)
    {
        $empresa = Empresa::withoutGlobalScopes()->findOrFail($id);

        // Não permitir excluir empresa padrão
        if ($empresa->slug === 'empresa-padrao') {
            return ApiResponseService::forbidden('Não é possível excluir a empresa padrão');
        }

        // Soft delete - desativa
        $empresa->ativa = false;
        $empresa->save();
        $empresa->delete();

        return ApiResponseService::success(null, 'Empresa desativada com sucesso');
    }

    // ========== USUÁRIOS POR EMPRESA ==========

    public function listarUsuarios(Request $request)
    {
        $query = User::query();

        if ($request->has('empresa_id')) {
            $query->where('empresa_id', $request->input('empresa_id'));
        }

        if ($request->has('busca')) {
            $busca = $request->input('busca');
            $query->where(function ($q) use ($busca) {
                $q->where('name', 'ilike', "%{$busca}%")
                  ->orWhere('email', 'ilike', "%{$busca}%");
            });
        }

        $usuarios = $query->with('empresa:id,nome')->orderBy('name')->get([
            'id', 'name', 'email', 'role', 'active', 'empresa_id', 'created_at'
        ]);

        return ApiResponseService::success(['usuarios' => $usuarios]);
    }

    public function moverUsuario(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'empresa_id' => 'required|exists:empresas,id',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->toArray());
        }

        $user = User::findOrFail($userId);
        $user->empresa_id = $request->input('empresa_id');
        $user->save();

        return ApiResponseService::success(['user' => $user->load('empresa')], 'Usuário movido com sucesso');
    }
}
