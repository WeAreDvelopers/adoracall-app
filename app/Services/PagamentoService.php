<?php
namespace App\Services;

use App\Models\Contato;
use App\Models\PropostaPagamento;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class PagamentoService
{
    private ?Client $twilioClient = null;
    private string $twilioFromNumber;
    private string $baseUrlPagamento;

    public function __construct(?int $empresaId = null)
    {
        // Auto-resolver empresa_id do container se não fornecido
        if ($empresaId === null && app()->bound('empresa_id')) {
            $empresaId = app('empresa_id');
        }

        // Resolver credenciais por empresa (com fallback para config/env global)
        $creds = IntegracaoService::getCredentials($empresaId);

        $accountSid = config('services.twilio.account_sid');
        $authToken  = config('services.twilio.auth_token');
        $fromNumber = $creds['from_number'] ?: config('services.twilio.from_number', '');

        if ($accountSid && $authToken) {
            $this->twilioClient = new Client($accountSid, $authToken);
        }

        $this->twilioFromNumber = $fromNumber;
        $this->baseUrlPagamento = config('app.payment_base_url', config('app.url') . '/pagamento');
    }

    /**
     * Cria uma proposta de pagamento e envia via SMS
     *
     * @param Contato $contato
     * @param float $valorProposta
     * @param string $tipoProposta (pix, boleto, cartao, link_generico)
     * @param array $opcoes Opções adicionais (desconto, validade, mensagem customizada, etc)
     * @return PropostaPagamento
     */
    public function criarEEnviarProposta(
        Contato $contato,
        float $valorProposta,
        string $tipoProposta = 'link_generico',
        array $opcoes = []
    ): PropostaPagamento {
        try {
            // Cria a proposta
            $proposta = $this->criarProposta($contato, $valorProposta, $tipoProposta, $opcoes);

            // Envia o SMS
            $this->enviarSmsProposta($proposta, $opcoes['mensagem_customizada'] ?? null);

            Log::info("Proposta criada e SMS enviado", [
                'proposta_id' => $proposta->id,
                'contato_id'  => $contato->id,
                'telefone'    => $contato->telefone,
                'valor'       => $valorProposta,
            ]);

            return $proposta;

        } catch (Exception $e) {
            Log::error("Erro ao criar e enviar proposta", [
                'contato_id' => $contato->id,
                'erro'       => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Cria uma proposta de pagamento
     *
     * @param Contato $contato
     * @param float $valorProposta
     * @param string $tipoProposta
     * @param array $opcoes
     * @return PropostaPagamento
     */
    public function criarProposta(
        Contato $contato,
        float $valorProposta,
        string $tipoProposta = 'link_generico',
        array $opcoes = []
    ): PropostaPagamento {
        $valorOriginal = $opcoes['valor_original'] ?? $contato->valor_debito ?? $valorProposta;
        $desconto      = $valorOriginal > 0 ? (($valorOriginal - $valorProposta) / $valorOriginal) * 100 : 0;

        // Gera o link de pagamento
        $linkPagamento = $this->gerarLinkPagamento($contato, $valorProposta, $tipoProposta, $opcoes);

        // Prepara os dados da proposta
        $dadosProposta = [
            'contato_id'               => $contato->id,
            'empresa_id'               => $contato->empresa_id,
            'script_id'                => $opcoes['script_id'] ?? null,
            'mailing_id'               => $contato->mailing_id,
            'telefone'                 => $contato->telefone,
            'nome_cliente'             => $contato->nome,
            'valor_original'           => $valorOriginal,
            'valor_original_numerico'  => floatval($valorOriginal),
            'valor_proposta'           => $valorProposta,
            'valor_proposta_numerico'  => floatval($valorProposta),
            'desconto'                 => round($desconto, 2),
            'tipo_proposta'            => $tipoProposta,
            'link_pagamento'           => $linkPagamento,
            'status'                   => 'gerada',
            'valido_ate'               => $opcoes['valido_ate'] ?? Carbon::now()->addDays(7),
            'expira_em'                => $opcoes['expira_em'] ?? Carbon::now()->addDays(7),
            'metadata'                 => $opcoes['metadata'] ?? null,
            'criado_por'               => $opcoes['criado_por'] ?? 'sistema',
        ];

        // Cria a proposta no banco
        $proposta = PropostaPagamento::create($dadosProposta);

        Log::info("Proposta de pagamento criada", [
            'proposta_id' => $proposta->id,
            'uuid'        => $proposta->uuid,
            'tipo'        => $tipoProposta,
            'valor'       => $valorProposta,
        ]);

        return $proposta;
    }

    /**
     * Gera o link de pagamento baseado no tipo
     *
     * @param Contato $contato
     * @param float $valor
     * @param string $tipo
     * @param array $opcoes
     * @return string
     */
    private function gerarLinkPagamento(
        Contato $contato,
        float $valor,
        string $tipo,
        array $opcoes = []
    ): string {
        // Por enquanto, gera um link mockado/placeholder
        // TODO: Integrar com gateway de pagamento real (Mercado Pago, Gerencianet, etc)

        $uuid = $opcoes['uuid'] ?? \Illuminate\Support\Str::uuid();

        switch ($tipo) {
            case 'pix':
                return $this->gerarLinkPix($contato, $valor, $uuid, $opcoes);

            case 'boleto':
                return $this->gerarLinkBoleto($contato, $valor, $uuid, $opcoes);

            case 'cartao':
                return $this->gerarLinkCartao($contato, $valor, $uuid, $opcoes);

            case 'link_generico':
            default:
                return $this->gerarLinkGenerico($contato, $valor, $uuid, $opcoes);
        }
    }

    /**
     * Gera link de pagamento PIX
     * TODO: Integrar com gateway real
     */
    private function gerarLinkPix(Contato $contato, float $valor, string $uuid, array $opcoes): string
    {
        // Placeholder - substituir por integração real
        $params = http_build_query([
            'tipo'     => 'pix',
            'valor'    => $valor,
            'ref'      => $uuid,
            'nome'     => $contato->nome,
            'telefone' => $contato->telefone,
        ]);

        return $this->baseUrlPagamento . '/pix?' . $params;
    }

    /**
     * Gera link de pagamento Boleto
     * TODO: Integrar com gateway real
     */
    private function gerarLinkBoleto(Contato $contato, float $valor, string $uuid, array $opcoes): string
    {
        // Placeholder - substituir por integração real
        $params = http_build_query([
            'tipo'     => 'boleto',
            'valor'    => $valor,
            'ref'      => $uuid,
            'nome'     => $contato->nome,
            'telefone' => $contato->telefone,
        ]);

        return $this->baseUrlPagamento . '/boleto?' . $params;
    }

    /**
     * Gera link de pagamento com cartão
     * TODO: Integrar com gateway real
     */
    private function gerarLinkCartao(Contato $contato, float $valor, string $uuid, array $opcoes): string
    {
        // Placeholder - substituir por integração real
        $params = http_build_query([
            'tipo'     => 'cartao',
            'valor'    => $valor,
            'ref'      => $uuid,
            'nome'     => $contato->nome,
            'telefone' => $contato->telefone,
        ]);

        return $this->baseUrlPagamento . '/cartao?' . $params;
    }

    /**
     * Gera link genérico de pagamento
     */
    private function gerarLinkGenerico(Contato $contato, float $valor, string $uuid, array $opcoes): string
    {
        $params = http_build_query([
            'valor'    => $valor,
            'ref'      => $uuid,
            'nome'     => $contato->nome,
            'telefone' => $contato->telefone,
        ]);

        return $this->baseUrlPagamento . '?' . $params;
    }

    /**
     * Envia SMS com a proposta de pagamento
     *
     * @param PropostaPagamento $proposta
     * @param string|null $mensagemCustomizada
     * @return bool
     */
    public function enviarSmsProposta(PropostaPagamento $proposta, ?string $mensagemCustomizada = null): bool
    {
        try {
            // Monta a mensagem
            $mensagem = $mensagemCustomizada ?? $this->montarMensagemPadrao($proposta);

            // Salva a mensagem na proposta
            $proposta->mensagem_sms = $mensagem;
            $proposta->save();

            // Verifica se o Twilio está configurado
            if (! $this->twilioClient || ! $this->twilioFromNumber) {
                throw new Exception("Twilio não configurado. Configure TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN e TWILIO_FROM_NUMBER");
            }

            // Formata o telefone
            $telefone = $this->formatarTelefone($proposta->telefone);

            Log::info("Enviando SMS via Twilio", [
                'proposta_id'     => $proposta->id,
                'telefone'        => $telefone,
                'mensagem_length' => strlen($mensagem),
            ]);

            // Envia o SMS via Twilio
            $message = $this->twilioClient->messages->create(
                $telefone,
                [
                    'from' => $this->twilioFromNumber,
                    'body' => $mensagem,
                ]
            );

            // Atualiza a proposta com o SID da mensagem
            $proposta->marcarSmsEnviado($message->sid);

            Log::info("SMS enviado com sucesso via Twilio", [
                'proposta_id' => $proposta->id,
                'message_sid' => $message->sid,
                'status'      => $message->status,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Erro ao enviar SMS via Twilio", [
                'proposta_id' => $proposta->id,
                'telefone'    => $proposta->telefone,
                'erro'        => $e->getMessage(),
            ]);

            // Marca como erro
            $proposta->marcarSmsErro($e->getMessage());

            return false;
        }
    }

    /**
     * Monta mensagem padrão do SMS
     */
    private function montarMensagemPadrao(PropostaPagamento $proposta): string
    {
        $nomeCliente    = $proposta->nome_cliente ? explode(' ', $proposta->nome_cliente)[0] : 'Cliente';
        $valorFormatado = number_format($proposta->valor_proposta, 2, ',', '.');

        $mensagem = "Olá {$nomeCliente}! ";

        if ($proposta->desconto > 0) {
            $descontoFormatado  = number_format($proposta->desconto, 0);
            $mensagem          .= "Temos uma proposta especial com {$descontoFormatado}% de desconto! ";
        }

        $mensagem .= "Valor: R$ {$valorFormatado}. ";
        $mensagem .= "Pague agora: {$proposta->link_pagamento}";

        return $mensagem;
    }

    /**
     * Formata o telefone para o padrão internacional
     */
    private function formatarTelefone(string $telefone): string
    {
        // Remove caracteres não numéricos
        $telefone = preg_replace('/[^0-9]/', '', $telefone);

        // Adiciona código do país se não tiver
        if (! str_starts_with($telefone, '+')) {
            // Assume Brasil (+55) se tiver 10 ou 11 dígitos
            if (strlen($telefone) == 10 || strlen($telefone) == 11) {
                $telefone = '+55' . $telefone;
            } else {
                $telefone = '+' . $telefone;
            }
        }

        return $telefone;
    }

    /**
     * Atualiza o status do SMS baseado no webhook do Twilio
     */
    public function atualizarStatusSms(string $messageSid, string $status, ?string $errorMessage = null): bool
    {
        try {
            $proposta = PropostaPagamento::where('twilio_message_sid', $messageSid)->first();

            if (! $proposta) {
                Log::warning("Proposta não encontrada para MessageSid", ['message_sid' => $messageSid]);
                return false;
            }

            switch ($status) {
                case 'delivered':
                    $proposta->marcarSmsEntregue();
                    break;

                case 'failed':
                case 'undelivered':
                    $proposta->marcarSmsErro($errorMessage ?? "Falha na entrega: {$status}");
                    break;

                case 'sent':
                    $proposta->sms_status = 'enviado';
                    $proposta->save();
                    break;
            }

            Log::info("Status do SMS atualizado", [
                'proposta_id' => $proposta->id,
                'message_sid' => $messageSid,
                'status'      => $status,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Erro ao atualizar status do SMS", [
                'message_sid' => $messageSid,
                'erro'        => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Confirma o pagamento de uma proposta
     */
    public function confirmarPagamento(
        PropostaPagamento $proposta,
        float $valorPago,
        ?string $transactionId = null,
        ?array $dadosAdicionais = null
    ): bool {
        try {
            $proposta->marcarPago($valorPago, $transactionId);

            if ($dadosAdicionais) {
                $metadata              = $proposta->metadata ?? [];
                $metadata['pagamento'] = $dadosAdicionais;
                $proposta->metadata    = $metadata;
                $proposta->save();
            }

            // Atualiza o status do contato
            $contato = $proposta->contato;
            if ($contato) {
                $contato->status    = 'pago';
                $contato->resultado = 'sucesso';
                $contato->save();
            }

            Log::info("Pagamento confirmado", [
                'proposta_id'    => $proposta->id,
                'valor_pago'     => $valorPago,
                'transaction_id' => $transactionId,
            ]);

            return true;

        } catch (Exception $e) {
            Log::error("Erro ao confirmar pagamento", [
                'proposta_id' => $proposta->id,
                'erro'        => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Processa propostas expiradas
     */
    public function processarPropostasExpiradas(): int
    {
        $count = 0;

        try {
            $propostas = PropostaPagamento::expiradas()->get();

            foreach ($propostas as $proposta) {
                if ($proposta->verificarExpiracao()) {
                    $count++;
                }
            }

            Log::info("Propostas expiradas processadas", ['total' => $count]);

        } catch (Exception $e) {
            Log::error("Erro ao processar propostas expiradas", ['erro' => $e->getMessage()]);
        }

        return $count;
    }

    /**
     * Estatísticas de propostas
     */
    public function obterEstatisticas(?int $mailingId = null, ?array $periodo = null): array
    {
        $query = PropostaPagamento::query();

        if ($mailingId) {
            $query->where('mailing_id', $mailingId);
        }

        if ($periodo && isset($periodo['inicio']) && isset($periodo['fim'])) {
            $query->porPeriodo($periodo['inicio'], $periodo['fim']);
        }

        $total      = $query->count();
        $geradas    = (clone $query)->porStatus('gerada')->count();
        $enviadas   = (clone $query)->porStatus('sms_enviado')->count();
        $aguardando = (clone $query)->porStatus('aguardando_pagamento')->count();
        $pagas      = (clone $query)->porStatus('pago')->count();
        $expiradas  = (clone $query)->porStatus('expirado')->count();
        $erros      = (clone $query)->porStatus('sms_erro')->count();

        $valorTotal = (clone $query)->sum('valor_proposta');
        $valorPago  = (clone $query)->porStatus('pago')->sum('valor_pago');

        $taxaConversao = $total > 0 ? ($pagas / $total) * 100 : 0;

        return [
            'total_propostas' => $total,
            'por_status'      => [
                'geradas'              => $geradas,
                'sms_enviado'          => $enviadas,
                'aguardando_pagamento' => $aguardando,
                'pagas'                => $pagas,
                'expiradas'            => $expiradas,
                'erros'                => $erros,
            ],
            'valores'         => [
                'total_proposto' => round($valorTotal, 2),
                'total_pago'     => round($valorPago, 2),
                'media_proposta' => $total > 0 ? round($valorTotal / $total, 2) : 0,
            ],
            'taxas'           => [
                'conversao'   => round($taxaConversao, 2),
                'entrega_sms' => $total > 0 ? round((($enviadas + $aguardando + $pagas) / $total) * 100, 2) : 0,
            ],
        ];
    }
}
