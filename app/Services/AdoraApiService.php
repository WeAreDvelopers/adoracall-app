<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AdoraApiService
{
    protected Client $client;
    protected string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? env('ADORA_API_URL', 'http://localhost:8000'), '/');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
            'headers'  => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Passo 1: Solicitar propostas para um CPF.
     * POST /v3/proposal/request
     *
     * @return array {name, id (proposalId), message, sessionId}
     */
    public function requestProposals(string $cpf): array
    {
        Log::info("[ADORA-API] requestProposals CPF: {$cpf}");

        $response = $this->client->post('/v3/proposal/request', [
            'headers' => ['identifier' => $cpf],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        Log::info("[ADORA-API] requestProposals response: " . json_encode($data));

        return $data;
    }

    /**
     * Passo 2: Verificar status da consulta (poll até completed=true).
     * GET /v3/proposal/{proposalId}/status
     *
     * @return array {name, id, completed, message}
     */
    public function getProposalStatus(int $proposalId): array
    {
        $response = $this->client->get("/v3/proposal/{$proposalId}/status");
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Passo 3: Obter detalhes completos da proposta com débitos e opções.
     * GET /v3/proposal/{proposalId}
     *
     * @return array {id, name, firstName, document, debits[{id, paymentOptions[...], totalValue, ...}], hash}
     */
    public function getProposalDetails(int $proposalId, ?string $cpf = null): array
    {
        $headers = [];
        if ($cpf) {
            $headers['identifier'] = $cpf;
        }

        $response = $this->client->get("/v3/proposal/{$proposalId}", [
            'headers' => $headers,
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        Log::info("[ADORA-API] getProposalDetails: " . json_encode($data));

        return $data;
    }

    /**
     * Passo 4: Confirmar proposta com opção de pagamento escolhida.
     * POST /v3/proposal/{proposalId}/confirm
     *
     * @return array {message, paymentCode, processing, dealId}
     */
    public function confirmProposal(int $proposalId, int $debitId, int $paymentOptionId): array
    {
        Log::info("[ADORA-API] confirmProposal: proposal={$proposalId} debit={$debitId} option={$paymentOptionId}");

        $response = $this->client->post("/v3/proposal/{$proposalId}/confirm", [
            'json' => [
                'debitId'         => $debitId,
                'paymentOptionId' => $paymentOptionId,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        Log::info("[ADORA-API] confirmProposal response: " . json_encode($data));

        return $data;
    }

    /**
     * Obter detalhes do acordo (boleto, pix, etc).
     * GET /v3/deals/{dealId}
     *
     * @return array {message, processing, debit{id, selectedPaymentOption, status, boleto{paymentLine, qrCodePix, url}}}
     */
    public function getDealDetails(int $dealId, ?string $cpf = null): array
    {
        $headers = [];
        if ($cpf) {
            $headers['identifier'] = $cpf;
        }

        $response = $this->client->get("/v3/deals/{$dealId}", [
            'headers' => $headers,
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        Log::info("[ADORA-API] getDealDetails: " . json_encode($data));

        return $data;
    }

    /**
     * Listar acordos do cliente.
     * GET /v3/deals
     *
     * @return array {message, processing, debit{...}}
     */
    public function listDeals(?string $cpf = null): array
    {
        $headers = [];
        if ($cpf) {
            $headers['identifier'] = $cpf;
        }

        $response = $this->client->get('/v3/deals', [
            'headers' => $headers,
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Solicita propostas e aguarda até estar pronto (poll).
     * Combina passos 1, 2 e 3 em uma chamada só.
     *
     * @return array Detalhes completos da proposta
     */
    public function consultarEAguardar(string $cpf, int $maxRetries = 10, int $sleepMs = 500): array
    {
        // Passo 1: Solicitar
        $request = $this->requestProposals($cpf);
        $proposalId = $request['id'];

        // Passo 2: Poll até completed
        for ($i = 0; $i < $maxRetries; $i++) {
            $status = $this->getProposalStatus($proposalId);

            if (!empty($status['completed'])) {
                break;
            }

            usleep($sleepMs * 1000);
        }

        // Passo 3: Obter detalhes
        return $this->getProposalDetails($proposalId, $cpf);
    }
}
