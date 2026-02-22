<?php
namespace App\Jobs;

use App\Http\Controllers\URADevedorController;
use App\Models\Contato;
use App\Models\Empresa;
use App\Models\EmpresaConfiguracao;
use App\Models\Ligacao;
use App\Models\Mailing;
use App\Models\QueueJob;
use App\Models\Script;
use App\Services\IntegracaoService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessarContatoJob extends Job
{
    protected $queueJobId;

    /**
     * Create a new job instance.
     *
     * @param  int  $queueJobId
     * @return void
     */
    public function __construct($queueJobId)
    {
        $this->queueJobId = $queueJobId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $queueJob = QueueJob::withoutGlobalScopes()->find($this->queueJobId);

        if (! $queueJob) {
            Log::error("❌ [JOB-NOTFOUND] QueueJob {$this->queueJobId} não encontrado no banco de dados");
            return;
        }

        // Bindar empresa_id no container para contexto CLI
        if ($queueJob->empresa_id) {
            app()->instance('empresa_id', $queueJob->empresa_id);
        }

        Log::info("✅ [JOB-FOUND] QueueJob encontrado: {$this->queueJobId} | Tentativa: {$queueJob->tentativas}");

        // Verifica se o mailing ainda está ativo
        $mailing = $queueJob->mailing;
        if ($mailing->isPausado() || $mailing->status === 'cancelado') {
            Log::warning("⏸️  [JOB-MAILING-PAUSED] Mailing {$mailing->id} está {$mailing->status}, job {$this->queueJobId} será adiado 5 minutos");
            $queueJob->proxima_tentativa = \Carbon\Carbon::now()->addMinutes(5);
            $queueJob->save();
            return;
        }

        $contato = $queueJob->contato;
        $script  = $mailing->script;

        Log::info("📋 [JOB-CONTEXT] Mailing: {$mailing->nome} | Contato: {$contato->nome} | Tel: {$contato->telefone} | Valor: R$ {$contato->valor_debito}");

        try {

            // Marca como processando
            $queueJob->marcarComoProcessando(gethostname());
            Log::info("⚙️  [JOB-PROCESSING] Marcado como processando no host: " . gethostname());

            Log::info("Dados do contato {$contato->toJson()}");
            Log::info("🔄 [JOB-CALLING] Iniciando ligação para contato {$contato->id} - {$contato->nome} ({$contato->telefone})");
            // Verifica se pode fazer ligação neste horário
            // TEMPORÁRIO: Desabilitado para testes
            // if (!$script->podeDiscarNaHora()) {
            //     Log::info("⏰ Fora do horário permitido para o script {$script->id}, adiando job");
            //     $queueJob->status = 'pending';
            //     $queueJob->proxima_tentativa = \Carbon\Carbon::now()->addHour();
            //     $queueJob->save();
            //     return;
            // }

            DB::beginTransaction();

            Log::info("🔐 [JOB-SECURITY] Dados do contato {$contato}");

            // Preparar variáveis dinâmicas para o agente (novo formato)
            $nomeParts    = explode(' ', $contato->nome);
            $primeiroNome = $nomeParts[0] ?? $contato->nome;
            $sobrenome    = $contato->sobrenome ?? (isset($nomeParts[1]) ? $nomeParts[1] : '');

            // Usa CPF encriptado ou primeiros dígitos como fallback
            $cpfValidacao = $contato->cpf_primeiros_digitos ?? '000';
            // $cpfDescriptografado = decrypt($contato->cpf);
            // Log::info("🔐 [JOB-CPF-DECRYPT] CPF descriptografado com sucesso para contato {$cpfDescriptografado}");
            // try {
            //     if (! empty($contato->cpf)) {
            //         $cpfValidacao = substr($cpfDescriptografado, 0, 3);
            //     }
            // } catch (\Exception $e) {
            //     Log::warning("⚠️  [JOB-CPF-DECRYPT] Erro ao descriptografar CPF: {$e->getMessage()}, usando primeiros dígitos");
            //     $cpfValidacao = $contato->cpf_primeiros_digitos ?? '000';
            // }

            // Buscar configurações da empresa
            $empresa = Empresa::withoutGlobalScopes()->find($mailing->empresa_id);
            $configuracao = EmpresaConfiguracao::getForEmpresa($mailing->empresa_id);

            $nomeCredora      = $configuracao->nome_credora ?? ($empresa ? $empresa->nome : 'Dvelopers Credito');
            $limiteDescAlto   = $configuracao->limite_valor_desconto_alto;
            $descontoAlto     = $configuracao->percentual_desconto_alto / 100;
            $descontoBaixo    = $configuracao->percentual_desconto_baixo / 100;
            $desconto         = $contato->valor_debito > $limiteDescAlto ? $descontoAlto : $descontoBaixo;
            $maxParcelas      = $configuracao->max_parcelas;
            $valorMinParcela  = $configuracao->valor_minimo_parcela;
            $valorComDesconto = $contato->valor_debito * (1 - $desconto);
            $numParcelas      = max(1, min($maxParcelas, floor($contato->valor_debito / max($valorMinParcela, 1))));

            $converter        = new URADevedorController();
            $dynamicVariables = [
                'nome_cliente'            => trim($primeiroNome),
                'sobrenome'               => trim($sobrenome),
                'credora'                 => $nomeCredora,
                'cpf'                     => $contato->cpf,
                'valida_doc'              => $cpfValidacao,
                'valor_devido'            => $converter->valorPorExtenso($contato->valor_debito),
                'data_vencimento'         => $contato->vencimento ? $contato->vencimento->format('d/m/Y') : '01/01/2025',
                'percentual_desconto'     => round($desconto * 100) . '%',
                'valor_com_desconto'      => $converter->valorPorExtenso($valorComDesconto),
                'max_parcelas'            => (string) $numParcelas,
                'valor_parcela'           => $converter->valorPorExtenso($valorComDesconto / $numParcelas),
                'campanha'                => $contato->campanha ?? $mailing->nome,
                'historico_inadimplencia' => $contato->historico_inadimplencia ?? 'primeira_vez',
                'tentativas_contato'      => (string) ($contato->tentativas_contato ?? 1),
            ];

            Log::info("📊 [DYNAMIC-VARIABLES] Variáveis dinâmicas preparadas: " . json_encode($dynamicVariables));

            $start = microtime(true);
            Log::info("📞 [JOB-RETELL-START] Chamando Retell API v2/create-phone-call");

            // Configurações da API (por empresa, com fallback para env global)
            $creds      = IntegracaoService::getCredentials($mailing->empresa_id);
            $apiKey     = $creds['retell_api_key'];
            $agentId    = $creds['retell_agent_id'];
            $fromNumber = $creds['from_number'];

            Log::info('🔐 [RETELL-CONFIG] API Key: ' . (empty($apiKey) ? 'VAZIA!' : substr($apiKey, 0, 10) . '...'));
            Log::info('🤖 [RETELL-CONFIG] Agent ID: ' . (empty($agentId) ? 'VAZIO!' : $agentId));
            Log::info('📞 [RETELL-CONFIG] From Number: ' . (empty($fromNumber) ? 'VAZIO!' : $fromNumber));

            // Busca a versão do agente dinamicamente
            $agentVersion = $this->fetchAgentVersion($apiKey, $agentId);
            Log::info("🔄 [RETELL-AGENT-VERSION] Versão do agente obtida: {$agentVersion}");

            // Construir request no novo formato da API
            $requestBody = [
                'from_number'                  => $fromNumber,
                'to_number'                    => $contato->telefone,
                'override_agent_id'            => $agentId,
                'override_agent_version'       => $agentVersion,
                'retell_llm_dynamic_variables' => $dynamicVariables,
                'metadata'                     => [
                    'contato_id'    => $contato->id,
                    'customer_name' => $contato->nome,
                    'company'       => $contato->empresa_credora,
                    'debt_amount'   => $converter->valorPorExtenso($contato->valor_debito),
                    'tipo_chamada'  => 'cobranca',
                    'mailing_id'    => $mailing->id,
                    'queue_job_id'  => $queueJob->id,
                    'empresa_id'    => $queueJob->empresa_id,
                ],
            ];

            Log::info("📤 [RETELL-REQUEST] Body: " . json_encode($requestBody));

            // Log curl equivalente
            $curlCommand = $this->generateCurlCommand(
                'https://api.retellai.com/v2/create-phone-call',
                $requestBody,
                ['Authorization' => 'Bearer ' . $apiKey, 'Content-Type' => 'application/json']
            );
            Log::info("🔗 [RETELL-CURL] " . $curlCommand);

            $client         = new Client();
            $retellResponse = $client->post('https://api.retellai.com/v2/create-phone-call', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json'    => $requestBody,
            ]);

            $duration   = microtime(true) - $start;
            $retellBody = json_decode($retellResponse->getBody()->getContents(), true);

            Log::info("✅ [RETELL-RESPONSE] Status: {$retellResponse->getStatusCode()} | Duração: " . round($duration * 1000) . "ms");
            Log::info("✅ [RETELL-BODY] Response: " . json_encode($retellBody));

            // Extrair call_id do resultado
            $callId = $retellBody['call_id'] ?? null;
            if (! $callId) {
                throw new \Exception("Retell API não retornou call_id: " . json_encode($retellBody));
            }

            Log::info("📱 [JOB-CALL-ID] Call ID extraído: {$callId}");

            // Atualiza contato
            $contato->status = 'em_ligacao';
            $contato->save();
            Log::info("✅ [JOB-CONTACT-UPDATED] Contato {$contato->id} atualizado para status 'em_ligacao'");

            // Cria registro de ligação
            $ligacao = Ligacao::create([
                'contato_id'     => $contato->id,
                'empresa_id'     => $queueJob->empresa_id,
                'call_id_retell' => $callId,
                'status'         => 'iniciada',
                'detalhes'       => [
                    'agente_id'           => $retellBody['agent_id'] ?? null,
                    'iniciado_em'         => Carbon::now()->toIso8601String(),
                    'tempo_requisicao_ms' => (int) ($duration * 1000),
                    'retell_response'     => $retellBody,
                ],
            ]);
            Log::info("💾 [JOB-LIGACAO-CREATED] Ligação {$ligacao->id} criada com call_id: {$callId}");

            // Marca job como aguardando callback da Retell
            $queueJob->status    = 'awaiting_callback';
            $queueJob->resultado = [
                'call_id'             => $callId,
                'ligacao_id'          => $ligacao->id,
                'iniciado_em'         => Carbon::now()->toIso8601String(),
                'tempo_requisicao_ms' => (int) ($duration * 1000),
                'retell_response'     => $retellBody,
            ];
            $queueJob->tempo_processamento = (int) ($duration * 1000);
            $queueJob->ultima_tentativa    = Carbon::now();
            $queueJob->save();
            Log::info("⏳ [JOB-AWAITING-CALLBACK] QueueJob {$this->queueJobId} em aguardo de callback com call_id: {$callId}");

            // Atualiza estatísticas do mailing
            $mailing->atualizarEstatisticas();
            Log::info("📊 [JOB-STATS-UPDATED] Estatísticas do mailing {$mailing->id} atualizadas");

            DB::commit();
            Log::info("✅ [JOB-SUCCESS] Ligação iniciada com sucesso! Call ID: {$callId}");

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            DB::rollBack();
            $errorBody = $e->hasResponse() ? json_decode($e->getResponse()->getBody()->getContents(), true) : null;
            Log::error("❌ [RETELL-API-ERROR] " . json_encode($errorBody ?? ['message' => $e->getMessage()]));
            $this->handleFailure($queueJob, new \Exception("Retell API Error: " . json_encode($errorBody ?? $e->getMessage())));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ [JOB-EXCEPTION] Exceção capturada: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            $this->handleFailure($queueJob, $e);
        }
    }

    /**
     * Handle job failure.
     *
     * @param  QueueJob  $queueJob
     * @param  \Exception  $exception
     * @return void
     */
    protected function handleFailure($queueJob, $exception)
    {
        Log::error("❌ [JOB-FAILURE] Job {$this->queueJobId} falhou: " . $exception->getMessage());

        $contato = $queueJob->contato;
        $mailing = $queueJob->mailing;
        $script  = $mailing->script;

        // Incrementa tentativas
        $queueJob->tentativas    += 1;
        $queueJob->erro_mensagem = substr($exception->getMessage(), 0, 500);
        Log::info("⚠️  [JOB-ATTEMPT] Tentativa #{$queueJob->tentativas} | Erro: {$queueJob->erro_mensagem}");

        // Determina max tentativas (usa do mailing ou do script)
        $maxTentativas  = $mailing->max_tentativas ?? $script->tentativas_max ?? 3;
        Log::info("📊 [JOB-MAX-ATTEMPTS] Max tentativas configuradas: {$maxTentativas}");

        if ($queueJob->tentativas >= $maxTentativas) {
            // Falhou permanentemente
            $queueJob->status   = 'failed';
            $contato->status    = 'falhou';
            $contato->resultado = 'max_tentativas_atingido';
            $contato->save();
            Log::error("❌ [JOB-PERMANENT-FAIL] Job {$this->queueJobId} falhou permanentemente após {$queueJob->tentativas} tentativas");
            Log::error("❌ [JOB-CONTACT-FAILED] Contato {$contato->id} ({$contato->nome}) marcado como FALHOU");

        } else {
            // Agenda retry com backoff exponencial
            $intervalo = $mailing->intervalo_retry ?? $script->intervalo_entre_tentativas ?? 3600;
            $delay     = $intervalo * pow(2, $queueJob->tentativas - 1); // exponencial

            $queueJob->proxima_tentativa = Carbon::now()->addSeconds($delay);
            $queueJob->status            = 'pending';

            Log::warning("🔄 [JOB-RETRY-SCHEDULED] Job {$this->queueJobId} será retentado em " . round($delay / 60) . " minutos (tentativa {$queueJob->tentativas}/{$maxTentativas})");
            Log::debug("⏱️  [JOB-BACKOFF] Intervalo base: {$intervalo}s | Multiplicador: 2^" . ($queueJob->tentativas - 1) . " | Delay final: {$delay}s");
        }

        $queueJob->save();
        Log::info("💾 [JOB-STATE-SAVED] QueueJob {$this->queueJobId} estado salvo no banco de dados");

        // Atualiza estatísticas do mailing
        $mailing->atualizarEstatisticas();
        Log::info("📊 [JOB-MAILING-STATS] Estatísticas do mailing {$mailing->id} atualizadas");
    }

    /**
     * The job failed to process.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        Log::critical("Job {$this->queueJobId} falhou criticamente: " . $exception->getMessage());

        $queueJob = QueueJob::withoutGlobalScopes()->find($this->queueJobId);
        if ($queueJob) {
            $queueJob->status        = 'failed';
            $queueJob->erro_mensagem = 'Falha crítica: ' . $exception->getMessage();
            $queueJob->save();
        }
    }

    /**
     * Busca a versão atual do agente na Retell API.
     *
     * @param  string  $apiKey
     * @param  string  $agentId
     * @return int
     */
    protected function fetchAgentVersion($apiKey, $agentId)
    {
        try {
            $client   = new Client();
            $response = $client->get("https://api.retellai.com/v2/get-agent/{$agentId}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
            ]);

            $agentData = json_decode($response->getBody()->getContents(), true);
            $version   = $agentData['version'] ?? 0;

            Log::info("✅ [AGENT-VERSION-FETCHED] Versão obtida da API Retell: {$version}");
            return $version;

        } catch (\Exception $e) {
            Log::warning("⚠️  [AGENT-VERSION-FALLBACK] Erro ao buscar versão do agente: " . $e->getMessage() . " | Usando fallback: 2");
            return 2; // Fallback para versão 2
        }
    }

    /**
     * Gera um comando curl equivalente à requisição.
     *
     * @param  string  $url
     * @param  array   $body
     * @param  array   $headers
     * @return string
     */
    private function generateCurlCommand($url, $body, $headers)
    {
        $curl = "curl -X POST '{$url}'";

        foreach ($headers as $key => $value) {
            $curl .= " \\\n  -H '{$key}: {$value}'";
        }

        $bodyJson = json_encode($body);
        $curl     .= " \\\n  -d '" . str_replace("'", "'\\''", $bodyJson) . "'";

        return $curl;
    }
}
