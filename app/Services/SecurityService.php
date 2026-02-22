<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class SecurityService
{
    /**
     * Valida CPF (dígitos verificadores)
     */
    public function validarCpf(string $cpf): bool
    {
        // Remove formatação
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se todos os dígitos são iguais (CPF inválido)
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Valida primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += intval($cpf[$i]) * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = ($resto < 2) ? 0 : 11 - $resto;

        if (intval($cpf[9]) != $digito1) {
            return false;
        }

        // Valida segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += intval($cpf[$i]) * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = ($resto < 2) ? 0 : 11 - $resto;

        if (intval($cpf[10]) != $digito2) {
            return false;
        }

        return true;
    }

    /**
     * Remove formatação do CPF (mantém apenas números)
     */
    public function limparCpf(string $cpf): string
    {
        return preg_replace('/[^0-9]/', '', $cpf);
    }

    /**
     * Extrai os primeiros 3 dígitos do CPF
     */
    public function getPrimeirosDigitosCpf(string $cpf): string
    {
        $cpfLimpo = $this->limparCpf($cpf);
        return substr($cpfLimpo, 0, 3);
    }

    /**
     * Criptografa o CPF
     */
    public function encryptCpf(string $cpf): string
    {
        $cpfLimpo = $this->limparCpf($cpf);
        return Crypt::encryptString($cpfLimpo);
    }

    /**
     * Descriptografa o CPF
     */
    public function decryptCpf(string $cpfCriptografado): string
    {
        try {
            return Crypt::decryptString($cpfCriptografado);
        } catch (\Exception $e) {
            \Log::error('Erro ao descriptografar CPF: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Compara os primeiros 3 dígitos do CPF
     */
    public function compararPrimeirosDigitosCpf(string $cpfCriptografado, string $primeirosDigitosInformados): bool
    {
        $cpfDescriptografado = $this->decryptCpf($cpfCriptografado);

        if (empty($cpfDescriptografado)) {
            return false;
        }

        $primeirosDigitosReais = substr($cpfDescriptografado, 0, 3);
        $primeirosDigitosInformados = $this->limparCpf($primeirosDigitosInformados);

        return $primeirosDigitosReais === $primeirosDigitosInformados;
    }

    /**
     * Formata CPF para exibição (XXX.XXX.XXX-XX)
     */
    public function formatarCpf(string $cpf): string
    {
        $cpfLimpo = $this->limparCpf($cpf);

        if (strlen($cpfLimpo) != 11) {
            return $cpf;
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($cpfLimpo, 0, 3),
            substr($cpfLimpo, 3, 3),
            substr($cpfLimpo, 6, 3),
            substr($cpfLimpo, 9, 2)
        );
    }

    /**
     * Mascara o CPF para exibição segura (XXX.XXX.XXX-XX → XXX.***.***-XX)
     */
    public function mascararCpf(string $cpf): string
    {
        $cpfLimpo = $this->limparCpf($cpf);

        if (strlen($cpfLimpo) != 11) {
            return '***.***.***-**';
        }

        return sprintf(
            '%s.***.***-%s',
            substr($cpfLimpo, 0, 3),
            substr($cpfLimpo, 9, 2)
        );
    }

    /**
     * Valida data de nascimento
     */
    public function validarDataNascimento(string $dataNascimento): bool
    {
        try {
            $data = Carbon::parse($dataNascimento);

            // Data não pode ser no futuro
            if ($data->isFuture()) {
                return false;
            }

            // Data não pode ser há mais de 120 anos
            if ($data->diffInYears(Carbon::now()) > 120) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Compara datas de nascimento
     */
    public function compararDataNascimento(string $dataArmazenada, string $dataInformada): bool
    {
        try {
            $data1 = Carbon::parse($dataArmazenada)->format('Y-m-d');
            $data2 = Carbon::parse($dataInformada)->format('Y-m-d');

            return $data1 === $data2;
        } catch (\Exception $e) {
            \Log::error('Erro ao comparar datas: ' . $e->getMessage());
            return false;
        }
    }
}
