<?php

namespace App\Services;

class NumberToWordsService
{
    /**
     * Converte valor monetário para texto por extenso em português.
     * Ex: 1234.56 → "Mil Duzentos e Trinta e Quatro Reais e Cinquenta e Seis Centavos"
     */
    public function valorPorExtenso($valor): string
    {
        $partes   = explode('.', number_format($valor, 2, '.', ''));
        $reais    = $partes[0];
        $centavos = isset($partes[1]) ? $partes[1] : '00';

        $extensoReais = $this->converterNumero($reais);

        if ($reais == '1') {
            $extensoReais .= ' Real';
        } elseif ($reais == '0') {
            $extensoReais = '';
        } else {
            $extensoReais .= ' Reais';
        }

        $extensoCentavos = '';
        if ($centavos != '00' && $centavos != '0') {
            $extensoCentavos = $this->converterNumero($centavos);

            if ($centavos == '01' || $centavos == '1') {
                $extensoCentavos .= ' Centavo';
            } else {
                $extensoCentavos .= ' Centavos';
            }
        }

        $resultado = trim($extensoReais);
        if ($extensoCentavos != '') {
            if ($resultado != '') {
                $resultado .= ' e ' . $extensoCentavos;
            } else {
                $resultado = $extensoCentavos;
            }
        }

        if ($resultado == '') {
            $resultado = 'Zero Reais';
        }

        return ucfirst($resultado);
    }

    /**
     * Converte número inteiro para texto por extenso em português.
     */
    public function converterNumero($numero): string
    {
        $unidades = [
            '', 'Um', 'Dois', 'Três', 'Quatro', 'Cinco', 'Seis', 'Sete', 'Oito', 'Nove',
            'Dez', 'Onze', 'Doze', 'Treze', 'Catorze', 'Quinze', 'Dezesseis', 'Dezessete',
            'Dezoito', 'Dezenove',
        ];

        $dezenas = [
            '', '', 'Vinte', 'Trinta', 'Quarenta', 'Cinquenta', 'Sessenta',
            'Setenta', 'Oitenta', 'Noventa',
        ];

        $centenas = [
            '', 'Cem', 'Duzentos', 'Trezentos', 'Quatrocentos', 'Quinhentos',
            'Seiscentos', 'Setecentos', 'Oitocentos', 'Novecentos',
        ];

        $numero = ltrim($numero, '0');
        if ($numero == '') {
            return 'Zero';
        }

        $num = intval($numero);

        if ($num < 20) {
            return $unidades[$num];
        } elseif ($num < 100) {
            $dezena    = floor($num / 10);
            $unidade   = $num % 10;
            $resultado = $dezenas[$dezena];
            if ($unidade > 0) {
                $resultado .= ' e ' . $unidades[$unidade];
            }
            return $resultado;
        } elseif ($num < 1000) {
            $centena   = floor($num / 100);
            $resto     = $num % 100;
            $resultado = $centenas[$centena];

            if ($centena == 1 && $resto > 0) {
                $resultado = 'Cento';
            }

            if ($resto > 0) {
                if ($resto < 20) {
                    $resultado .= ' e ' . $unidades[$resto];
                } else {
                    $dezena     = floor($resto / 10);
                    $unidade    = $resto % 10;
                    $resultado .= ' e ' . $dezenas[$dezena];
                    if ($unidade > 0) {
                        $resultado .= ' e ' . $unidades[$unidade];
                    }
                }
            }
            return $resultado;
        } elseif ($num < 1000000) {
            $mil   = floor($num / 1000);
            $resto = $num % 1000;

            if ($mil == 1) {
                $resultado = 'Mil';
            } else {
                $resultado = $this->converterNumero($mil) . ' Mil';
            }

            if ($resto > 0) {
                if ($resto < 100 || ($resto % 100 == 0)) {
                    $resultado .= ' e ' . $this->converterNumero($resto);
                } else {
                    $resultado .= ' ' . $this->converterNumero($resto);
                }
            }
            return $resultado;
        } else {
            return 'Número muito grande para conversão';
        }
    }

    /**
     * Converte número de parcelas para texto por extenso.
     */
    public function converterParcelasPorExtenso(int $numero): string
    {
        $mapa = [
            1  => 'uma parcela',
            2  => 'duas parcelas',
            3  => 'três parcelas',
            6  => 'seis parcelas',
            12 => 'doze parcelas',
            24 => 'vinte e quatro parcelas',
            36 => 'trinta e seis parcelas',
            48 => 'quarenta e oito parcelas',
            60 => 'sessenta parcelas',
        ];

        return $mapa[$numero] ?? "{$numero} parcelas";
    }
}
