<?php

namespace App\Services;

use App\Models\EmpresaIntegracao;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class IntegracaoService
{
    /**
     * Resolve credenciais para uma empresa específica.
     * Fallback: se a empresa não tem credenciais, usa env() global.
     */
    public static function getCredentials($empresaId): array
    {
        $integracao = null;

        if ($empresaId) {
            $integracao = EmpresaIntegracao::where('empresa_id', $empresaId)->first();
        }

        return [
            'retell_api_key'        => self::decryptField($integracao, 'retell_api_key', env('RETELL_API_KEY')),
            'retell_agent_id'       => $integracao->retell_agent_id ?? env('RETELL_AGENT_ID'),
            'retell_agent_id_sales' => $integracao->retell_agent_id_sales ?? env('RETELL_AGENT_ID_SALES'),
            'retell_webhook_secret' => self::decryptField($integracao, 'retell_webhook_secret', env('RETELL_WEBHOOK_SECRET')),
            'from_number'           => self::normalizePhone($integracao->retell_from_number ?? env('RETELL_FROM_NUMBER')),
        ];
    }

    /**
     * Criptografa campos sensíveis antes de salvar.
     * Recebe dados com nomes de coluna DB (retell_api_key, etc).
     */
    public static function encryptCredentials(array $dados): array
    {
        $resultado = [];

        foreach ($dados as $chave => $valor) {
            if (EmpresaIntegracao::isEncrypted($chave) && !empty($valor)) {
                $resultado[$chave] = Crypt::encryptString($valor);
            } else {
                $resultado[$chave] = $valor;
            }
        }

        return $resultado;
    }

    /**
     * Mascara credenciais para exibição no frontend.
     * Retorna chaves com prefixo integracao_ para compatibilidade com frontend.
     */
    public static function maskCredentials(?EmpresaIntegracao $integracao): array
    {
        $resultado = [];

        foreach (EmpresaIntegracao::FIELD_MAP as $dbCol => $apiKey) {
            $valor = $integracao ? $integracao->{$dbCol} : null;

            if (empty($valor)) {
                $resultado[$apiKey] = '';
                continue;
            }

            if (EmpresaIntegracao::isVisible($dbCol)) {
                // Campos não-sensíveis retornados em texto puro (ex: voz, velocidade)
                $resultado[$apiKey] = $valor;
            } elseif (EmpresaIntegracao::isEncrypted($dbCol)) {
                try {
                    $valorReal = Crypt::decryptString($valor);
                    $resultado[$apiKey] = self::mask($valorReal);
                } catch (\Exception $e) {
                    $resultado[$apiKey] = '••••••(erro)';
                }
            } else {
                $resultado[$apiKey] = self::mask($valor);
            }
        }

        return $resultado;
    }

    /**
     * Resolve credenciais Twilio para uma empresa específica.
     * Fallback: se a empresa não tem credenciais, usa env() global.
     */
    public static function getTwilioCredentials($empresaId): array
    {
        $integracao = null;

        if ($empresaId) {
            $integracao = EmpresaIntegracao::where('empresa_id', $empresaId)->first();
        }

        return [
            'twilio_account_sid' => self::decryptField($integracao, 'twilio_account_sid', env('TWILIO_ACCOUNT_SID')),
            'twilio_auth_token'  => self::decryptField($integracao, 'twilio_auth_token', env('TWILIO_AUTH_TOKEN')),
            'twilio_from_number' => self::normalizePhone($integracao->twilio_from_number ?? env('TWILIO_FROM_NUMBER')),
            'twilio_voice'       => $integracao->twilio_voice ?? env('TWILIO_VOICE', 'Polly.Camila'),
            'twilio_speech_rate' => $integracao->twilio_speech_rate ?? env('TWILIO_SPEECH_RATE'),
        ];
    }

    /**
     * Verifica se a empresa tem credenciais próprias configuradas.
     */
    public static function hasOwnCredentials($empresaId): bool
    {
        $integracao = EmpresaIntegracao::where('empresa_id', $empresaId)->first();
        if (!$integracao) return false;

        return !empty($integracao->retell_api_key) || !empty($integracao->retell_agent_id);
    }

    /**
     * Descriptografa campo do model ou retorna default.
     */
    private static function decryptField(?EmpresaIntegracao $integracao, string $campo, $default)
    {
        if (!$integracao) {
            return $default;
        }

        $valor = $integracao->{$campo};

        if (empty($valor)) {
            return $default;
        }

        if (!EmpresaIntegracao::isEncrypted($campo)) {
            return $valor;
        }

        try {
            return Crypt::decryptString($valor);
        } catch (\Exception $e) {
            Log::warning("[INTEGRACAO] Erro ao descriptografar {$campo}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Normaliza telefone para formato E.164 (+5511999887766)
     */
    private static function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) <= 11) {
            $digits = '55' . $digits;
        }

        return '+' . $digits;
    }

    private static function mask(string $valor): string
    {
        $len = strlen($valor);
        if ($len <= 6) return str_repeat('•', $len);
        return str_repeat('•', $len - 6) . substr($valor, -6);
    }
}
