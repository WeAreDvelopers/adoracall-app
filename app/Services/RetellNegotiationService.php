<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RetellNegotiationService
{
    protected AdoraApiService $adoraApi;

    public function __construct(?AdoraApiService $adoraApi = null)
    {
        $this->adoraApi = $adoraApi ?? new AdoraApiService();
    }

    /**
     * Busca negociação na API Adora e retorna dados normalizados para o Retell.
     */
    public function buscarNegociacao(string $cpf): array
    {
        $proposal = $this->adoraApi->consultarEAguardar($cpf);

        if (empty($proposal['debits'][0])) {
            throw new \RuntimeException('Nenhum débito encontrado para este cliente.');
        }

        return $this->normalizar($proposal);
    }

    /**
     * Normaliza os dados da proposta para o formato padronizado do Retell.
     */
    private function normalizar(array $proposal): array
    {
        $debit = $proposal['debits'][0];
        $options = $debit['paymentOptions'] ?? [];

        // Separar à vista e parceladas
        $aVista = array_filter($options, fn($opt) => ($opt['installmentNumber'] ?? 0) <= 1);
        $parceladas = array_filter($options, fn($opt) => ($opt['installmentNumber'] ?? 0) > 1);

        // Ordenar parceladas por número de parcelas (menor → maior)
        usort($parceladas, fn($a, $b) => ($a['installmentNumber'] ?? 0) <=> ($b['installmentNumber'] ?? 0));

        return [
            'customer_name' => $proposal['name'] ?? '',
            'debit_id'      => $debit['id'],
            'hash'          => $proposal['hash'] ?? '',
            'total_value'   => $debit['totalValue'] ?? 0,
            'valid_date'    => $debit['validDate'] ?? null,

            'best_option'          => $this->selecionarMelhorOpcao($options),
            'cash_options'         => array_map(fn($opt) => $this->formatarOpcao($opt), array_values($aVista)),
            'installment_options'  => array_map(fn($opt) => $this->formatarOpcao($opt), array_values($parceladas)),
        ];
    }

    /**
     * best_option: opção com suggested=true, senão maior percDiscount.
     */
    private function selecionarMelhorOpcao(array $options): ?array
    {
        if (empty($options)) {
            return null;
        }

        // Prioridade 1: suggested = true
        foreach ($options as $opt) {
            if (!empty($opt['suggested'])) {
                return $this->formatarMelhorOpcao($opt);
            }
        }

        // Prioridade 2: maior percDiscount
        $melhor = null;
        $maiorDesconto = -1;

        foreach ($options as $opt) {
            $desconto = $opt['percDiscount'] ?? 0;
            if ($desconto > $maiorDesconto) {
                $maiorDesconto = $desconto;
                $melhor = $opt;
            }
        }

        return $melhor ? $this->formatarMelhorOpcao($melhor) : null;
    }

    private function formatarOpcao(array $opt): array
    {
        return [
            'id'                => $opt['id'],
            'totalValue'        => $opt['totalValue'] ?? 0,
            'percDiscount'      => $opt['percDiscount'] ?? 0,
            'firstValue'        => $opt['firstValue'] ?? $opt['installmentValue'] ?? 0,
            'installmentNumber' => $opt['installmentNumber'] ?? 0,
            'installmentValue'  => $opt['installmentValue'] ?? 0,
        ];
    }

    private function formatarMelhorOpcao(array $opt): array
    {
        return [
            'id'           => $opt['id'],
            'totalValue'   => $opt['totalValue'] ?? 0,
            'percDiscount' => $opt['percDiscount'] ?? 0,
        ];
    }
}
