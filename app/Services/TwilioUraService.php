<?php

namespace App\Services;

use App\Models\Acordo;
use App\Models\Contato;
use App\Models\Ligacao;
use App\Models\PropostaPagamento;
use App\Models\UraCall;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class TwilioUraService
{
    protected NumberToWordsService $numberToWords;
    protected AdoraApiService $adoraApi;

    public function __construct()
    {
        $this->numberToWords = new NumberToWordsService();
        $this->adoraApi = new AdoraApiService();
    }

    /**
     * Inicia uma chamada Twilio outbound para o contato.
     * Cria registros UraCall e Ligacao para compatibilidade com o dashboard.
     */
    public function initiateCall(Contato $contato, int $empresaId, ?int $mailingId = null, ?int $queueJobId = null): array
    {
        $creds = IntegracaoService::getTwilioCredentials($empresaId);

        if (empty($creds['twilio_account_sid']) || empty($creds['twilio_auth_token'])) {
            throw new \Exception('Credenciais Twilio não configuradas para empresa ' . $empresaId);
        }

        if (empty($creds['twilio_from_number'])) {
            throw new \Exception('Número Twilio (from) não configurado para empresa ' . $empresaId);
        }

        $baseUrl = rtrim(env('APP_URL', 'http://localhost:8000'), '/');

        // Criar registro UraCall ANTES da chamada (webhook precisa encontrar)
        $uraCall = UraCall::create([
            'empresa_id'   => $empresaId,
            'contato_id'   => $contato->id,
            'mailing_id'   => $mailingId,
            'queue_job_id' => $queueJobId,
            'step'         => UraCall::STEP_START,
        ]);

        Log::info("[IVR-INITIATE] UraCall #{$uraCall->id} criada para contato {$contato->id}");

        try {
            $client = new TwilioClient($creds['twilio_account_sid'], $creds['twilio_auth_token']);

            $call = $client->calls->create(
                $contato->telefone,
                $creds['twilio_from_number'],
                [
                    'url'                  => $baseUrl . '/api/ura/ivr/welcome',
                    'statusCallback'       => $baseUrl . '/api/ura/ivr/status',
                    'statusCallbackEvent'  => ['initiated', 'ringing', 'answered', 'completed'],
                    'statusCallbackMethod' => 'POST',
                    'method'               => 'POST',
                ]
            );

            // Atualizar call_sid no registro
            $uraCall->call_sid = $call->sid;
            $uraCall->save();

            // Criar registro Ligacao para compatibilidade com dashboard
            $ligacao = Ligacao::create([
                'contato_id'     => $contato->id,
                'empresa_id'     => $empresaId,
                'sid_twilio'     => $call->sid,
                'status'         => 'iniciada',
                'detalhes'       => [
                    'tipo'        => 'ivr_programatica',
                    'iniciado_em' => Carbon::now()->toIso8601String(),
                    'ura_call_id' => $uraCall->id,
                ],
            ]);

            // Atualizar contato
            $contato->status = 'em_ligacao';
            $contato->save();

            Log::info("[IVR-CALL-CREATED] CallSid: {$call->sid} | Ligacao: {$ligacao->id} | Contato: {$contato->id}");

            return [
                'call_sid'    => $call->sid,
                'ura_call_id' => $uraCall->id,
                'ligacao_id'  => $ligacao->id,
            ];

        } catch (\Exception $e) {
            $uraCall->step          = UraCall::STEP_FINALIZADO;
            $uraCall->result        = 'erro_twilio';
            $uraCall->error_message = substr($e->getMessage(), 0, 500);
            $uraCall->save();

            throw $e;
        }
    }

    /**
     * Consulta dívida via API Adora (request → poll → get details).
     * Retorna proposalId, nome, débitos e opções de pagamento da API externa.
     */
    public function consultarDividaApi(string $cpf): ?array
    {
        try {
            $proposal = $this->adoraApi->consultarEAguardar($cpf);

            if (empty($proposal['debits'])) {
                return null;
            }

            // Pegar primeiro débito com status OPEN
            $debit = null;
            foreach ($proposal['debits'] as $d) {
                if (($d['status'] ?? '') === 'OPEN') {
                    $debit = $d;
                    break;
                }
            }

            if (!$debit) {
                $debit = $proposal['debits'][0];
            }

            // Montar opções de pagamento a partir da API
            $opcoes = [];
            $numero = 1;
            foreach ($debit['paymentOptions'] ?? [] as $option) {
                $valorTotal = $option['totalValue'] ?? 0;
                $parcelas   = $option['installmentNumber'] ?? 0;
                $modelo     = $option['model'] ?? 'ONE_PAYMENT';

                if ($modelo === 'ONE_PAYMENT' || $parcelas == 0) {
                    // À vista
                    $desconto = $debit['totalValue'] > 0
                        ? round((1 - $valorTotal / $debit['totalValue']) * 100)
                        : 0;

                    $descricaoVoz = "Opção {$this->numeroPorExtenso($numero)}: pagamento à vista de {$this->numberToWords->valorPorExtenso($valorTotal)}";
                    if ($desconto > 0) {
                        $descricaoVoz .= ", com {$desconto} por cento de desconto";
                    }
                    $descricaoVoz .= '.';

                    $opcoes[] = [
                        'numero'            => $numero,
                        'tipo'              => 'avista',
                        'parcelas'          => 1,
                        'valor_total'       => $valorTotal,
                        'valor_parcela'     => $valorTotal,
                        'desconto'          => $desconto,
                        'descricao_voz'     => $descricaoVoz,
                        'payment_option_id' => $option['id'],
                        'identifier'        => $option['identifier'] ?? '',
                    ];
                } else {
                    // Parcelado
                    $valorParcela = $parcelas > 0 ? round($valorTotal / $parcelas, 2) : $valorTotal;

                    $descricaoVoz = "Opção {$this->numeroPorExtenso($numero)}: {$this->numberToWords->converterParcelasPorExtenso($parcelas)} de {$this->numberToWords->valorPorExtenso($valorParcela)}, totalizando {$this->numberToWords->valorPorExtenso($valorTotal)}.";

                    $opcoes[] = [
                        'numero'            => $numero,
                        'tipo'              => 'parcelado',
                        'parcelas'          => $parcelas,
                        'valor_total'       => $valorTotal,
                        'valor_parcela'     => $valorParcela,
                        'desconto'          => 0,
                        'descricao_voz'     => $descricaoVoz,
                        'payment_option_id' => $option['id'],
                        'identifier'        => $option['identifier'] ?? '',
                    ];
                }
                $numero++;
            }

            return [
                'proposal_id' => $proposal['id'],
                'nome'         => $proposal['firstName'] ?? $proposal['name'] ?? 'cliente',
                'nome_completo' => $proposal['name'] ?? '',
                'document'     => $proposal['document'] ?? $cpf,
                'debit_id'     => $debit['id'],
                'total_debito' => $debit['totalValue'] ?? 0,
                'total_extenso' => $this->numberToWords->valorPorExtenso($debit['totalValue'] ?? 0),
                'opcoes'       => $opcoes,
                'hash'         => $proposal['hash'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error("[IVR-ADORA-API-ERROR] Erro ao consultar API Adora: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Confirma acordo na API Adora e registra localmente.
     */
    public function firmarAcordoApi(UraCall $uraCall, array $opcaoEscolhida, array $dadosApi): array
    {
        $contato = Contato::withoutGlobalScopes()->find($uraCall->contato_id);

        if (!$contato) {
            throw new \Exception("Contato {$uraCall->contato_id} não encontrado");
        }

        // Confirmar na API Adora
        $confirmResult = $this->adoraApi->confirmProposal(
            $dadosApi['proposal_id'],
            $dadosApi['debit_id'],
            $opcaoEscolhida['payment_option_id']
        );

        Log::info("[IVR-ADORA-CONFIRM] Resultado: " . json_encode($confirmResult));

        $dealId = $confirmResult['dealId'] ?? null;

        // Buscar detalhes do deal (boleto, pix)
        $dealDetails = null;
        if ($dealId) {
            try {
                $dealDetails = $this->adoraApi->getDealDetails($dealId, $dadosApi['document'] ?? null);
            } catch (\Exception $e) {
                Log::warning("[IVR-ADORA-DEAL] Erro ao buscar deal: {$e->getMessage()}");
            }
        }

        // Buscar ligação associada
        $ligacao = Ligacao::withoutGlobalScopes()
            ->where('sid_twilio', $uraCall->call_sid)
            ->first();

        DB::beginTransaction();

        try {
            // Criar proposta de pagamento local (para dashboard)
            $proposta = PropostaPagamento::create([
                'contato_id'              => $contato->id,
                'mailing_id'              => $uraCall->mailing_id,
                'empresa_id'              => $uraCall->empresa_id,
                'telefone'                => $contato->telefone,
                'nome_cliente'            => $contato->nome,
                'valor_original'          => $dadosApi['total_debito'],
                'valor_original_numerico' => $dadosApi['total_debito'],
                'valor_proposta'          => $opcaoEscolhida['valor_total'],
                'valor_proposta_numerico' => $opcaoEscolhida['valor_total'],
                'desconto'                => $opcaoEscolhida['desconto'] ?? 0,
                'tipo_proposta'           => $opcaoEscolhida['tipo'] === 'avista' ? 'a_vista' : 'parcelado',
                'status'                  => 'acordado_ivr',
                'criado_por'              => 'ivr_programatica',
                'metadata'                => [
                    'ura_call_id'       => $uraCall->id,
                    'call_sid'          => $uraCall->call_sid,
                    'adora_proposal_id' => $dadosApi['proposal_id'],
                    'adora_debit_id'    => $dadosApi['debit_id'],
                    'adora_deal_id'     => $dealId,
                    'payment_option_id' => $opcaoEscolhida['payment_option_id'],
                    'deal_details'      => $dealDetails,
                ],
            ]);

            // Criar acordo local
            $acordo = Acordo::create([
                'contato_id'             => $contato->id,
                'empresa_id'             => $uraCall->empresa_id,
                'ligacao_id'             => $ligacao ? $ligacao->id : null,
                'proposta_id'            => $proposta->id,
                'aceito_em'              => Carbon::now(),
                'confirmacao_texto'      => 'Acordo firmado via URA IVR + API Adora',
                'valor_acordado'         => $opcaoEscolhida['valor_total'],
                'parcelas_acordadas'     => $opcaoEscolhida['parcelas'],
                'valor_parcela_acordada' => $opcaoEscolhida['valor_parcela'],
                'status'                 => 'acordado_voz_ivr',
            ]);

            // Atualizar contato
            $contato->status    = 'acordo_firmado';
            $contato->resultado = 'acordo_firmado_ivr';
            $contato->save();

            // Atualizar UraCall
            $uraCall->step            = UraCall::STEP_FINALIZADO;
            $uraCall->result          = 'acordo_firmado';
            $uraCall->selected_option = $opcaoEscolhida;
            $uraCall->save();

            DB::commit();

            Log::info("[IVR-ACORDO] Acordo #{$acordo->id} firmado via API Adora | DealId: {$dealId} | Contato: {$contato->id}");

            // Extrair info do boleto se disponível
            $boleto = $dealDetails['debit']['boleto'] ?? null;

            return [
                'acordo_id'   => $acordo->id,
                'proposta_id' => $proposta->id,
                'deal_id'     => $dealId,
                'valor_total' => $opcaoEscolhida['valor_total'],
                'parcelas'    => $opcaoEscolhida['parcelas'],
                'boleto'      => $boleto,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Converte número ordinal simples para texto.
     */
    private function numeroPorExtenso(int $numero): string
    {
        $mapa = [1 => 'um', 2 => 'dois', 3 => 'três'];
        return $mapa[$numero] ?? (string) $numero;
    }
}
