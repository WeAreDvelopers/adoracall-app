<?php

namespace App\Http\Controllers;

use App\Models\Acordo;
use App\Models\Contato;
use App\Models\Ligacao;
use App\Models\PropostaPagamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardAcordosController extends Controller
{
    /**
     * GET /dashboard/acordos
     * View do dashboard consolidado
     */
    public function index()
    {
        return view('dashboard.acordos-consolidado');
    }

    /**
     * GET /api/dashboard/stats
     * Estatísticas resumidas do dashboard
     */
    public function getStats(Request $request)
    {
        try {
            $dias = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($dias);

            // Total de ligações (tentativas + atendidas)
            $totalLigacoes = Ligacao::where('created_at', '>=', $startDate)->count();

            // Ligações bem-sucedidas (atendidas)
            $ligacoesAtendidas = Ligacao::where('foi_atendida', true)
                ->where('created_at', '>=', $startDate)
                ->count();

            // Acordos confirmados (ligações com acordo_id)
            $acordosConfirmados = Ligacao::where('foi_atendida', true)
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('acordo_id')
                ->count();

            // Ligações falhadas/não atendidas
            $ligacoesFalhadas = Ligacao::where('foi_atendida', false)
                ->where('created_at', '>=', $startDate)
                ->count();

            // Ligações pendentes (em fila, processando, etc)
            $ligacoesPendentes = Ligacao::whereIn('status', ['pending', 'processing', 'awaiting_callback'])
                ->count();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total' => $totalLigacoes,
                    'success' => $ligacoesAtendidas,
                    'failed' => $ligacoesFalhadas,
                    'pending' => $ligacoesPendentes,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Erro ao obter dashboard stats', [
                'erro'  => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/atividades
     * Atividades recentes (ligações e acordos)
     */
    public function getAtividades(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $dias = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($dias);

            $atividades = [];

            // Ligações recentes
            $ligacoes = Ligacao::where('created_at', '>=', $startDate)
                ->with(['contato.mailing'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($ligacoes as $ligacao) {
                $status = $ligacao->foi_atendida ? 'success' : 'warning';
                $descricao = $ligacao->foi_atendida
                    ? "Ligação atendida com " . ($ligacao->contato->nome ?? 'Unknown')
                    : "Ligação não atendida";

                $atividades[] = [
                    'created_at' => $ligacao->created_at,
                    'campanha_nome' => $ligacao->contato->mailing->nome ?? 'Sem Campanha',
                    'tipo' => 'ligacao',
                    'status' => $status,
                    'descricao' => $descricao,
                ];
            }

            // Acordos recentes
            try {
                $acordos = Acordo::where('created_at', '>=', $startDate)
                    ->with('contato')
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();

                foreach ($acordos as $acordo) {
                    $statusMap = [
                        'pendente_pagamento' => 'warning',
                        'pago' => 'success',
                        'vencido' => 'error',
                        'concluido' => 'success',
                    ];

                    $atividades[] = [
                        'created_at' => $acordo->created_at,
                        'campanha_nome' => 'Acordo',
                        'tipo' => 'acordo',
                        'status' => $statusMap[$acordo->status] ?? 'warning',
                        'descricao' => "Acordo de {$acordo->valor_acordado} com " . ($acordo->contato->nome ?? 'Unknown'),
                    ];
                }
            } catch (\Exception $e) {
                Log::debug('Erro ao carregar acordos (pode estar vazio)', ['erro' => $e->getMessage()]);
            }

            // Ordenar por data decrescente
            usort($atividades, function ($a, $b) {
                return $b['created_at']->timestamp <=> $a['created_at']->timestamp;
            });

            // Limitar ao total solicitado
            $atividades = array_slice($atividades, 0, $limit);

            return response()->json([
                'success' => true,
                'data' => $atividades,
            ]);

        } catch (\Throwable $e) {
            Log::error('Erro ao obter atividades', [
                'erro'  => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/test
     * Teste simplificado do dashboard
     */
    public function testSimple()
    {
        try {
            Log::info('🧪 Iniciando teste simples');

            return response()->json([
                'success' => true,
                'message' => 'Teste OK',
                'ligacoes_total' => Ligacao::count(),
                'acordos_total' => Acordo::count(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro no teste: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/consolidated
     * Dados consolidados de ligações e acordos
     */
    public function getConsolidated(Request $request)
    {
        try {
            $dias = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($dias);

            // Obter ligações
            $ligacoes = $this->obterStatLigacoes($startDate);

            // Obter taxa de conversão
            $conversao = $this->obterTaxaConversao($startDate);

            // Obter acordos com valores calculados corretamente
            $acordos = $this->obterStatAcordos($startDate);

            // Obter valores consolidados
            $valor_consolidado = $this->obterValoresConsolidados($startDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'periodo_dias' => $dias,
                    'periodo_inicio' => $startDate->toDateString(),
                    'periodo_fim' => Carbon::now()->toDateString(),
                    'ligacoes' => $ligacoes,
                    'acordos' => $acordos,
                    'conversao' => $conversao,
                    'valor_consolidado' => $valor_consolidado,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Erro ao obter dashboard consolidado', [
                'erro' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/agreements
     * Estatísticas de acordos
     */
    public function getAgreementStats(Request $request)
    {
        try {
            $dias = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($dias);

            $stats = [
                'resumo'              => $this->obterResumoAcordos($startDate),
                'por_tipo'            => $this->obterAcordosPorTipo($startDate),
                'por_status'          => $this->obterAcordosPorStatus($startDate),
                'timeline'            => $this->obterTimelineAcordos($startDate),
                'ultimos_acordos'     => $this->obterUltimosAcordos(10),
            ];

            return response()->json([
                'success' => true,
                'data'    => $stats,
            ]);

        } catch (\Throwable $e) {
            Log::error('Erro ao obter estatísticas de acordos', [
                'erro'  => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/dashboard/timeline
     * Timeline de eventos (ligações + acordos)
     */
    public function getTimeline(Request $request)
    {
        try {
            $dias = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($dias);

            $timeline = [];

            // Ligações atendidas
            $ligacoes = Ligacao::where('foi_atendida', true)
                ->where('created_at', '>=', $startDate)
                ->with('contato:id,telefone')
                ->get(['id', 'contato_id', 'created_at', 'acordo_id']);

            foreach ($ligacoes as $ligacao) {
                $timeline[] = [
                    'tipo'     => 'ligacao',
                    'status'   => $ligacao->acordo_id ? 'sucesso' : 'pendente',
                    'descricao' => "Ligação atendida: " . ($ligacao->contato->telefone ?? 'Unknown'),
                    'data'     => $ligacao->created_at,
                ];
            }

            // Acordos
            $acordos = Acordo::where('created_at', '>=', $startDate)
                ->get(['id', 'uuid', 'status', 'created_at', 'contato_id']);

            foreach ($acordos as $acordo) {
                $timeline[] = [
                    'tipo'      => 'acordo',
                    'status'    => $acordo->status,
                    'descricao' => "Acordo formalizado: {$acordo->uuid}",
                    'data'      => $acordo->created_at,
                ];
            }

            // Ordenar por data decrescente
            usort($timeline, function ($a, $b) {
                return $b['data']->timestamp <=> $a['data']->timestamp;
            });

            return response()->json([
                'success' => true,
                'data'    => array_slice($timeline, 0, 50),
            ]);

        } catch (\Throwable $e) {
            Log::error('Erro ao obter timeline', [
                'erro'  => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ========================================
    // MÉTODOS PRIVADOS
    // ========================================

    /**
     * Obter estatísticas de ligações
     * Conta apenas LIGAÇÕES ATENDIDAS (foi_atendida = true)
     */
    private function obterStatLigacoes($startDate)
    {
        $totalAtendidas = Ligacao::where('foi_atendida', true)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalNaoAtendidas = Ligacao::where('foi_atendida', false)
            ->where('created_at', '>=', $startDate)
            ->count();

        $totalTentativas = $totalAtendidas + $totalNaoAtendidas;

        $duracaoMedia = Ligacao::where('foi_atendida', true)
            ->where('created_at', '>=', $startDate)
            ->avg('duracao') ?? 0;

        $duracaoTotal = Ligacao::where('foi_atendida', true)
            ->where('created_at', '>=', $startDate)
            ->sum('duracao') ?? 0;

        return [
            'total_tentativas'     => $totalTentativas,
            'total_atendidas'      => $totalAtendidas,
            'total_nao_atendidas'  => $totalNaoAtendidas,
            'taxa_atendimento'     => $totalTentativas > 0 ? round(($totalAtendidas / $totalTentativas) * 100, 2) : 0,
            'duracao_media'        => (int) $duracaoMedia,
            'duracao_total'        => (int) $duracaoTotal,
        ];
    }

    /**
     * Obter estatísticas de acordos
     */
    private function obterStatAcordos($startDate)
    {
        $totalAcordos = Acordo::where('created_at', '>=', $startDate)->count();

        $acordosPorStatus = Acordo::where('created_at', '>=', $startDate)
            ->groupBy('status')
            ->selectRaw('status, count(*) as total')
            ->pluck('total', 'status')
            ->toArray();

        // Somar valores das propostas associadas aos acordos
        $valorTotal = 0;
        try {
            // JOIN entre acordos e propostas_pagamento para pegar valores corretos
            $valorTotal = Acordo::where('acordos.created_at', '>=', $startDate)
                ->join('propostas_pagamento', 'acordos.proposta_id', '=', 'propostas_pagamento.id')
                ->sum('propostas_pagamento.valor_proposta_numerico') ?? 0;
        } catch (\Exception $e) {
            Log::warning('Erro ao processar valores de acordos', ['erro' => $e->getMessage()]);
            $valorTotal = 0;
        }

        $valorMedio = $totalAcordos > 0 ? $valorTotal / $totalAcordos : 0;

        return [
            'total_acordos'    => $totalAcordos,
            'por_status'       => $acordosPorStatus,
            'valor_total'      => round($valorTotal, 2),
            'valor_medio'      => round($valorMedio, 2),
        ];
    }

    /**
     * Obter taxa de conversão
     * IMPORTANTE: Só conta ligações atendidas na base
     * Taxa = acordos_firmados / ligações_atendidas
     */
    private function obterTaxaConversao($startDate)
    {
        // Ligações ATENDIDAS (não contamos não atendidas no denominador)
        $ligacoesAtendidas = Ligacao::where('foi_atendida', true)
            ->where('created_at', '>=', $startDate)
            ->count();

        // Acordos firmados = ligações que têm acordo_id preenchido
        $acordosFirmados = Ligacao::where('foi_atendida', true)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('acordo_id')
            ->count();

        $taxaConversao = $ligacoesAtendidas > 0
            ? round(($acordosFirmados / $ligacoesAtendidas) * 100, 2)
            : 0;

        return [
            'ligacoes_atendidas'   => $ligacoesAtendidas,
            'acordos_confirmados'  => $acordosFirmados,
            'taxa_conversao_pct'   => $taxaConversao,
            'metrica'              => "{$acordosFirmados} / {$ligacoesAtendidas} = {$taxaConversao}%",
        ];
    }

    /**
     * Obter valores consolidados
     */
    private function obterValoresConsolidados($startDate)
    {
        $debitosProcessados = Contato::where('updated_at', '>=', $startDate)
            ->sum('valor_debito') ?? 0;

        // Somar valores acordados (valor_proposta_numerico das propostas conectadas aos acordos)
        $acordosRealizados = 0;
        try {
            $acordosRealizados = Acordo::where('acordos.created_at', '>=', $startDate)
                ->join('propostas_pagamento', 'acordos.proposta_id', '=', 'propostas_pagamento.id')
                ->sum('propostas_pagamento.valor_proposta_numerico') ?? 0;
        } catch (\Exception $e) {
            Log::warning('Erro ao processar valores de acordos', ['erro' => $e->getMessage()]);
            $acordosRealizados = 0;
        }

        // Somar descontos (diferença entre valor_original_numerico e valor_proposta_numerico)
        $desconto_total = 0;
        try {
            $desconto_total = PropostaPagamento::where('created_at', '>=', $startDate)
                ->selectRaw('SUM(valor_original_numerico - valor_proposta_numerico) as desconto')
                ->first()
                ->desconto ?? 0;
        } catch (\Exception $e) {
            Log::warning('Erro ao processar descontos', ['erro' => $e->getMessage()]);
            $desconto_total = 0;
        }

        return [
            'debitos_processados'  => round($debitosProcessados, 2),
            'acordos_realizados'   => round($acordosRealizados, 2),
            'desconto_concedido'   => round($desconto_total, 2),
            'economia_cliente'     => round($desconto_total, 2),
        ];
    }

    /**
     * Obter resumo de acordos
     */
    private function obterResumoAcordos($startDate)
    {
        return Acordo::where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN status = "pendente_pagamento" THEN 1 END) as pendentes,
                COUNT(CASE WHEN status = "pago" THEN 1 END) as pagos,
                COUNT(CASE WHEN status = "vencido" THEN 1 END) as vencidos
            ')
            ->first()
            ->toArray();
    }

    /**
     * Obter acordos por tipo (baseado em parcelas)
     */
    private function obterAcordosPorTipo($startDate)
    {
        $acordos = Acordo::where('created_at', '>=', $startDate)
            ->get(['parcelas_acordadas', 'valor_acordado']);

        $groupedByParcelas = [];
        foreach ($acordos as $acordo) {
            $parcelas = $acordo->parcelas_acordadas;
            if (!isset($groupedByParcelas[$parcelas])) {
                $groupedByParcelas[$parcelas] = [
                    'total' => 0,
                    'valor_total' => 0,
                ];
            }

            $groupedByParcelas[$parcelas]['total']++;

            // Processar valor
            if ($acordo->valor_acordado) {
                $valor = str_replace(['reais', 'R$', ' '], '', $acordo->valor_acordado);
                $valor = str_replace(',', '.', str_replace('.', '', $valor));
                if (is_numeric($valor)) {
                    $groupedByParcelas[$parcelas]['valor_total'] += (float) $valor;
                }
            }
        }

        $result = [];
        foreach ($groupedByParcelas as $parcelas => $data) {
            $descricao = $parcelas === 1 ? 'À Vista' : "{$parcelas}x";
            $result[] = [
                'tipo'        => $descricao,
                'parcelas'    => $parcelas,
                'quantidade'  => $data['total'],
                'valor_total' => round($data['valor_total'], 2),
            ];
        }

        return $result;
    }

    /**
     * Obter acordos por status
     */
    private function obterAcordosPorStatus($startDate)
    {
        return Acordo::where('created_at', '>=', $startDate)
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as total')
            ->get()
            ->map(function ($item) {
                $statusLabelMap = [
                    'pendente_pagamento' => 'Pendente Pagamento',
                    'pago'               => 'Pago',
                    'vencido'            => 'Vencido',
                    'concluido'          => 'Concluído',
                ];

                return [
                    'status' => $statusLabelMap[$item->status] ?? ucfirst($item->status),
                    'total'  => $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Obter timeline de acordos (últimos 7 dias)
     */
    private function obterTimelineAcordos($startDate)
    {
        $acordos = Acordo::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as data, valor_acordado')
            ->get();

        $groupedByDate = [];
        foreach ($acordos as $acordo) {
            $data = $acordo->data;
            if (!isset($groupedByDate[$data])) {
                $groupedByDate[$data] = [
                    'quantidade' => 0,
                    'valor_total' => 0,
                ];
            }

            $groupedByDate[$data]['quantidade']++;

            // Processar valor
            if ($acordo->valor_acordado) {
                $valor = str_replace(['reais', 'R$', ' '], '', $acordo->valor_acordado);
                $valor = str_replace(',', '.', str_replace('.', '', $valor));
                if (is_numeric($valor)) {
                    $groupedByDate[$data]['valor_total'] += (float) $valor;
                }
            }
        }

        $result = [];
        foreach ($groupedByDate as $data => $item) {
            $result[] = [
                'data'       => $data,
                'quantidade' => $item['quantidade'],
                'valor'      => round($item['valor_total'], 2),
            ];
        }

        // Ordenar por data decrescente
        usort($result, function ($a, $b) {
            return strcmp($b['data'], $a['data']);
        });

        return $result;
    }

    /**
     * Obter últimos acordos para exibir na tabela
     */
    private function obterUltimosAcordos($limit = 10)
    {
        return Acordo::with(['contato', 'proposta'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($acordo) {
                return [
                    'id'          => $acordo->id,
                    'uuid'        => $acordo->uuid,
                    'cpf'         => $acordo->contato->cpf ?? 'N/A',
                    'nome'        => $acordo->contato->nome ?? 'Unknown',
                    'valor'       => $acordo->valor_acordado,
                    'parcelas'    => $acordo->parcelas_acordadas,
                    'status'      => $acordo->status,
                    'data'        => $acordo->created_at->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }
}
