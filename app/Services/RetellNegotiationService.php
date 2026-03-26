<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RetellNegotiationService
{
    protected AdoraApiService $adoraApi;
    protected NumberToWordsService $numberToWords;

    public function __construct(?AdoraApiService $adoraApi = null)
    {
        $this->adoraApi = $adoraApi ?? new AdoraApiService();
        $this->numberToWords = new NumberToWordsService();
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

        $totalValue = $debit['totalValue'] ?? 0;

        return [
            'customer_name'        => $proposal['name'] ?? '',
            'debit_id'             => $debit['id'],
            'hash'                 => $proposal['hash'] ?? '',
            'total_value'          => $totalValue,
            'total_value_formatted' => $this->valorPorExtenso($totalValue),
            'valid_date'           => $debit['validDate'] ?? null,

            'total_options'        => count($options),
            'total_cash'           => count($aVista),
            'total_installments'   => count($parceladas),
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
        $totalValue = $opt['totalValue'] ?? 0;
        $firstValue = $opt['firstValue'] ?? $opt['installmentValue'] ?? 0;
        $installmentValue = $opt['installmentValue'] ?? 0;

        return [
            'id'                        => $opt['id'],
            'totalValue'                => $totalValue,
            'totalValueFormatted'       => $this->valorPorExtenso($totalValue),
            'percDiscount'              => $opt['percDiscount'] ?? 0,
            'firstValue'                => $firstValue,
            'firstValueFormatted'       => $this->valorPorExtenso($firstValue),
            'installmentNumber'         => $opt['installmentNumber'] ?? 0,
            'installmentValue'          => $installmentValue,
            'installmentValueFormatted' => $this->valorPorExtenso($installmentValue),
        ];
    }

    private function formatarMelhorOpcao(array $opt): array
    {
        $totalValue = $opt['totalValue'] ?? 0;

        return [
            'id'                  => $opt['id'],
            'totalValue'          => $totalValue,
            'totalValueFormatted' => $this->valorPorExtenso($totalValue),
            'percDiscount'        => $opt['percDiscount'] ?? 0,
        ];
    }

    /**
     * Converte valor monetário para texto por extenso usando NumberToWordsService.
     */
    private function valorPorExtenso($valor): string
    {
        return $this->numberToWords->valorPorExtenso($valor);
    }
}
