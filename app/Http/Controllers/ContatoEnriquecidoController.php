<?php
namespace App\Http\Controllers;

use App\Models\Contato;
use App\Services\DadosContatoService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Controller para Contatos Enriquecidos
 *
 * Fornece endpoints que retornam dados completos e enriquecidos do contato
 * para serem consumidos pela URA e outros sistemas
 */
class ContatoEnriquecidoController extends Controller
{
    protected $dadosService;

    public function __construct(DadosContatoService $dadosService)
    {
        $this->dadosService = $dadosService;
    }

    /**
     * Obtém dados enriquecidos de um contato
     *
     * GET /api/contatos/{id}/enriquecido
     *
     * Retorna:
     * - Dados pessoais
     * - Dados da dívida com juros e multa
     * - Histórico de tentativas
     * - Propostas disponíveis
     * - Alertas e restrições
     * - Instruções para URA
     */
    public function obterEnriquecido($id)
    {
        try {
            $dados = $this->dadosService->obterDadosEnriquecidos($id);

            return response()->json([
                'sucesso'   => true,
                'dados'     => $dados,
                'timestamp' => Carbon::now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro'    => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Obtém dados enriquecidos por telefone
     *
     * GET /api/contatos/telefone/{telefone}/enriquecido
     *
     * Útil quando a URA recebe uma ligação e precisa buscar dados pelo telefone
     */
    public function obterEnriquecidoPorTelefone($telefone)
    {
        try {
            $contato = Contato::where('telefone', $telefone)
                ->orWhere('telefone', 'like', '%' . preg_replace('/[^0-9]/', '', $telefone) . '%')
                ->first();

            if (! $contato) {
                return response()->json([
                    'sucesso' => false,
                    'erro'    => 'Contato não encontrado com este telefone',
                ], 404);
            }

            $dados = $this->dadosService->obterDadosEnriquecidos($contato->id);

            return response()->json([
                'sucesso'   => true,
                'dados'     => $dados,
                'timestamp' => Carbon::now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro'    => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtém apenas os dados da dívida (simplificado)
     *
     * GET /api/contatos/{id}/divida
     *
     * Retorna apenas:
     * - Valores (original, juros, multa, total)
     * - Propostas disponíveis
     * - Status da dívida
     */
    public function obterDivida($id)
    {
        try {
            $contato = Contato::find($id);

            if (! $contato) {
                return response()->json([
                    'sucesso' => false,
                    'erro'    => 'Contato não encontrado',
                ], 404);
            }

            $dadosCompletos = $this->dadosService->obterDadosEnriquecidos($id);

            return response()->json([
                'sucesso'    => true,
                'contato_id' => $id,
                'nome'       => $dadosCompletos['contato']['nome_completo'],
                'divida'     => $dadosCompletos['divida'],
                'propostas'  => $dadosCompletos['propostas'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro'    => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtém apenas as propostas disponíveis para um contato
     *
     * GET /api/contatos/{id}/propostas
     */
    public function obterPropostas($id)
    {
        try {
            $contato = Contato::find($id);

            if (! $contato) {
                return response()->json([
                    'sucesso' => false,
                    'erro'    => 'Contato não encontrado',
                ], 404);
            }

            $dadosCompletos = $this->dadosService->obterDadosEnriquecidos($id);

            return response()->json([
                'sucesso'    => true,
                'contato_id' => $id,
                'nome'       => $dadosCompletos['contato']['nome_completo'],
                'propostas'  => $dadosCompletos['propostas'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro'    => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtém instruções para a URA sobre como abordar este contato
     *
     * GET /api/contatos/{id}/instrucoes-ura
     */
    public function obterInstrucoesURA($id)
    {
        try {
            $contato = Contato::find($id);

            if (! $contato) {
                return response()->json([
                    'sucesso' => false,
                    'erro'    => 'Contato não encontrado',
                ], 404);
            }

            $dadosCompletos = $this->dadosService->obterDadosEnriquecidos($id);

            return response()->json([
                'sucesso'      => true,
                'contato_id'   => $id,
                'nome'         => $dadosCompletos['contato']['nome_completo'],
                'alertas'      => $dadosCompletos['alertas'],
                'instrucoes'   => $dadosCompletos['instrucoes_ura'],
                'dados_resumo' => [
                    'tentativas'    => $dadosCompletos['historico']['total_tentativas'],
                    'valor_divida'  => $dadosCompletos['divida']['valor_atual'],
                    'dias_atrasado' => $dadosCompletos['divida']['dias_atrasado'],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro'    => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enriquece um contato (atualiza seu status e timestamps)
     *
     * POST /api/contatos/{id}/enriquecer
     *
     * Marcado como "pronto para discagem" com dados já carregados
     */
    public function enriquecerContato($id)
    {
        try {
            $contato = Contato::find($id);

            if (! $contato) {
                return response()->json([
                    'sucesso' => false,
                    'erro'    => 'Contato não encontrado',
                ], 404);
            }

            // Buscar dados enriquecidos (valida se API está disponível)
            $dados = $this->dadosService->obterDadosEnriquecidos($id);

            // Atualizar status e timestamp
            $contato->status = 'enviado_a_discagem';
            $contato->save();

            return response()->json([
                'sucesso'      => true,
                'mensagem'     => 'Contato enriquecido e pronto para discagem',
                'contato_id'   => $id,
                'status_novo'  => 'enviado_a_discagem',
                'dados_resumo' => [
                    'nome'                  => $dados['contato']['nome_completo'],
                    'valor_divida'          => $dados['divida']['valor_atual'],
                    'propostas_disponiveis' => $dados['propostas']['quantidade'],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'sucesso' => false,
                'erro'    => 'Falha ao enriquecer contato: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enriquece múltiplos contatos em lote
     *
     * POST /api/contatos/lote/enriquecer
     *
     * Body:
     * {
     *   "contato_ids": [1, 2, 3, 4, 5]
     * }
     */
    public function enriquecerLote(Request $request)
    {
        $contatoIds = $request->input('contato_ids', []);

        if (empty($contatoIds)) {
            return response()->json([
                'sucesso' => false,
                'erro'    => 'Nenhum ID de contato fornecido',
            ], 400);
        }

        $resultados = [
            'total'    => count($contatoIds),
            'sucesso'  => 0,
            'falhas'   => 0,
            'detalhes' => [],
        ];

        foreach ($contatoIds as $id) {
            try {
                $contato = Contato::find($id);

                if (! $contato) {
                    $resultados['falhas']++;
                    $resultados['detalhes'][] = [
                        'contato_id' => $id,
                        'status'     => 'erro',
                        'mensagem'   => 'Contato não encontrado',
                    ];
                    continue;
                }

                // Buscar dados (valida disponibilidade)
                $dados = $this->dadosService->obterDadosEnriquecidos($id);

                // Atualizar status
                $contato->status = 'enviado_a_discagem';
                $contato->save();

                $resultados['sucesso']++;
                $resultados['detalhes'][] = [
                    'contato_id' => $id,
                    'status'     => 'sucesso',
                    'nome'       => $dados['contato']['nome_completo'],
                ];

            } catch (\Exception $e) {
                $resultados['falhas']++;
                $resultados['detalhes'][] = [
                    'contato_id' => $id,
                    'status'     => 'erro',
                    'mensagem'   => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'sucesso'    => $resultados['falhas'] === 0,
            'resultados' => $resultados,
        ]);
    }
}
