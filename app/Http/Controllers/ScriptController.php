<?php

namespace App\Http\Controllers;

use App\Models\Script;
use App\Models\IntencaoScript;
use App\Models\ScriptLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScriptController extends Controller
{
    /**
     * Lista todos os scripts
     * GET /api/scripts
     */
    public function index(Request $request)
    {
        $query = Script::with('intencoes');

        // Filtros
        if ($request->has('tipo')) {
            $query->porTipo($request->tipo);
        }

        if ($request->has('ativo')) {
            $ativo = filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN);
            if ($ativo) {
                $query->ativos();
            } else {
                $query->where('ativo', false);
            }
        }

        // Ordenação
        $orderBy = $request->get('order_by', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($orderBy, $order);

        // Paginação
        $perPage = $request->get('per_page', 15);
        $scripts = $query->paginate($perPage);

        return response()->json($scripts);
    }

    /**
     * Cria um novo script
     * POST /api/scripts
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:cobranca,vendas,retencao,follow_up,pesquisa,outro',
            'agente_id' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'agente_nome' => 'nullable|string|max:255',
            'voz' => 'nullable|string|max:50',
            'idioma' => 'nullable|string|max:10',
            'velocidade_fala' => 'nullable|numeric|min:0.5|max:2.0',
            'horario_inicio' => 'nullable|integer|min:0|max:23',
            'horario_fim' => 'nullable|integer|min:0|max:23',
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'integer|min:1|max:7',
            'excluir_feriados' => 'nullable|boolean',
            'tentativas_max' => 'nullable|integer|min:1|max:10',
            'intervalo_entre_tentativas' => 'nullable|integer|min:60',
            'ativo' => 'nullable|boolean',
            'configuracoes' => 'nullable|array',
            'intencoes' => 'nullable|array',
            'intencoes.*.nome' => 'required|string|max:100',
            'intencoes.*.palavras_chave' => 'required|array',
            'intencoes.*.acao' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $scriptData = $request->except('intencoes');
            $script = Script::create($scriptData);

            // Criar intenções se fornecidas
            if ($request->has('intencoes')) {
                foreach ($request->intencoes as $intencaoData) {
                    $script->intencoes()->create($intencaoData);
                }
            }

            // Log
            ScriptLog::scriptCriado($script, $request->user_id ?? null, $request->ip());

            return response()->json([
                'message' => 'Script criado com sucesso',
                'script' => $script->load('intencoes')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao criar script',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe um script específico
     * GET /api/scripts/{id}
     */
    public function show($id)
    {
        $script = Script::with(['intencoes', 'mailings'])->find($id);

        if (!$script) {
            return response()->json(['error' => 'Script não encontrado'], 404);
        }

        // Estatísticas
        $stats = [
            'total_mailings' => $script->mailings()->count(),
            'mailings_ativos' => $script->mailings()->ativos()->count(),
            'total_intencoes' => $script->intencoes()->count(),
        ];

        return response()->json([
            'script' => $script,
            'stats' => $stats
        ]);
    }

    /**
     * Atualiza um script
     * PUT /api/scripts/{id}
     */
    public function update(Request $request, $id)
    {
        $script = Script::find($id);

        if (!$script) {
            return response()->json(['error' => 'Script não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:255',
            'tipo' => 'sometimes|required|in:cobranca,vendas,retencao,follow_up,pesquisa,outro',
            'agente_id' => 'sometimes|required|string|max:255',
            'descricao' => 'nullable|string',
            'agente_nome' => 'nullable|string|max:255',
            'voz' => 'nullable|string|max:50',
            'idioma' => 'nullable|string|max:10',
            'velocidade_fala' => 'nullable|numeric|min:0.5|max:2.0',
            'horario_inicio' => 'nullable|integer|min:0|max:23',
            'horario_fim' => 'nullable|integer|min:0|max:23',
            'dias_semana' => 'nullable|array',
            'excluir_feriados' => 'nullable|boolean',
            'tentativas_max' => 'nullable|integer|min:1|max:10',
            'intervalo_entre_tentativas' => 'nullable|integer|min:60',
            'ativo' => 'nullable|boolean',
            'configuracoes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $alteracoes = [];
            foreach ($request->all() as $key => $value) {
                if ($script->{$key} != $value) {
                    $alteracoes[$key] = [
                        'de' => $script->{$key},
                        'para' => $value
                    ];
                }
            }

            $script->update($request->all());
            $script->versao += 1;
            $script->save();

            // Log
            if (!empty($alteracoes)) {
                ScriptLog::scriptEditado($script, $alteracoes, $request->user_id ?? null, $request->ip());
            }

            return response()->json([
                'message' => 'Script atualizado com sucesso',
                'script' => $script->load('intencoes')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao atualizar script',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove um script
     * DELETE /api/scripts/{id}
     */
    public function destroy($id)
    {
        $script = Script::find($id);

        if (!$script) {
            return response()->json(['error' => 'Script não encontrado'], 404);
        }

        // Verifica se há mailings ativos usando este script
        $mailings_ativos = $script->mailings()->ativos()->count();
        if ($mailings_ativos > 0) {
            return response()->json([
                'error' => 'Não é possível excluir um script com mailings ativos',
                'mailings_ativos' => $mailings_ativos
            ], 400);
        }

        try {
            $script->delete(); // Soft delete

            return response()->json([
                'message' => 'Script removido com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao remover script',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clona um script
     * POST /api/scripts/{id}/clonar
     */
    public function clonar(Request $request, $id)
    {
        $script = Script::find($id);

        if (!$script) {
            return response()->json(['error' => 'Script não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $novoNome = $request->get('nome', null);
            $clone = $script->clonar($novoNome);

            return response()->json([
                'message' => 'Script clonado com sucesso',
                'script' => $clone->load('intencoes')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao clonar script',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Publica uma nova versão do script
     * POST /api/scripts/{id}/publicar
     */
    public function publicar($id)
    {
        $script = Script::find($id);

        if (!$script) {
            return response()->json(['error' => 'Script não encontrado'], 404);
        }

        try {
            $script->versao += 1;
            $script->save();

            return response()->json([
                'message' => 'Nova versão publicada com sucesso',
                'versao' => $script->versao
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao publicar script',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista intenções de um script
     * GET /api/scripts/{id}/intencoes
     */
    public function intencoes($id)
    {
        $script = Script::find($id);

        if (!$script) {
            return response()->json(['error' => 'Script não encontrado'], 404);
        }

        $intencoes = $script->intencoes()->ordenadoPorPrioridade()->get();

        return response()->json([
            'script_id' => $script->id,
            'script_nome' => $script->nome,
            'intencoes' => $intencoes
        ]);
    }

    /**
     * Adiciona uma intenção ao script
     * POST /api/scripts/{id}/intencoes
     */
    public function adicionarIntencao(Request $request, $id)
    {
        $script = Script::find($id);

        if (!$script) {
            return response()->json(['error' => 'Script não encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string',
            'palavras_chave' => 'required|array',
            'regex_patterns' => 'nullable|array',
            'acao' => 'required|string|max:100',
            'parametros_acao' => 'nullable|array',
            'confianca_minima' => 'nullable|numeric|min:0|max:1',
            'prioridade' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $intencao = $script->intencoes()->create($request->all());

            return response()->json([
                'message' => 'Intenção adicionada com sucesso',
                'intencao' => $intencao
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao adicionar intenção',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza uma intenção
     * PUT /api/scripts/{id}/intencoes/{intencaoId}
     */
    public function atualizarIntencao(Request $request, $id, $intencaoId)
    {
        $intencao = IntencaoScript::where('script_id', $id)
            ->where('id', $intencaoId)
            ->first();

        if (!$intencao) {
            return response()->json(['error' => 'Intenção não encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'sometimes|required|string|max:100',
            'descricao' => 'nullable|string',
            'palavras_chave' => 'sometimes|required|array',
            'regex_patterns' => 'nullable|array',
            'acao' => 'sometimes|required|string|max:100',
            'parametros_acao' => 'nullable|array',
            'confianca_minima' => 'nullable|numeric|min:0|max:1',
            'prioridade' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $intencao->update($request->all());

            return response()->json([
                'message' => 'Intenção atualizada com sucesso',
                'intencao' => $intencao
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao atualizar intenção',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove uma intenção
     * DELETE /api/scripts/{id}/intencoes/{intencaoId}
     */
    public function removerIntencao($id, $intencaoId)
    {
        $intencao = IntencaoScript::where('script_id', $id)
            ->where('id', $intencaoId)
            ->first();

        if (!$intencao) {
            return response()->json(['error' => 'Intenção não encontrada'], 404);
        }

        try {
            $intencao->delete();

            return response()->json([
                'message' => 'Intenção removida com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao remover intenção',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
