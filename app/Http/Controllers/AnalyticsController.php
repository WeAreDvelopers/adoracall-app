<?php
namespace App\Http\Controllers;

use App\Models\Contato;
use App\Models\Lead;
use App\Models\Ligacao;
use App\Models\LigacaoVenda;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    /**
     * Analytics gerais (ambos os fluxos)
     */
    public function getGeneralAnalytics(Request $request)
    {
        try {
            $days      = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($days);

            $stats = [
                'sales'      => $this->getSalesStats($startDate),
                'collection' => $this->getCollectionStats($startDate),
                'combined'   => [],
            ];

            // Estatísticas combinadas
            $stats['combined'] = [
                'total_calls'    => $stats['sales']['total_calls'] + $stats['collection']['total_calls'],
                'total_duration' => $stats['sales']['total_duration'] + $stats['collection']['total_duration'],
                'avg_duration'   => ($stats['sales']['avg_duration'] + $stats['collection']['avg_duration']) / 2,
            ];

            return response()->json([
                'success'     => true,
                'period_days' => $days,
                'analytics'   => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao gerar analytics gerais: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analytics específicos de VENDAS
     */
    public function getSalesAnalytics(Request $request)
    {
        try {
            $days      = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($days);

            $stats = $this->getSalesStats($startDate);

            // Métricas adicionais de vendas
            $stats['conversion_funnel']   = $this->getSalesConversionFunnel($startDate);
            $stats['interest_breakdown']  = $this->getInterestBreakdown($startDate);
            $stats['objections_analysis'] = $this->getObjectionsAnalysis($startDate);
            $stats['follow_ups']          = $this->getFollowUpStats($startDate);
            $stats['top_campaigns']       = $this->getTopCampaigns($startDate);
            $stats['top_products']        = $this->getTopProducts($startDate);
            $stats['daily_trend']         = $this->getSalesDailyTrend($startDate);

            return response()->json([
                'success'     => true,
                'period_days' => $days,
                'analytics'   => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao gerar analytics de vendas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analytics específicos de COBRANÇA
     */
    public function getCollectionAnalytics(Request $request)
    {
        try {
            $days      = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($days);

            $stats = $this->getCollectionStats($startDate);

            // Métricas adicionais de cobrança
            $stats['validation_stats']         = $this->getValidationStats($startDate);
            $stats['success_rate_by_attempts'] = $this->getSuccessRateByAttempts($startDate);
            $stats['daily_trend']              = $this->getCollectionDailyTrend($startDate);
            $stats['debt_analysis']            = $this->getDebtAnalysis($startDate);

            return response()->json([
                'success'     => true,
                'period_days' => $days,
                'analytics'   => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao gerar analytics de cobrança: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // MÉTODOS AUXILIARES - VENDAS
    // ============================================

    private function getSalesStats($startDate)
    {
        $totalCalls     = LigacaoVenda::where('created_at', '>=', $startDate)->count();
        $completedCalls = LigacaoVenda::where('created_at', '>=', $startDate)
            ->where('status', 'concluida')
            ->count();

        $interested = LigacaoVenda::where('created_at', '>=', $startDate)
            ->where('resultado', 'interessado')
            ->count();

        $notInterested = LigacaoVenda::where('created_at', '>=', $startDate)
            ->where('resultado', 'nao_interessado')
            ->count();

        $callbacks = LigacaoVenda::where('created_at', '>=', $startDate)
            ->where('resultado', 'callback')
            ->count();

        $noResponse = LigacaoVenda::where('created_at', '>=', $startDate)
            ->where('resultado', 'sem_resposta')
            ->count();

        $totalDuration = LigacaoVenda::where('created_at', '>=', $startDate)
            ->sum('duracao');

        $avgDuration = LigacaoVenda::where('created_at', '>=', $startDate)
            ->avg('duracao');

        // Taxa de conversão
        $conversionRate = $completedCalls > 0
            ? round(($interested / $completedCalls) * 100, 2)
            : 0;

        // Taxa de contato
        $contactRate = $totalCalls > 0
            ? round((($completedCalls - $noResponse) / $totalCalls) * 100, 2)
            : 0;

        return [
            'total_calls'     => $totalCalls,
            'completed_calls' => $completedCalls,
            'interested'      => $interested,
            'not_interested'  => $notInterested,
            'callbacks'       => $callbacks,
            'no_response'     => $noResponse,
            'conversion_rate' => $conversionRate,
            'contact_rate'    => $contactRate,
            'total_duration'  => $totalDuration,
            'avg_duration'    => round($avgDuration ?? 0, 2),
            'by_status'       => LigacaoVenda::where('created_at', '>=', $startDate)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_result'       => LigacaoVenda::where('created_at', '>=', $startDate)
                ->select('resultado', DB::raw('count(*) as count'))
                ->groupBy('resultado')
                ->pluck('count', 'resultado'),
        ];
    }

    private function getSalesConversionFunnel($startDate)
    {
        $totalLeads = Lead::where('created_at', '>=', $startDate)->count();
        $contacted  = Lead::where('created_at', '>=', $startDate)
            ->where('tentativas', '>', 0)
            ->count();
        $interested = LigacaoVenda::where('created_at', '>=', $startDate)
            ->where('interesse_demonstrado', true)
            ->count();
        $followUpScheduled = LigacaoVenda::where('created_at', '>=', $startDate)
            ->whereNotNull('agendamento_follow_up')
            ->count();

        return [
            'total_leads'         => $totalLeads,
            'contacted'           => $contacted,
            'contacted_rate'      => $totalLeads > 0 ? round(($contacted / $totalLeads) * 100, 2) : 0,
            'interested'          => $interested,
            'interest_rate'       => $contacted > 0 ? round(($interested / $contacted) * 100, 2) : 0,
            'follow_up_scheduled' => $followUpScheduled,
            'follow_up_rate'      => $interested > 0 ? round(($followUpScheduled / $interested) * 100, 2) : 0,
        ];
    }

    private function getInterestBreakdown($startDate)
    {
        return Lead::where('created_at', '>=', $startDate)
            ->whereNotNull('interesse_nivel')
            ->select('interesse_nivel', DB::raw('count(*) as count'))
            ->groupBy('interesse_nivel')
            ->pluck('count', 'interesse_nivel');
    }

    private function getObjectionsAnalysis($startDate)
    {
        $callsWithObjections = LigacaoVenda::where('created_at', '>=', $startDate)
            ->whereNotNull('objecoes')
            ->count();

        $totalCalls = LigacaoVenda::where('created_at', '>=', $startDate)->count();

        return [
            'total_with_objections' => $callsWithObjections,
            'objection_rate'        => $totalCalls > 0 ? round(($callsWithObjections / $totalCalls) * 100, 2) : 0,
            'most_common'           => [], // TODO: Extrair objeções comuns do JSON
        ];
    }

    private function getFollowUpStats($startDate)
    {
        $scheduled = LigacaoVenda::where('created_at', '>=', $startDate)
            ->whereNotNull('agendamento_follow_up')
            ->count();

        $upcoming = LigacaoVenda::where('created_at', '>=', $startDate)
            ->where('agendamento_follow_up', '>=', Carbon::now())
            ->count();

        $overdue = LigacaoVenda::where('created_at', '>=', $startDate)
            ->where('agendamento_follow_up', '<', Carbon::now())
            ->where('status', '!=', 'concluida')
            ->count();

        return [
            'total_scheduled' => $scheduled,
            'upcoming'        => $upcoming,
            'overdue'         => $overdue,
        ];
    }

    private function getTopCampaigns($startDate)
    {
        return Lead::where('created_at', '>=', $startDate)
            ->whereNotNull('campanha')
            ->select('campanha', DB::raw('count(*) as count'))
            ->groupBy('campanha')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    private function getTopProducts($startDate)
    {
        return Lead::where('created_at', '>=', $startDate)
            ->whereNotNull('produto')
            ->select('produto', DB::raw('count(*) as count'))
            ->groupBy('produto')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    private function getSalesDailyTrend($startDate)
    {
        return LigacaoVenda::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when resultado = "interessado" then 1 else 0 end) as interested'),
                DB::raw('sum(case when interesse_demonstrado = 1 then 1 else 0 end) as showed_interest'),
                DB::raw('avg(duracao) as avg_duration')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    // ============================================
    // MÉTODOS AUXILIARES - COBRANÇA
    // ============================================

    private function getCollectionStats($startDate)
    {
        $totalCalls = Ligacao::where('created_at', '>=', $startDate)->count();

        $completedCalls = Ligacao::where('created_at', '>=', $startDate)
            ->where('status', 'concluida')
            ->count();

        $validationSuccess = Ligacao::where('created_at', '>=', $startDate)
            ->where('validacao_sucesso', true)
            ->count();

        $validationFailed = Ligacao::where('created_at', '>=', $startDate)
            ->where('validacao_sucesso', false)
            ->count();

        $totalDuration = Ligacao::where('created_at', '>=', $startDate)
            ->sum('duracao');

        $avgDuration = Ligacao::where('created_at', '>=', $startDate)
            ->avg('duracao');

        $avgValidationAttempts = Ligacao::where('created_at', '>=', $startDate)
            ->avg('tentativas_validacao');

        // Taxa de sucesso de validação
        $validationRate = ($validationSuccess + $validationFailed) > 0
            ? round(($validationSuccess / ($validationSuccess + $validationFailed)) * 100, 2)
            : 0;

        return [
            'total_calls'             => $totalCalls,
            'completed_calls'         => $completedCalls,
            'validation_success'      => $validationSuccess,
            'validation_failed'       => $validationFailed,
            'validation_rate'         => $validationRate,
            'avg_validation_attempts' => round($avgValidationAttempts ?? 0, 2),
            'total_duration'          => $totalDuration,
            'avg_duration'            => round($avgDuration ?? 0, 2),
            'by_status'               => Ligacao::where('created_at', '>=', $startDate)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_result'               => Ligacao::where('created_at', '>=', $startDate)
                ->select('resultado', DB::raw('count(*) as count'))
                ->groupBy('resultado')
                ->pluck('count', 'resultado'),
        ];
    }

    private function getValidationStats($startDate)
    {
        return [
            'by_attempts'              => Ligacao::where('created_at', '>=', $startDate)
                ->select('tentativas_validacao', DB::raw('count(*) as count'))
                ->groupBy('tentativas_validacao')
                ->orderBy('tentativas_validacao')
                ->pluck('count', 'tentativas_validacao'),
            'success_on_first_attempt' => Ligacao::where('created_at', '>=', $startDate)
                ->where('validacao_sucesso', true)
                ->where('tentativas_validacao', 1)
                ->count(),
        ];
    }

    private function getSuccessRateByAttempts($startDate)
    {
        $stats = Contato::where('created_at', '>=', $startDate)
            ->select(
                'tentativas_contato',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when resultado is not null then 1 else 0 end) as with_result')
            )
            ->groupBy('tentativas_contato')
            ->orderBy('tentativas_contato')
            ->get();

        return $stats->map(function ($stat) {
            return [
                'attempts'     => $stat->tentativas_contato,
                'total'        => $stat->total,
                'success_rate' => $stat->total > 0 ? round(($stat->with_result / $stat->total) * 100, 2) : 0,
            ];
        });
    }

    private function getCollectionDailyTrend($startDate)
    {
        return Ligacao::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total_calls'),
                DB::raw('sum(case when validacao_sucesso = 1 then 1 else 0 end) as successful_validations'),
                DB::raw('avg(duracao) as avg_duration'),
                DB::raw('avg(tentativas_validacao) as avg_attempts')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
    }

    private function getDebtAnalysis($startDate)
    {
        $totalDebt = Contato::where('created_at', '>=', $startDate)
            ->sum('valor_divida');

        $contactedDebt = Contato::where('created_at', '>=', $startDate)
            ->where('tentativas_contato', '>', 0)
            ->sum('valor_divida');

        $resolvedDebt = Contato::where('created_at', '>=', $startDate)
            ->whereNotNull('resultado')
            ->sum('valor_divida');

        return [
            'total_debt'      => $totalDebt,
            'contacted_debt'  => $contactedDebt,
            'resolved_debt'   => $resolvedDebt,
            'resolution_rate' => $totalDebt > 0 ? round(($resolvedDebt / $totalDebt) * 100, 2) : 0,
        ];
    }
}
