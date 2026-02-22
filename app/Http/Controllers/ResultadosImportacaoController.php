<?php

namespace App\Http\Controllers;

use App\Models\Mailing;
use App\Models\Contato;
use App\Models\Ligacao;
use App\Models\CallHistory;
use App\Models\QueueJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResultadosImportacaoController extends Controller
{
    /**
     * Lista todos os mailings com resultados
     * GET /resultados
     */
    public function index(Request $request)
    {
        try {
            $mailings = Mailing::with([
                'contatos',
                'queueJobs'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

            foreach ($mailings as $mailing) {
                $mailing->stats = $this->calcularStats($mailing);
            }

            return view('resultados.index', [
                'mailings' => $mailings,
                'totalMailings' => Mailing::count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar resultados: ' . $e->getMessage());
            return view('resultados.index', [
                'mailings' => collect(),
                'error' => 'Erro ao carregar resultados',
            ]);
        }
    }

    /**
     * Exibe detalhes de um mailing com resultados
     * GET /resultados/{mailingId}
     */
    public function show($mailingId)
    {
        try {
            $mailing = Mailing::with(['contatos', 'queueJobs', 'script'])->findOrFail($mailingId);

            $stats = $this->calcularStats($mailing);

            $contatos = Contato::where('mailing_id', $mailingId)
                ->with(['ligacoes' => function ($q) {
                    $q->with('callHistory')->latest();
                }])
                ->paginate(20);

            // Gerar dados para gráficos
            $graficoStatus = $this->gerarGraficoStatus($mailing);
            $graficoRetentativa = $this->gerarGraficoRetentativa($mailing);
            $graficoSentimento = $this->gerarGraficoSentimento($mailing);

            return view('resultados.show', [
                'mailing' => $mailing,
                'stats' => $stats,
                'contatos' => $contatos,
                'graficoStatus' => $graficoStatus,
                'graficoRetentativa' => $graficoRetentativa,
                'graficoSentimento' => $graficoSentimento,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao exibir resultado: ' . $e->getMessage());
            return redirect('/resultados')->with('error', 'Mailing não encontrado');
        }
    }

    /**
     * Exporta resultados em CSV
     * GET /resultados/{mailingId}/exportar
     */
    public function exportar($mailingId)
    {
        try {
            $mailing = Mailing::findOrFail($mailingId);

            $contatos = Contato::where('mailing_id', $mailingId)
                ->with(['ligacoes.callHistory'])
                ->get();

            $csv = "ID,Nome,Telefone,Email,CPF,Valor Débito,Status,Ligações,Última Ligação,Resultado,Sentimento\n";

            foreach ($contatos as $contato) {
                $ultimaLigacao = $contato->ligacoes()->latest()->first();
                $resultado = $ultimaLigacao && $ultimaLigacao->callHistory
                    ? $ultimaLigacao->callHistory->resultado_conversacao ?? 'desconhecido'
                    : 'sem ligação';
                $sentimento = $ultimaLigacao && $ultimaLigacao->callHistory
                    ? $ultimaLigacao->callHistory->user_sentiment ?? '-'
                    : '-';
                $dataLigacao = $ultimaLigacao ? $ultimaLigacao->created_at->format('d/m/Y H:i') : '-';

                $csv .= "\"{$contato->id}\",";
                $csv .= "\"{$contato->nome}\",";
                $csv .= "\"{$contato->telefone}\",";
                $csv .= "\"{$contato->email}\",";
                $csv .= "\"{$contato->cpf}\",";
                $csv .= "\"{$contato->valor_debito}\",";
                $csv .= "\"{$contato->status}\",";
                $csv .= "\"{$contato->ligacoes()->count()}\",";
                $csv .= "\"{$dataLigacao}\",";
                $csv .= "\"{$resultado}\",";
                $csv .= "\"{$sentimento}\"\n";
            }

            Log::info("✅ [EXPORT] Exportação realizada para mailing {$mailingId}");

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=resultados_mailing_{$mailingId}_" . date('YmdHis') . ".csv",
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao exportar: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao exportar resultados');
        }
    }

    /**
     * API: Retorna estatísticas em JSON
     * GET /api/resultados/{mailingId}/stats
     */
    public function apiStats($mailingId)
    {
        try {
            $mailing = Mailing::findOrFail($mailingId);
            $stats = $this->calcularStats($mailing);

            return response()->json([
                'success' => true,
                'mailing_id' => $mailingId,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Mailing não encontrado',
            ], 404);
        }
    }

    /**
     * Calcula estatísticas de um mailing
     */
    private function calcularStats(Mailing $mailing)
    {
        $total = $mailing->contatos()->count();

        $statuses = $mailing->contatos()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $sucessos = CallHistory::whereIn('contato_id', function ($q) use ($mailing) {
            $q->select('id')->from('contatos')->where('mailing_id', $mailing->id);
        })->where('call_successful', true)->count();

        $chamadas = Ligacao::whereIn('contato_id', function ($q) use ($mailing) {
            $q->select('id')->from('contatos')->where('mailing_id', $mailing->id);
        })->count();

        $taxa_conversao = $total > 0 ? round(($sucessos / $total) * 100, 2) : 0;
        $taxa_resposta = $chamadas > 0 ? round(($chamadas / $total) * 100, 2) : 0;

        return [
            'total_importados' => $total,
            'pendentes' => $statuses['pendente'] ?? 0,
            'em_ligacao' => $statuses['em_ligacao'] ?? 0,
            'nao_atendidas' => $statuses['nao_atendida'] ?? 0,
            'acordos' => $statuses['acordo_realizado'] ?? 0,
            'falhados' => $statuses['falhou'] ?? 0,
            'sem_tratativa' => $statuses['sem_tratativa'] ?? 0,
            'erros' => $statuses['erro_ligacao'] ?? 0,
            'taxa_conversao' => $taxa_conversao . '%',
            'taxa_resposta' => $taxa_resposta . '%',
            'total_ligacoes' => $chamadas,
            'ligacoes_sucesso' => $sucessos,
        ];
    }

    /**
     * Gera dados para gráfico de status
     */
    private function gerarGraficoStatus(Mailing $mailing)
    {
        $statuses = Contato::where('mailing_id', $mailing->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $labels = [];
        $data = [];

        foreach ($statuses as $item) {
            $labels[] = $this->traduzirStatus($item->status);
            $data[] = $item->count;
        }

        return json_encode([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    /**
     * Gera dados para gráfico de tentativas
     */
    private function gerarGraficoRetentativa(Mailing $mailing)
    {
        $tentativas = QueueJob::where('mailing_id', $mailing->id)
            ->select('tentativas', DB::raw('count(*) as count'))
            ->groupBy('tentativas')
            ->orderBy('tentativas')
            ->get();

        $labels = [];
        $data = [];

        foreach ($tentativas as $item) {
            $labels[] = "Tentativa " . ($item->tentativas + 1);
            $data[] = $item->count;
        }

        return json_encode([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    /**
     * Gera dados para gráfico de sentimento
     */
    private function gerarGraficoSentimento(Mailing $mailing)
    {
        $sentimentos = CallHistory::whereIn('contato_id', function ($q) use ($mailing) {
            $q->select('id')->from('contatos')->where('mailing_id', $mailing->id);
        })
        ->select('user_sentiment', DB::raw('count(*) as count'))
        ->groupBy('user_sentiment')
        ->get();

        $labels = [];
        $data = [];

        foreach ($sentimentos as $item) {
            $labels[] = ucfirst($item->user_sentiment ?? 'Desconhecido');
            $data[] = $item->count;
        }

        return json_encode([
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    /**
     * Traduz status para português
     */
    private function traduzirStatus($status)
    {
        $traducoes = [
            'pendente' => 'Pendente',
            'em_ligacao' => 'Em Ligação',
            'nao_atendida' => 'Não Atendida',
            'acordo_realizado' => 'Acordo ✓',
            'falhou' => 'Falhou',
            'sem_tratativa' => 'Sem Tratativa',
            'erro_ligacao' => 'Erro',
            'desligou_rapido' => 'Desligou Rápido',
        ];

        return $traducoes[$status] ?? $status;
    }
}
