<?php
namespace App\Http\Controllers;

use App\Models\Mailing;
use App\Models\QueueJob;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class QueueController extends Controller
{
    /**
     * Status de todas as filas
     * GET /api/queues
     */
    public function index(Request $request)
    {
        $mailings = Mailing::with('script')
            ->whereIn('status', ['ativo', 'pausado', 'pronto', 'concluido'])
            ->get();

        $filas = [];
        foreach ($mailings as $mailing) {
            $stats = $this->getMailingStats($mailing->id);

            $filas[] = [
                'mailing_id'   => $mailing->id,
                'mailing_nome' => $mailing->nome,
                'script_nome'  => $mailing->script->nome,
                'status'       => $mailing->status,
                'prioridade'   => $mailing->prioridade,
                'stats'        => $stats,
            ];
        }

        return response()->json([
            'filas'       => $filas,
            'total_filas' => count($filas),
        ]);
    }

    /**
     * Estatísticas globais de todas as filas
     * GET /api/queues/stats
     */
    public function stats()
    {
        $global = QueueJob::selectRaw('
            COUNT(*) as total_jobs,
            SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) as pendentes,
            SUM(CASE WHEN status = \'processing\' THEN 1 ELSE 0 END) as processando,
            SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completados,
            SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as falhados,
            AVG(CASE WHEN tempo_processamento IS NOT NULL THEN tempo_processamento ELSE NULL END) as tempo_medio
        ')->first();

        $workersAtivos = QueueJob::where('status', 'processing')
            ->distinct('worker_id')
            ->count('worker_id');

        $mailings = Mailing::selectRaw('
            COUNT(*) as total_mailings,
            SUM(CASE WHEN status = \'ativo\' THEN 1 ELSE 0 END) as ativos,
            SUM(CASE WHEN status = \'pausado\' THEN 1 ELSE 0 END) as pausados,
            SUM(CASE WHEN status = \'concluido\' THEN 1 ELSE 0 END) as concluidos
        ')->first();

        return response()->json([
            'jobs'     => [
                'total'                => $global->total_jobs,
                'pendentes'            => $global->pendentes,
                'processando'          => $global->processando,
                'completados'          => $global->completados,
                'falhados'             => $global->falhados,
                'tempo_medio_segundos' => round($global->tempo_medio ?? 0, 2),
            ],
            'workers'  => [
                'ativos' => $workersAtivos,
            ],
            'mailings' => [
                'total'      => $mailings->total_mailings,
                'ativos'     => $mailings->ativos,
                'pausados'   => $mailings->pausados,
                'concluidos' => $mailings->concluidos,
            ],
        ]);
    }

    /**
     * Detalhes de uma fila específica
     * GET /api/queues/{mailingId}
     */
    public function show($mailingId)
    {
        $mailing = Mailing::with('script')->find($mailingId);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        $stats = $this->getMailingStats($mailingId);

        // Tempo estimado para conclusão
        $tempoEstimado = null;
        if ($stats['pendentes'] > 0 && $mailing->velocidade_contatos_hora > 0) {
            $horasRestantes = $stats['pendentes'] / $mailing->velocidade_contatos_hora;
            $tempoEstimado  = $this->formatarTempo($horasRestantes * 3600);
        }

        // Últimos jobs processados
        $ultimosJobs = QueueJob::where('mailing_id', $mailingId)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'mailing'        => $mailing,
            'stats'          => $stats,
            'tempo_estimado' => $tempoEstimado,
            'ultimos_jobs'   => $ultimosJobs,
        ]);
    }

    /**
     * Lista jobs de um mailing
     * GET /api/queues/{mailingId}/jobs
     */
    public function jobs(Request $request, $mailingId)
    {
        $query = QueueJob::where('mailing_id', $mailingId)
            ->with('contato');

        // Filtros
        if ($request->has('status')) {
            $query->porStatus($request->status);
        }

        // Ordenação
        $orderBy = $request->get('order_by', 'created_at');
        $order   = $request->get('order', 'desc');
        $query->orderBy($orderBy, $order);

        // Paginação
        $perPage = $request->get('per_page', 50);
        $jobs    = $query->paginate($perPage);

        return response()->json($jobs);
    }

    /**
     * Aumenta ou diminui prioridade de um mailing
     * POST /api/queues/{mailingId}/prioridade
     */
    public function alterarPrioridade(Request $request, $mailingId)
    {
        $mailing = Mailing::find($mailingId);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        $prioridades    = ['baixo', 'normal', 'alto'];
        $novaPrioridade = $request->input('prioridade');

        if (! in_array($novaPrioridade, $prioridades)) {
            return response()->json([
                'error' => 'Prioridade inválida. Use: baixo, normal ou alto',
            ], 400);
        }

        $mailing->prioridade = $novaPrioridade;
        $mailing->save();

        return response()->json([
            'message'    => 'Prioridade alterada com sucesso',
            'prioridade' => $novaPrioridade,
        ]);
    }

    /**
     * Status dos workers
     * GET /api/queues/workers/status
     */
    public function workersStatus()
    {
        $workers = QueueJob::where('status', 'processing')
            ->select('worker_id')
            ->selectRaw('COUNT(*) as jobs_processando')
            ->selectRaw('MAX(updated_at) as ultima_atividade')
            ->groupBy('worker_id')
            ->get();

        $totalWorkers        = $workers->count();
        $jobsEmProcessamento = $workers->sum('jobs_processando');

        return response()->json([
            'total_workers'         => $totalWorkers,
            'jobs_em_processamento' => $jobsEmProcessamento,
            'workers'               => $workers->map(function ($worker) {
                return [
                    'id'               => $worker->worker_id,
                    'jobs_processando' => $worker->jobs_processando,
                    'ultima_atividade' => $worker->ultima_atividade,
                    'ativo'            => $worker->ultima_atividade > Carbon::now()->subMinutes(5),
                ];
            }),
        ]);
    }

    /**
     * Limpa jobs completados antigos
     * DELETE /api/queues/cleanup
     */
    public function cleanup(Request $request)
    {
        $diasRetencao = $request->input('dias', 30);

        $deletados = QueueJob::where('status', 'completed')
            ->where('completed_at', '<', Carbon::now()->subDays($diasRetencao))
            ->delete();

        return response()->json([
            'message'        => 'Limpeza concluída',
            'jobs_deletados' => $deletados,
        ]);
    }

    /**
     * Métricas em tempo real para dashboard
     * GET /api/queues/dashboard
     */
    public function dashboard()
    {
        // Jobs processados nas últimas 24h (agrupados por hora)
        $ultimasHoras = QueueJob::where('completed_at', '>=', Carbon::now()->subHours(24))
            ->selectRaw('HOUR(completed_at) as hora')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as sucesso')
            ->selectRaw('SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as falhas')
            ->groupBy('hora')
            ->orderBy('hora')
            ->get();

        // Top 5 mailings mais ativos
        $topMailings = Mailing::with('script')
            ->where('status', 'ativo')
            ->orderBy('processados', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($mailing) {
                return [
                    'id'           => $mailing->id,
                    'nome'         => $mailing->nome,
                    'script'       => $mailing->script->nome,
                    'progresso'    => $mailing->progresso,
                    'taxa_sucesso' => $mailing->taxa_sucesso,
                ];
            });

        // Taxa de sucesso geral (últimos 7 dias)
        $taxaSucesso = QueueJob::where('completed_at', '>=', Carbon::now()->subDays(7))
            ->whereIn('status', ['completed', 'failed'])
            ->selectRaw('
                SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as sucesso,
                COUNT(*) as total
            ')
            ->first();

        $taxaSucessoPorcentagem = 0;
        if ($taxaSucesso && $taxaSucesso->total > 0) {
            $taxaSucessoPorcentagem = round(($taxaSucesso->sucesso / $taxaSucesso->total) * 100, 2);
        }

        return response()->json([
            'atividade_24h'   => $ultimasHoras,
            'top_mailings'    => $topMailings,
            'taxa_sucesso_7d' => $taxaSucessoPorcentagem,
        ]);
    }

    // Métodos auxiliares privados

    /**
     * Retorna estatísticas de um mailing
     */
    private function getMailingStats(int $mailingId): array
    {
        $stats = QueueJob::where('mailing_id', $mailingId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status = \'processing\' THEN 1 ELSE 0 END) as processando,
                SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as falhados,
                AVG(CASE WHEN tempo_processamento IS NOT NULL THEN tempo_processamento ELSE NULL END) as tempo_medio
            ')
            ->first();

        $processados = ($stats->completados ?? 0) + ($stats->falhados ?? 0);
        $taxaSucesso = 0;
        if ($processados > 0) {
            $taxaSucesso = round((($stats->completados ?? 0) / $processados) * 100, 2);
        }

        return [
            'total'                => $stats->total ?? 0,
            'pendentes'            => $stats->pendentes ?? 0,
            'processando'          => $stats->processando ?? 0,
            'completados'          => $stats->completados ?? 0,
            'falhados'             => $stats->falhados ?? 0,
            'processados'          => $processados,
            'taxa_sucesso'         => $taxaSucesso,
            'tempo_medio_segundos' => round($stats->tempo_medio ?? 0, 2),
        ];
    }

    /**
     * Formata tempo em segundos para string legível
     */
    private function formatarTempo(float $segundos): string
    {
        if ($segundos < 60) {
            return round($segundos) . ' segundos';
        }

        if ($segundos < 3600) {
            return round($segundos / 60) . ' minutos';
        }

        if ($segundos < 86400) {
            $horas   = floor($segundos / 3600);
            $minutos = round(($segundos % 3600) / 60);
            return "{$horas}h {$minutos}min";
        }

        $dias  = floor($segundos / 86400);
        $horas = round(($segundos % 86400) / 3600);
        return "{$dias}d {$horas}h";
    }

    /**
     * Status global da fila
     * GET /api/queue/status
     */
    public function queueStatus()
    {
        // Conta jobs por status
        $stats = QueueJob::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) as total_pending,
            SUM(CASE WHEN status = \'processing\' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as failed
        ')->first();

        // Verifica se fila está ativa através de Redis ou status de mailings
        $fila_ativa   = Mailing::where('status', 'ativo')->exists();
        $status_geral = $fila_ativa ? 'running' : 'stopped';

        // Verifica se está pausada
        $fila_pausada = Mailing::where('status', 'pausado')->exists();
        if ($fila_pausada && ! $fila_ativa) {
            $status_geral = 'paused';
        }

        return response()->json([
            'status'     => $status_geral,
            'total'      => (int) ($stats->total ?? 0),
            'processing' => (int) ($stats->processing ?? 0),
            'completed'  => (int) ($stats->completed ?? 0),
            'failed'     => (int) ($stats->failed ?? 0),
        ]);
    }

    /**
     * Inicia a fila de processamento
     * POST /api/queue/start
     */
    public function queueStart(Request $request)
    {
        try {
            // Busca mailings em status 'pronto' ou 'pausado' e ativa-os
            $updated = Mailing::whereIn('status', ['pronto', 'pausado'])
                ->update(['status' => 'ativo', 'updated_at' => Carbon::now()]);

            // Se nenhum mailing foi encontrado, retorna erro
            if ($updated === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma campanha disponível para iniciar',
                ], 400);
            }

            return response()->json([
                'success'           => true,
                'message'           => 'Fila iniciada com sucesso',
                'mailings_ativados' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao iniciar fila: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pausa a fila de processamento
     * POST /api/queue/pause
     */
    public function queuePause(Request $request)
    {
        try {
            // Move mailings ativos para pausado
            $updated = Mailing::where('status', 'ativo')
                ->update(['status' => 'pausado', 'updated_at' => Carbon::now()]);

            if ($updated === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma fila ativa para pausar',
                ], 400);
            }

            return response()->json([
                'success'           => true,
                'message'           => 'Fila pausada com sucesso',
                'mailings_pausados' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao pausar fila: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retoma a fila de processamento
     * POST /api/queue/resume
     */
    public function queueResume(Request $request)
    {
        try {
            // Move mailings pausados para ativo
            $updated = Mailing::where('status', 'pausado')
                ->update(['status' => 'ativo', 'updated_at' => Carbon::now()]);

            if ($updated === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma fila pausada para retomar',
                ], 400);
            }

            return response()->json([
                'success'            => true,
                'message'            => 'Fila retomada com sucesso',
                'mailings_retomados' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao retomar fila: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Para completamente a fila de processamento
     * POST /api/queue/stop
     */
    public function queueStop(Request $request)
    {
        try {
            // Move todos os mailings para 'pronto' (parados)
            $updated = Mailing::whereIn('status', ['ativo', 'pausado'])
                ->update(['status' => 'pronto', 'updated_at' => Carbon::now()]);

            // Marca jobs em processamento como pendentes novamente
            $pendingUpdated = QueueJob::where('status', 'processing')
                ->update(['status' => 'pending', 'updated_at' => Carbon::now()]);

            return response()->json([
                'success'          => true,
                'message'          => 'Fila parada com sucesso',
                'mailings_parados' => $updated,
                'jobs_revertidos'  => $pendingUpdated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao parar fila: ' . $e->getMessage(),
            ], 500);
        }
    }
}
