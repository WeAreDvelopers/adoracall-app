<?php
namespace App\Http\Controllers;

use App\Models\Contato;
use App\Models\Lead;
use App\Models\Ligacao;
use App\Models\LigacaoVenda;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    /**
     * Exportar dados em CSV
     */
    public function exportCSV(Request $request)
    {
        try {
            $type      = $request->get('type', 'sales'); // sales, collection, general
            $days      = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($days);

            $filename = "analytics_{$type}_" . Carbon::now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($type, $startDate) {
                $file = fopen('php://output', 'w');

                if ($type === 'sales') {
                    $this->exportSalesCSV($file, $startDate);
                } elseif ($type === 'collection') {
                    $this->exportCollectionCSV($file, $startDate);
                } else {
                    $this->exportGeneralCSV($file, $startDate);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao exportar CSV: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exportar dados em PDF
     */
    public function exportPDF(Request $request)
    {
        try {
            $type      = $request->get('type', 'sales'); // sales, collection, general
            $days      = $request->get('days', 30);
            $startDate = Carbon::now()->subDays($days);

            // Coletar dados
            $data = [];
            if ($type === 'sales') {
                $data = $this->getSalesData($startDate);
            } elseif ($type === 'collection') {
                $data = $this->getCollectionData($startDate);
            } else {
                $data = $this->getGeneralData($startDate);
            }

            // Gerar HTML para PDF
            $html = $this->generatePDFHTML($type, $data, $days);

            // Por enquanto, retornar HTML
            // TODO: Integrar com biblioteca de PDF como dompdf ou wkhtmltopdf
            return response($html, 200, [
                'Content-Type' => 'text/html',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erro ao exportar PDF: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ============================================
    // EXPORTAÇÃO CSV - VENDAS
    // ============================================

    private function exportSalesCSV($file, $startDate)
    {
        // Cabeçalhos
        fputcsv($file, [
            'ID',
            'Lead ID',
            'Lead Nome',
            'Telefone',
            'Data',
            'Duração (s)',
            'Status',
            'Resultado',
            'Interesse Demonstrado',
            'Produto',
            'Campanha',
            'Follow-up Agendado',
            'Call ID Retell',
        ]);

        // Dados
        $vendas = LigacaoVenda::with('lead')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($vendas as $venda) {
            fputcsv($file, [
                $venda->id,
                $venda->lead_id,
                $venda->lead ? $venda->lead->nome_completo : '-',
                $venda->lead ? $venda->lead->telefone : '-',
                $venda->created_at->format('Y-m-d H:i:s'),
                $venda->duracao ?? 0,
                $venda->status,
                $venda->resultado ?? '-',
                $venda->interesse_demonstrado ? 'Sim' : 'Não',
                $venda->lead ? $venda->lead->produto : '-',
                $venda->lead ? $venda->lead->campanha : '-',
                $venda->agendamento_follow_up ? $venda->agendamento_follow_up->format('Y-m-d H:i:s') : '-',
                $venda->call_id_retell,
            ]);
        }
    }

    // ============================================
    // EXPORTAÇÃO CSV - COBRANÇA
    // ============================================

    private function exportCollectionCSV($file, $startDate)
    {
        // Cabeçalhos
        fputcsv($file, [
            'ID',
            'Contato ID',
            'Nome',
            'Telefone',
            'Data',
            'Duração (s)',
            'Status',
            'Resultado',
            'Validação Sucesso',
            'Tentativas Validação',
            'Valor Dívida',
            'Call ID Retell',
        ]);

        // Dados
        $ligacoes = Ligacao::with('contato')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($ligacoes as $ligacao) {
            fputcsv($file, [
                $ligacao->id,
                $ligacao->contato_id,
                $ligacao->contato ? $ligacao->contato->nome : '-',
                $ligacao->contato ? $ligacao->contato->telefone : '-',
                $ligacao->created_at->format('Y-m-d H:i:s'),
                $ligacao->duracao ?? 0,
                $ligacao->status,
                $ligacao->resultado ?? '-',
                $ligacao->validacao_sucesso ? 'Sim' : 'Não',
                $ligacao->tentativas_validacao,
                $ligacao->contato ? $ligacao->contato->valor_divida : 0,
                $ligacao->call_id_retell,
            ]);
        }
    }

    // ============================================
    // EXPORTAÇÃO CSV - GERAL
    // ============================================

    private function exportGeneralCSV($file, $startDate)
    {
        // Cabeçalhos
        fputcsv($file, [
            'Tipo',
            'ID',
            'Nome/Lead',
            'Telefone',
            'Data',
            'Duração (s)',
            'Status',
            'Resultado',
        ]);

        // Dados de vendas
        $vendas = LigacaoVenda::with('lead')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($vendas as $venda) {
            fputcsv($file, [
                'Venda',
                $venda->id,
                $venda->lead ? $venda->lead->nome_completo : '-',
                $venda->lead ? $venda->lead->telefone : '-',
                $venda->created_at->format('Y-m-d H:i:s'),
                $venda->duracao ?? 0,
                $venda->status,
                $venda->resultado ?? '-',
            ]);
        }

        // Dados de cobrança
        $ligacoes = Ligacao::with('contato')
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($ligacoes as $ligacao) {
            fputcsv($file, [
                'Cobrança',
                $ligacao->id,
                $ligacao->contato ? $ligacao->contato->nome : '-',
                $ligacao->contato ? $ligacao->contato->telefone : '-',
                $ligacao->created_at->format('Y-m-d H:i:s'),
                $ligacao->duracao ?? 0,
                $ligacao->status,
                $ligacao->resultado ?? '-',
            ]);
        }
    }

    // ============================================
    // GERAÇÃO DE PDF
    // ============================================

    private function getSalesData($startDate)
    {
        return [
            'calls' => LigacaoVenda::with('lead')
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'desc')
                ->get(),
            'stats' => [
                'total'          => LigacaoVenda::where('created_at', '>=', $startDate)->count(),
                'interested'     => LigacaoVenda::where('created_at', '>=', $startDate)->where('resultado', 'interessado')->count(),
                'not_interested' => LigacaoVenda::where('created_at', '>=', $startDate)->where('resultado', 'nao_interessado')->count(),
                'callbacks'      => LigacaoVenda::where('created_at', '>=', $startDate)->where('resultado', 'callback')->count(),
            ],
        ];
    }

    private function getCollectionData($startDate)
    {
        return [
            'calls' => Ligacao::with('contato')
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'desc')
                ->get(),
            'stats' => [
                'total'              => Ligacao::where('created_at', '>=', $startDate)->count(),
                'validation_success' => Ligacao::where('created_at', '>=', $startDate)->where('validacao_sucesso', true)->count(),
                'validation_failed'  => Ligacao::where('created_at', '>=', $startDate)->where('validacao_sucesso', false)->count(),
            ],
        ];
    }

    private function getGeneralData($startDate)
    {
        return [
            'sales'      => $this->getSalesData($startDate),
            'collection' => $this->getCollectionData($startDate),
        ];
    }

    private function generatePDFHTML($type, $data, $days)
    {
        $title = match ($type) {
            'sales'      => 'Relatório de Vendas',
            'collection' => 'Relatório de Cobrança',
            default      => 'Relatório Geral'
        };

        $date   = Carbon::now()->format('d/m/Y H:i:s');
        $period = "Últimos {$days} dias";

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$title}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 40px;
                }
                h1 {
                    color: #333;
                    border-bottom: 2px solid #007bff;
                    padding-bottom: 10px;
                }
                .header {
                    margin-bottom: 30px;
                }
                .stats {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin-bottom: 30px;
                }
                .stat-card {
                    border: 1px solid #ddd;
                    padding: 15px;
                    border-radius: 5px;
                }
                .stat-value {
                    font-size: 32px;
                    font-weight: bold;
                    color: #007bff;
                }
                .stat-label {
                    color: #666;
                    margin-top: 5px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 12px;
                    text-align: left;
                }
                th {
                    background-color: #007bff;
                    color: white;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>{$title}</h1>
                <p><strong>Período:</strong> {$period}</p>
                <p><strong>Gerado em:</strong> {$date}</p>
            </div>
        ";

        if ($type === 'sales') {
            $html .= $this->generateSalesPDFContent($data);
        } elseif ($type === 'collection') {
            $html .= $this->generateCollectionPDFContent($data);
        } else {
            $html .= $this->generateGeneralPDFContent($data);
        }

        $html .= "
        </body>
        </html>
        ";

        return $html;
    }

    private function generateSalesPDFContent($data)
    {
        $html = "
            <div class='stats'>
                <div class='stat-card'>
                    <div class='stat-value'>{$data['stats']['total']}</div>
                    <div class='stat-label'>Total de Chamadas</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-value'>{$data['stats']['interested']}</div>
                    <div class='stat-label'>Interessados</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-value'>{$data['stats']['not_interested']}</div>
                    <div class='stat-label'>Não Interessados</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-value'>{$data['stats']['callbacks']}</div>
                    <div class='stat-label'>Callbacks Agendados</div>
                </div>
            </div>

            <h2>Detalhes das Chamadas</h2>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Lead</th>
                        <th>Telefone</th>
                        <th>Duração</th>
                        <th>Status</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
        ";

        foreach ($data['calls'] as $call) {
            $date     = $call->created_at->format('d/m/Y H:i');
            $nome     = $call->lead ? $call->lead->nome_completo : '-';
            $telefone = $call->lead ? $call->lead->telefone : '-';
            $duracao  = $call->duracao ? gmdate('i:s', $call->duracao) : '-';

            $html .= "
                <tr>
                    <td>{$date}</td>
                    <td>{$nome}</td>
                    <td>{$telefone}</td>
                    <td>{$duracao}</td>
                    <td>{$call->status}</td>
                    <td>{$call->resultado}</td>
                </tr>
            ";
        }

        $html .= "
                </tbody>
            </table>
        ";

        return $html;
    }

    private function generateCollectionPDFContent($data)
    {
        $html = "
            <div class='stats'>
                <div class='stat-card'>
                    <div class='stat-value'>{$data['stats']['total']}</div>
                    <div class='stat-label'>Total de Chamadas</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-value'>{$data['stats']['validation_success']}</div>
                    <div class='stat-label'>Validações Bem-Sucedidas</div>
                </div>
                <div class='stat-card'>
                    <div class='stat-value'>{$data['stats']['validation_failed']}</div>
                    <div class='stat-label'>Validações Falhadas</div>
                </div>
            </div>

            <h2>Detalhes das Chamadas</h2>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Contato</th>
                        <th>Telefone</th>
                        <th>Duração</th>
                        <th>Validação</th>
                        <th>Tentativas</th>
                    </tr>
                </thead>
                <tbody>
        ";

        foreach ($data['calls'] as $call) {
            $date      = $call->created_at->format('d/m/Y H:i');
            $nome      = $call->contato ? $call->contato->nome : '-';
            $telefone  = $call->contato ? $call->contato->telefone : '-';
            $duracao   = $call->duracao ? gmdate('i:s', $call->duracao) : '-';
            $validacao = $call->validacao_sucesso ? 'Sucesso' : 'Falha';

            $html .= "
                <tr>
                    <td>{$date}</td>
                    <td>{$nome}</td>
                    <td>{$telefone}</td>
                    <td>{$duracao}</td>
                    <td>{$validacao}</td>
                    <td>{$call->tentativas_validacao}</td>
                </tr>
            ";
        }

        $html .= "
                </tbody>
            </table>
        ";

        return $html;
    }

    private function generateGeneralPDFContent($data)
    {
        $salesHtml      = $this->generateSalesPDFContent($data['sales']);
        $collectionHtml = $this->generateCollectionPDFContent($data['collection']);

        return "
            <h2>Vendas</h2>
            {$salesHtml}
            <hr style='margin: 40px 0;'>
            <h2>Cobrança</h2>
            {$collectionHtml}
        ";
    }
}
