<?php

namespace App\Services\Ai\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OpenAiProvider implements AiProviderInterface
{
    private Client $client;
    private string $apiKey;
    private string $model;

    private const API_URL = 'https://api.openai.com/v1/chat/completions';
    private const TIMEOUT = 5;          // segundos - rápido para não travar ligação
    private const MAX_TOKENS = 250;     // limita tamanho da resposta
    private const TEMPERATURE = 0.7;    // criatividade controlada

    public function __construct(string $apiKey, string $model = 'gpt-4o-mini')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->client = new Client([
            'timeout'         => self::TIMEOUT,
            'connect_timeout' => 3,
        ]);
    }

    /**
     * Gera texto via OpenAI Chat Completions API.
     *
     * @throws \RuntimeException se a API retornar erro
     */
    public function generate(string $systemPrompt, array $context): string
    {
        $userMessage = $this->buildUserMessage($context);


        $response = $this->client->post(self::API_URL, [
            'headers' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'model'       => $this->model,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'max_tokens'  => self::MAX_TOKENS,
                'temperature' => self::TEMPERATURE,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        $text = $data['choices'][0]['message']['content'] ?? '';
        $text = trim($text);

        if (empty($text)) {
            throw new \RuntimeException('OpenAI retornou resposta vazia');
        }


        return $text;
    }

    /**
     * Monta mensagem do usuário a partir do contexto simplificado.
     * Nunca envia JSON bruto - converte para texto legível.
     */
    private function buildUserMessage(array $context): string
    {
        $lines = [];

        foreach ($context as $key => $value) {
            $label = str_replace('_', ' ', $key);
            $lines[] = ucfirst($label) . ': ' . $value;
        }

        return implode("\n", $lines);
    }
}
