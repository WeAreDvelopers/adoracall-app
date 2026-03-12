<?php
namespace App\Http\Controllers;

use App\Models\CallHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CallHistoryController extends Controller
{
    /**
     * Listar chamadas do banco de dados local (já sincronizadas)
     */
    public function listRetellCalls(Request $request)
    {
        try {

            // Query builder
            $query = CallHistory::query();

            // Filtrar por status
            if ($request->has('call_status') && $request->call_status) {
                $query->where('call_status', $request->call_status);
            }

            // Filtrar por tipo
            if ($request->has('call_type') && $request->call_type) {
                $query->where('call_type', $request->call_type);
            }

            // Filtrar por sentimento
            if ($request->has('sentiment') && $request->sentiment) {
                $query->where('user_sentiment', $request->sentiment);
            }

            // Filtrar por período
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('start_timestamp', [
                    $request->start_date . ' 00:00:00',
                    $request->end_date . ' 23:59:59',
                ]);
            } else {
                // Últimos 30 dias por padrão
                $query->where('start_timestamp', '>=', Carbon::now()->subDays(30));
            }

            // Ordenação
            $sortOrder = $request->get('sort_order', 'descending');
            if ($sortOrder === 'descending') {
                $query->orderBy('start_timestamp', 'desc');
            } else {
                $query->orderBy('start_timestamp', 'asc');
            }

            // Paginação
            $limit = $request->get('limit', 50);
            $page  = $request->get('page', 1);

            // Contar total antes da paginação
            $total = $query->count();

            // Aplicar paginação
            $calls = $query->skip(($page - 1) * $limit)
                ->take($limit)
                ->get();


            // Formatar para o frontend
            $formattedCalls = $calls->map(function ($call) {
                return $call->paraFrontend();
            });

            return response()->json([
                'success'   => true,
                'calls'     => $formattedCalls,
                'total'     => $total,
                'page'      => $page,
                'per_page'  => $limit,
                'last_page' => ceil($total / $limit),
                'has_more'  => ($page * $limit) < $total,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao buscar histórico: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Buscar detalhes de uma chamada específica do banco local
     */
    public function getCallDetails($callId)
    {
        try {

            $call = CallHistory::where('call_id_retell', $callId)->first();

            if (! $call) {

                return response()->json([
                    'success' => false,
                    'error'   => ['message' => 'Chamada não encontrada'],
                ], 404);
            }


            return response()->json([
                'success' => true,
                'data'    => $call->paraFrontend(),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Exception: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => ['message' => $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Buscar estatísticas gerais do banco local
     */
    public function getStatistics(Request $request)
    {
        try {
            $dias = $request->get('days', 30);


            // Usar método estático do model
            $stats = CallHistory::estatisticas($dias);
            // dump($stats);

            return response()->json([
                'success'    => true,
                'statistics' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao calcular estatísticas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => ['message' => $e->getMessage()],
            ], 500);
        }
    }

}
