<?php

namespace App\Services\Ai;

use App\Models\Empresa;
use App\Services\Ai\Providers\AiProviderInterface;
use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\IntegracaoService;
use Illuminate\Support\Facades\Log;

class AiMessageService
{
    /**
     * Prompt padrão do sistema (usado quando a empresa não tem prompt personalizado).
     * Tom: amigável e acolhedor.
     */
    private const DEFAULT_PROMPT = <<<'PROMPT'
Você é a Angélica, uma atendente simpática e acolhedora da central de negociação.
Sua tarefa: receber um MODELO DE MENSAGEM e reescrever com tom amigável e natural.

PERSONALIDADE:
- Calorosa e amigável, como uma amiga que quer ajudar de verdade.
- Use expressões naturais: "fique tranquilo", "estamos aqui para te ajudar".
- Transmita calma e segurança. O cliente não deve se sentir pressionado.
- Positiva: foque nas soluções e benefícios, não no problema.

REGRAS CRÍTICAS:
- Reescreva o MODELO mantendo TODAS as informações e instruções dele (valores, opções, pedidos de digitar, etc).
- NUNCA invente informações, valores, datas ou opções que não estão no modelo.
- NUNCA adicione explicações sobre "não conseguir capturar", "erro no sistema" ou qualquer cenário inventado.
- NUNCA altere as opções de pagamento. Repita-as como estão no modelo.
- NUNCA mencione que é uma IA, robô ou assistente virtual.
- Use linguagem brasileira coloquial (mas educada). Frases curtas.
- Adapte siglas para forma falada (ex: "CPF" com pausas entre letras).
- Retorne APENAS o texto da fala, sem aspas, sem prefixos, sem formatação.

REGRA DE CONTINUIDADE (MUITO IMPORTANTE):
- Esta é uma ligação telefônica contínua. Você fala VÁRIAS mensagens durante a MESMA ligação.
- Cumprimente o cliente pelo nome APENAS na primeira mensagem (step START).
- Nas mensagens seguintes, NÃO repita "Olá", "Oi", saudações ou o nome do cliente no início.
- Comece direto com o conteúdo. Exemplo: em vez de "Olá Rafael, encontrei sua pendência...", diga apenas "Encontrei sua pendência...".
- O campo "step" indica em que momento da ligação estamos. Se NÃO for "START", não cumprimente.
PROMPT;

    /** Tamanho máximo permitido da resposta da IA (caracteres) */
    private const MAX_RESPONSE_LENGTH = 1500;

    /**
     * Gera mensagem para um step do fluxo IVR.
     *
     * Se use_ai=false ou se a IA falhar, retorna a mensagem padrão (fallback).
     * A IA NUNCA controla fluxo, step ou lógica de negócio.
     *
     * @param Empresa $empresa       Empresa/tenant atual
     * @param string  $step          Step atual do fluxo (para log)
     * @param array   $data          Dados simplificados (NUNCA JSON bruto)
     * @param string  $defaultMessage Mensagem padrão (fallback obrigatório)
     * @return string                 Texto final para TTS
     */
    public function generateMessage(Empresa $empresa, string $step, array $data, string $defaultMessage): string
    {
        // Se IA desativada para esta empresa, retornar padrão imediatamente
        if (!$empresa->use_ai) {
            return $defaultMessage;
        }

        try {
            $provider = $this->resolveProvider($empresa);

            if (!$provider) {
                Log::warning("[AI] Provider não configurado para empresa {$empresa->id} - usando fallback");
                return $defaultMessage;
            }

            $prompt = !empty($empresa->ai_prompt) ? $empresa->ai_prompt : self::DEFAULT_PROMPT;

            // Incluir mensagem padrão como modelo e step atual para contexto
            $data['modelo_mensagem'] = $defaultMessage;
            $data['step'] = $step;

            $text = $provider->generate($prompt, $data);

            // Sanitizar resposta
            $text = $this->sanitize($text);

            if (empty($text)) {
                Log::warning("[AI] Resposta vazia após sanitização | empresa={$empresa->id} step={$step}");
                return $defaultMessage;
            }

            Log::info("[AI] Mensagem gerada com sucesso | empresa={$empresa->id} step={$step} length=" . strlen($text));

            return $text;

        } catch (\Exception $e) {
            // Fallback silencioso - a ligação NUNCA pode falhar por erro de IA
            Log::error("[AI] Erro ao gerar mensagem | empresa={$empresa->id} step={$step} error={$e->getMessage()}");
            return $defaultMessage;
        }
    }

    /**
     * Resolve o provider de IA baseado no ai_model da empresa.
     */
    private function resolveProvider(Empresa $empresa): ?AiProviderInterface
    {
        $apiKey = $this->resolveApiKey($empresa);

        if (empty($apiKey)) {
            return null;
        }

        $model = $empresa->ai_model ?: 'gpt-4o';

        // Futuro: adicionar mais providers aqui (Claude, Gemini, etc)
        return match (true) {
            str_starts_with($model, 'gpt-') => new OpenAiProvider($apiKey, $model),
            default                         => new OpenAiProvider($apiKey, $model),
        };
    }

    /**
     * Resolve API key: primeiro tenta por empresa, depois fallback .env.
     */
    private function resolveApiKey(Empresa $empresa): ?string
    {
        // Tentar chave da empresa (via empresa_integracoes)
        $integracao = $empresa->integracao;

        if ($integracao && !empty($integracao->openai_api_key)) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($integracao->openai_api_key);
            } catch (\Exception $e) {
                Log::warning("[AI] Erro ao descriptografar openai_api_key empresa={$empresa->id}");
            }
        }

        // Fallback: chave global do .env
        return env('OPENAI_API_KEY');
    }

    /**
     * Sanitiza resposta da IA para segurança e compatibilidade com TTS.
     */
    private function sanitize(string $text): string
    {
        // Remover possíveis tags HTML/XML
        $text = strip_tags($text);

        // Remover aspas no início/fim (IA às vezes coloca)
        $text = trim($text, "\"'\xe2\x80\x9c\xe2\x80\x9d\xe2\x80\x98\xe2\x80\x99");

        // Remover quebras de linha excessivas (TTS não precisa)
        $text = preg_replace('/\n+/', ' ', $text);

        // Remover espaços múltiplos
        $text = preg_replace('/\s+/', ' ', $text);

        // Truncar se muito longo
        if (strlen($text) > self::MAX_RESPONSE_LENGTH) {
            $text = mb_substr($text, 0, self::MAX_RESPONSE_LENGTH);
            // Cortar na última frase completa
            $lastPeriod = mb_strrpos($text, '.');
            if ($lastPeriod !== false && $lastPeriod > self::MAX_RESPONSE_LENGTH * 0.5) {
                $text = mb_substr($text, 0, $lastPeriod + 1);
            }
        }

        return trim($text);
    }
}
