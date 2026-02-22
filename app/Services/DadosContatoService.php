<?php

namespace App\Services;

use App\Models\Contato;
use Carbon\Carbon;
use Illuminate\Support\Arr;

/**
 * Serviço para enriquecer dados de contatos
 *
 * Busca informações detalhadas sobre dívidas, propostas, etc.
 * Atualmente mockado, pronto para integrar com API externa
 */
class DadosContatoService
{
    /**
     * Busca dados enriquecidos de um contato
     *
     * Retorna informações:
     * - Dados pessoais
     * - Dados da dívida principal
     * - Histórico de tentativas
     * - Propostas disponíveis
     * - Restrições e alertas
     */
    public function obterDadosEnriquecidos($contatoId)
    {
        $contato = Contato::find($contatoId);

        if (!$contato) {
            throw new \Exception("Contato não encontrado: {$contatoId}");
        }

        return [
            'contato' => $this->formatarDadosPessoais($contato),
            'divida' => $this->obterDadosDivida($contato),
            'historico' => $this->obterHistoricoTentativas($contato),
            'propostas' => $this->obterPropostasDisponiveis($contato),
            'alertas' => $this->verificarAlertas($contato),
            'instrucoes_ura' => $this->gerarInstrucoesURA($contato),
        ];
    }

    /**
     * Formata dados pessoais do contato
     */
    private function formatarDadosPessoais($contato)
    {
        return [
            'id' => $contato->id,
            'nome_completo' => $contato->nome_completo ?? ($contato->nome . ' ' . ($contato->sobrenome ?? '')),
            'primeiro_nome' => $contato->nome,
            'telefone' => $contato->telefone,
            'cpf_mascarado' => $this->mascaraCPF($contato->cpf_primeiros_digitos),
            'data_nascimento' => $contato->data_nascimento?->format('d/m/Y'),
            'mailing_id' => $contato->mailing_id,
        ];
    }

    /**
     * Busca dados detalhados da dívida (mockado)
     *
     * Aqui você integrará com sua API de dívidas
     * Por enquanto, retorna dados mockados baseados no contato importado
     */
    private function obterDadosDivida($contato)
    {
        // TODO: Integrar com API externa de dívidas
        // $divida = ExternalAPI::getDivida($contato->cpf);

        // Por enquanto, usa dados do contato importado + dados mockados
        $hoje = Carbon::now();
        $vencimento = $contato->vencimento ? Carbon::parse($contato->vencimento) : null;
        $diasAtrasado = $vencimento ? $vencimento->diffInDays($hoje, false) : 0;

        return [
            'valor_original' => floatval($contato->valor_debito),
            'valor_atual' => $this->calcularValorComJuros($contato->valor_debito, $diasAtrasado),
            'valor_debito' => floatval($contato->valor_debito),
            'valor_juros' => $this->calcularJuros($contato->valor_debito, $diasAtrasado),
            'valor_multa' => $this->calcularMulta($contato->valor_debito),
            'data_vencimento' => $contato->vencimento?->format('d/m/Y'),
            'dias_atrasado' => max(0, $diasAtrasado),
            'empresa_credora' => $contato->empresa_credora ?? 'Empresa Credora ABC',
            'documento_origem' => $this->gerarNumeroDocumento(),
            'tipo_divida' => $this->classificarDivida($contato->valor_debito),
            'status_divida' => 'ativa',
        ];
    }

    /**
     * Busca histórico de tentativas de contato
     */
    private function obterHistoricoTentativas($contato)
    {
        $ligacoes = $contato->ligacoes()->latest()->limit(5)->get();

        return [
            'total_tentativas' => $contato->tentativas ?? 0,
            'ultima_tentativa' => $contato->ultima_tentativa?->format('d/m/Y H:i'),
            'ultima_ligacao' => $contato->ultima_ligacao?->format('d/m/Y H:i'),
            'ultimas_ligacoes' => $ligacoes->map(function ($ligacao) {
                return [
                    'data' => $ligacao->created_at->format('d/m/Y H:i'),
                    'duracao' => $ligacao->duracao ?? 0,
                    'resultado' => $ligacao->resultado ?? 'desconhecido',
                ];
            })->toArray(),
        ];
    }

    /**
     * Obtém propostas disponíveis para o contato (mockado)
     */
    private function obterPropostasDisponiveis($contato)
    {
        $valorDivida = floatval($contato->valor_debito);

        // Calcular propostas mockadas
        $propostas = [
            // Proposta 1: À Vista
            [
                'id' => 1,
                'tipo' => 'vista',
                'descricao' => 'Pagamento à vista',
                'valor_original' => $valorDivida,
                'valor_desconto' => $this->calcularDesconto($valorDivida, 0.10), // 10% desconto
                'valor_final' => $valorDivida * 0.90,
                'desconto_percentual' => 10,
                'condicoes' => 'Desconto de 10% para pagamento imediato',
                'validade_dias' => 7,
            ],

            // Proposta 2: 2 Parcelas
            [
                'id' => 2,
                'tipo' => 'parcelado',
                'descricao' => 'Parcelado em 2x',
                'valor_original' => $valorDivida,
                'valor_desconto' => $this->calcularDesconto($valorDivida, 0.05), // 5% desconto
                'valor_final' => $valorDivida * 0.95,
                'desconto_percentual' => 5,
                'parcelas' => 2,
                'valor_parcela' => ($valorDivida * 0.95) / 2,
                'intervalo_dias' => 30,
                'condicoes' => '2 parcelas de ' . number_format(($valorDivida * 0.95) / 2, 2, ',', '.'),
                'validade_dias' => 7,
            ],

            // Proposta 3: 3 Parcelas
            [
                'id' => 3,
                'tipo' => 'parcelado',
                'descricao' => 'Parcelado em 3x',
                'valor_original' => $valorDivida,
                'valor_desconto' => 0,
                'valor_final' => $valorDivida,
                'desconto_percentual' => 0,
                'parcelas' => 3,
                'valor_parcela' => $valorDivida / 3,
                'intervalo_dias' => 30,
                'condicoes' => '3 parcelas de ' . number_format($valorDivida / 3, 2, ',', '.'),
                'validade_dias' => 5,
            ],

            // Proposta 4: Acordo Especial (maior desconto, mais prazos)
            [
                'id' => 4,
                'tipo' => 'acordo_especial',
                'descricao' => 'Acordo Especial - Maior flexibilidade',
                'valor_original' => $valorDivida,
                'valor_desconto' => $this->calcularDesconto($valorDivida, 0.20), // 20% desconto
                'valor_final' => $valorDivida * 0.80,
                'desconto_percentual' => 20,
                'parcelas' => 6,
                'valor_parcela' => ($valorDivida * 0.80) / 6,
                'intervalo_dias' => 30,
                'condicoes' => '6 parcelas de ' . number_format(($valorDivida * 0.80) / 6, 2, ',', '.'),
                'requer_confirmacao' => true,
                'validade_dias' => 3,
            ],
        ];

        return [
            'quantidade' => count($propostas),
            'propostas' => $propostas,
            'melhor_proposta' => $propostas[0], // Proposta mais vantajosa (à vista)
        ];
    }

    /**
     * Verifica alertas e restrições do contato
     */
    private function verificarAlertas($contato)
    {
        $alertas = [];

        // Alerta 1: Muitas tentativas
        if ($contato->tentativas >= 3) {
            $alertas[] = [
                'tipo' => 'muitas_tentativas',
                'nivel' => 'alto',
                'mensagem' => 'Este contato tem ' . $contato->tentativas . ' tentativas de ligação',
                'acao' => 'Ofereça propostas mais vantajosas',
            ];
        }

        // Alerta 2: Valor alto
        if ($contato->valor_debito > 5000) {
            $alertas[] = [
                'tipo' => 'valor_alto',
                'nivel' => 'medio',
                'mensagem' => 'Dívida de alto valor (R$ ' . number_format($contato->valor_debito, 2, ',', '.') . ')',
                'acao' => 'Negocie acordos especiais com mais flexibilidade',
            ];
        }

        // Alerta 3: Vencimento próximo
        if ($contato->vencimento) {
            $diasParaVencer = Carbon::now()->diffInDays($contato->vencimento);
            if ($diasParaVencer <= 7 && $diasParaVencer > 0) {
                $alertas[] = [
                    'tipo' => 'vencimento_proximo',
                    'nivel' => 'medio',
                    'mensagem' => 'Vencimento em ' . $diasParaVencer . ' dias',
                    'acao' => 'Apresse negociação',
                ];
            }
        }

        return [
            'quantidade' => count($alertas),
            'alertas' => $alertas,
            'permitir_discagem' => empty($alertas) || !Arr::where($alertas, fn($a) => $a['tipo'] === 'restricao'),
        ];
    }

    /**
     * Gera instruções específicas para a URA
     */
    private function gerarInstrucoesURA($contato)
    {
        $instrucoes = [];

        // Instrução 1: Tom de voz e abordagem
        if ($contato->tentativas > 0) {
            $instrucoes[] = [
                'tipo' => 'tom_abordagem',
                'conteudo' => 'Este é um recontato. Use tom mais empático e ofereça condições especiais.',
            ];
        }

        // Instrução 2: Ênfase em propostas
        if ($contato->valor_debito > 1000) {
            $instrucoes[] = [
                'tipo' => 'enfase_negociacao',
                'conteudo' => 'Enfatize as opções de parcelamento e acordos especiais disponíveis.',
            ];
        }

        // Instrução 3: Tempo de ligação esperado
        $instrucoes[] = [
            'tipo' => 'tempo_esperado',
            'minutos' => $contato->tentativas > 0 ? 5 : 3,
            'conteudo' => 'Tempo estimado: ' . ($contato->tentativas > 0 ? '5 minutos' : '3 minutos'),
        ];

        return [
            'quantidade' => count($instrucoes),
            'instrucoes' => $instrucoes,
        ];
    }

    /**
     * Calcula valor com juros e multa (mockado)
     */
    private function calcularValorComJuros($valor, $diasAtrasado)
    {
        $juros = $this->calcularJuros($valor, $diasAtrasado);
        $multa = $this->calcularMulta($valor);

        return round($valor + $juros + $multa, 2);
    }

    /**
     * Calcula juros (2% ao mês = ~0.067% ao dia)
     */
    private function calcularJuros($valor, $diasAtrasado)
    {
        if ($diasAtrasado <= 0) return 0;

        $taxaDiaria = 0.0206 / 30; // 2% ao mês
        return round($valor * $taxaDiaria * $diasAtrasado, 2);
    }

    /**
     * Calcula multa (10% do valor)
     */
    private function calcularMulta($valor)
    {
        return round($valor * 0.10, 2);
    }

    /**
     * Calcula desconto
     */
    private function calcularDesconto($valor, $percentual)
    {
        return round($valor * $percentual, 2);
    }

    /**
     * Classifica tipo de dívida
     */
    private function classificarDivida($valor)
    {
        if ($valor < 500) return 'pequena';
        if ($valor < 2000) return 'media';
        if ($valor < 5000) return 'alta';
        return 'muito_alta';
    }

    /**
     * Gera número de documento mockado
     */
    private function gerarNumeroDocumento()
    {
        return 'DOC-' . strtoupper(uniqid());
    }

    /**
     * Mascara CPF para exibição (mostra apenas últimos dígitos)
     */
    private function mascaraCPF($cpfDigitos)
    {
        return '***-***-' . ($cpfDigitos ?? '000');
    }
}
