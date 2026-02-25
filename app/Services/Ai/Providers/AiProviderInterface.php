<?php

namespace App\Services\Ai\Providers;

interface AiProviderInterface
{
    /**
     * Gera texto a partir de contexto simplificado.
     * Retorna apenas string de texto para leitura por voz.
     *
     * @param string $systemPrompt  Prompt fixo do sistema
     * @param array  $context       Dados simplificados (nunca JSON bruto da API)
     * @return string               Texto gerado para TTS
     */
    public function generate(string $systemPrompt, array $context): string;
}
