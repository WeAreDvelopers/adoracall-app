<?php

namespace App\Services;

use App\Models\Contato;
use App\Models\Proposta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProposalService
{
    /**
     * Gera propostas baseadas no perfil do cliente
     */
    public function gerarPropostasParaCliente(Contato $contato): array
    {
        try {
            Log::info("📊 Gerando propostas para cliente: {$contato->nome_completo}");

            // Analisar perfil do cliente
            $perfil = $this->analisarPerfil($contato);

            Log::info("🎯 Perfil do cliente:", $perfil);

            // Gerar propostas baseadas no perfil
            $propostas = match($perfil['nivel_risco']) {
                'alto' => $this->propostasAltoRisco($contato, $perfil),
                'medio' => $this->propostasMedioRisco($contato, $perfil),
                'baixo' => $this->propostasBassoRisco($contato, $perfil),
            };

            Log::info("✅ " . count($propostas) . " propostas geradas para: {$contato->nome_completo}");

            // Salvar propostas no banco
            foreach ($propostas as $proposta_data) {
                Proposta::create([
                    'contato_id' => $contato->id,
                    'tipo' => $proposta_data['tipo'],
                    'valor_original' => $contato->valor_debito,
                    'valor_final' => $proposta_data['valor_final'],
                    'desconto_percentual' => $proposta_data['desconto_percentual'] ?? null,
                    'parcelas' => $proposta_data['parcelas'] ?? 1,
                    'valor_parcela' => $proposta_data['valor_parcela'] ?? null,
                    'valor_entrada' => $proposta_data['valor_entrada'] ?? null,
                    'descricao_agente' => $proposta_data['descricao_agente'],
                    'status' => 'ativa',
                    'expira_em' => Carbon::now()->addHours(24),
                ]);
            }

            return $propostas;

        } catch (\Exception $e) {
            Log::error("❌ Erro ao gerar propostas: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Analisa o perfil de risco do cliente
     */
    private function analisarPerfil(Contato $contato): array
    {
        try {
            // Calcular dias em atraso
            $dias_atraso = Carbon::now()->diffInDays(Carbon::parse($contato->vencimento));

            // Contar tentativas de chamada
            $total_tentativas = $contato->tentativas_chamada ?? 0;

            // Contar acordos anteriores
            $acordos_anteriores = $contato->acordos()->count() ?? 0;
            $acordos_cumpridos = $contato->acordos()
                ->where('status', 'pago')
                ->count() ?? 0;

            // Calcular nível de risco
            $risco = 0;

            // Dias em atraso
            if ($dias_atraso > 180) $risco += 3;
            elseif ($dias_atraso > 90) $risco += 2;
            elseif ($dias_atraso > 30) $risco += 1;

            // Tentativas de contato
            if ($total_tentativas > 5) $risco += 2;
            elseif ($total_tentativas > 2) $risco += 1;

            // Histórico de acordos
            if ($acordos_anteriores > 0) {
                $taxa_cumprimento = $acordos_cumpridos / $acordos_anteriores;
                if ($taxa_cumprimento < 0.3) $risco += 2;
                elseif ($taxa_cumprimento < 0.7) $risco += 1;
            }

            // Determinar nível
            $nivel_risco = $risco >= 5 ? 'alto' : ($risco >= 3 ? 'medio' : 'baixo');

            return [
                'nivel_risco' => $nivel_risco,
                'dias_atraso' => $dias_atraso,
                'tentativas' => $total_tentativas,
                'acordos_anteriores' => $acordos_anteriores,
                'taxa_cumprimento' => $acordos_anteriores > 0 ? $acordos_cumpridos / $acordos_anteriores : null,
                'risco_score' => $risco,
            ];

        } catch (\Exception $e) {
            Log::error("❌ Erro ao analisar perfil: " . $e->getMessage());
            // Retornar perfil padrão em caso de erro
            return [
                'nivel_risco' => 'medio',
                'dias_atraso' => 0,
                'tentativas' => 0,
                'acordos_anteriores' => 0,
                'taxa_cumprimento' => null,
                'risco_score' => 0,
            ];
        }
    }

    /**
     * Propostas para clientes de ALTO RISCO
     */
    private function propostasAltoRisco(Contato $contato, array $perfil): array
    {
        $valor_total = $contato->valor_debito;

        return [
            // Proposta 1: Desconto agressivo à vista
            [
                'tipo' => 'desconto_vista',
                'valor_final' => $valor_total * 0.5,
                'desconto_percentual' => 50,
                'parcelas' => 1,
                'descricao_agente' => 'Desconto de 50% para pagamento à vista até amanhã. De R$ ' .
                    number_format($valor_total, 2, ',', '.') . ' por R$ ' .
                    number_format($valor_total * 0.5, 2, ',', '.'),
            ],
            // Proposta 2: Desconto moderado parcelado
            [
                'tipo' => 'desconto_vista',
                'valor_final' => $valor_total * 0.7,
                'desconto_percentual' => 30,
                'parcelas' => 3,
                'valor_parcela' => ($valor_total * 0.7) / 3,
                'descricao_agente' => 'Desconto de 30% parcelado em 3 vezes. De R$ ' .
                    number_format($valor_total, 2, ',', '.') . ' por 3x de R$ ' .
                    number_format(($valor_total * 0.7) / 3, 2, ',', '.'),
            ],
        ];
    }

    /**
     * Propostas para clientes de MÉDIO RISCO
     */
    private function propostasMedioRisco(Contato $contato, array $perfil): array
    {
        $valor_total = $contato->valor_debito;

        return [
            // Proposta 1: Desconto à vista
            [
                'tipo' => 'desconto_vista',
                'valor_final' => $valor_total * 0.8,
                'desconto_percentual' => 20,
                'parcelas' => 1,
                'descricao_agente' => 'Desconto de 20% para pagamento à vista. De R$ ' .
                    number_format($valor_total, 2, ',', '.') . ' por R$ ' .
                    number_format($valor_total * 0.8, 2, ',', '.'),
            ],
            // Proposta 2: Parcelamento sem juros
            [
                'tipo' => 'parcelamento',
                'valor_final' => $valor_total,
                'desconto_percentual' => 0,
                'parcelas' => 6,
                'valor_parcela' => $valor_total / 6,
                'descricao_agente' => 'Parcelamento em 6 vezes sem juros. 6x de R$ ' .
                    number_format($valor_total / 6, 2, ',', '.'),
            ],
        ];
    }

    /**
     * Propostas para clientes de BAIXO RISCO
     */
    private function propostasBassoRisco(Contato $contato, array $perfil): array
    {
        $valor_total = $contato->valor_debito;

        return [
            // Proposta 1: Parcelamento padrão
            [
                'tipo' => 'parcelamento',
                'valor_final' => $valor_total,
                'desconto_percentual' => 0,
                'parcelas' => 12,
                'valor_parcela' => $valor_total / 12,
                'descricao_agente' => 'Parcelamento em até 12 vezes. 12x de R$ ' .
                    number_format($valor_total / 12, 2, ',', '.'),
            ],
        ];
    }

    /**
     * Obter propostas válidas para um cliente
     */
    public function obterPropostasValidas(Contato $contato): array
    {
        $propostas = Proposta::where('contato_id', $contato->id)
            ->where('status', 'ativa')
            ->where(function ($query) {
                $query->whereNull('expira_em')
                      ->orWhere('expira_em', '>', Carbon::now());
            })
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->toArray();

        return $propostas;
    }
}
