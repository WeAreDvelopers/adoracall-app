<?php

namespace App\Http\Controllers;

use App\Models\TipoPublico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TipoPublicoController extends Controller
{
    /**
     * Lista todos os tipos de público da empresa
     * GET /api/tipos-publico
     */
    public function index(Request $request)
    {
        $query = TipoPublico::ordenados();

        if ($request->has('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        $tipos = $query->get();

        return response()->json([
            'tipos' => $tipos,
            'total' => $tipos->count(),
        ]);
    }

    /**
     * Cria um novo tipo de público
     * POST /api/tipos-publico
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome'                     => 'required|string|max:150',
            'subtitulo'                => 'nullable|string|max:200',
            'descricao'                => 'nullable|string|max:500',
            'cor'                      => 'nullable|string|max:20',
            'icone'                    => 'nullable|string|max:5',
            'max_tentativas'           => 'nullable|integer|min:1|max:20',
            'dias_estimados'           => 'nullable|integer|min:1|max:30',
            'velocidade_contatos_hora' => 'nullable|integer|min:5|max:500',
            'intervalo_retry'          => 'nullable|integer|min:5|max:1440',
            'prioridade'               => 'nullable|in:baixa,normal,alta,urgente',
            'faixa_velocidade'         => 'nullable|string|max:20',
            'insight'                  => 'nullable|string|max:500',
            'ordem'                    => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dados = $validator->validated();
        $dados['slug'] = Str::slug($dados['nome'], '_');

        // Verificar slug duplicado na empresa
        $empresaId = app()->bound('empresa_id') ? app('empresa_id') : null;
        if ($empresaId) {
            $exists = TipoPublico::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                ->where('slug', $dados['slug'])
                ->exists();

            if ($exists) {
                return response()->json(['errors' => ['nome' => ['Já existe um tipo de público com este nome.']]], 422);
            }
        }

        // Definir ordem automática se não fornecida
        if (!isset($dados['ordem'])) {
            $dados['ordem'] = TipoPublico::max('ordem') + 1;
        }

        $tipo = TipoPublico::create($dados);

        return response()->json([
            'message' => 'Tipo de público criado com sucesso',
            'tipo'    => $tipo,
        ], 201);
    }

    /**
     * Exibe um tipo de público
     * GET /api/tipos-publico/{id}
     */
    public function show($id)
    {
        $tipo = TipoPublico::find($id);

        if (!$tipo) {
            return response()->json(['error' => 'Tipo de público não encontrado'], 404);
        }

        return response()->json(['tipo' => $tipo]);
    }

    /**
     * Atualiza um tipo de público
     * PUT /api/tipos-publico/{id}
     */
    public function update(Request $request, $id)
    {
        $tipo = TipoPublico::find($id);

        if (!$tipo) {
            return response()->json(['error' => 'Tipo de público não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome'                     => 'sometimes|required|string|max:150',
            'subtitulo'                => 'nullable|string|max:200',
            'descricao'                => 'nullable|string|max:500',
            'cor'                      => 'nullable|string|max:20',
            'icone'                    => 'nullable|string|max:5',
            'max_tentativas'           => 'nullable|integer|min:1|max:20',
            'dias_estimados'           => 'nullable|integer|min:1|max:30',
            'velocidade_contatos_hora' => 'nullable|integer|min:5|max:500',
            'intervalo_retry'          => 'nullable|integer|min:5|max:1440',
            'prioridade'               => 'nullable|in:baixa,normal,alta,urgente',
            'faixa_velocidade'         => 'nullable|string|max:20',
            'insight'                  => 'nullable|string|max:500',
            'ordem'                    => 'nullable|integer|min:0',
            'ativo'                    => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dados = $validator->validated();

        // Atualizar slug se nome mudou
        if (isset($dados['nome']) && $dados['nome'] !== $tipo->nome) {
            $novoSlug = Str::slug($dados['nome'], '_');
            $empresaId = app()->bound('empresa_id') ? app('empresa_id') : $tipo->empresa_id;

            $exists = TipoPublico::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                ->where('slug', $novoSlug)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json(['errors' => ['nome' => ['Já existe um tipo de público com este nome.']]], 422);
            }

            $dados['slug'] = $novoSlug;
        }

        $tipo->update($dados);

        return response()->json([
            'message' => 'Tipo de público atualizado com sucesso',
            'tipo'    => $tipo->fresh(),
        ]);
    }

    /**
     * Remove um tipo de público (soft delete)
     * DELETE /api/tipos-publico/{id}
     */
    public function destroy($id)
    {
        $tipo = TipoPublico::find($id);

        if (!$tipo) {
            return response()->json(['error' => 'Tipo de público não encontrado'], 404);
        }

        $tipo->delete();

        return response()->json(['message' => 'Tipo de público removido com sucesso']);
    }

    /**
     * Reordena os tipos de público
     * PUT /api/tipos-publico/reordenar
     */
    public function reordenar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ordem' => 'required|array',
            'ordem.*.id' => 'required|integer',
            'ordem.*.ordem' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        foreach ($request->ordem as $item) {
            TipoPublico::where('id', $item['id'])->update(['ordem' => $item['ordem']]);
        }

        return response()->json(['message' => 'Ordem atualizada com sucesso']);
    }
}
