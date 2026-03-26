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


        // Verifica se o mailing ainda está ativo
        $mailing = $queueJob->mailing;
        if ($mailing->isPausado() || $mailing->status === 'cancelado') {
            $queueJob->proxima_tentativa = \Carbon\Carbon::now()->addMinutes(5);
            $queueJob->save();
            return;
        }

        $contato = $queueJob->contato;
        $script  = $mailing->script;


        try {

            // Marca como processando
            $queueJob->marcarComoProcessando(gethostname());

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


            // Preparar variáveis dinâmicas para o agente (novo formato)
            $nomeParts    = explode(' ', $contato->nome);
            $primeiroNome = $nomeParts[0] ?? $contato->nome;
            $sobrenome    = $contato->sobrenome ?? (isset($nomeParts[1]) ? $nomeParts[1] : '');

            // Obter CPF em texto plano para enviar ao Retell
            $cpfPlano = '';
            $cpfValidacao = $contato->cpf_primeiros_digitos ?? '000';

            if (!empty($contato->cpf)) {
                $cpfLimpo = preg_replace('/[^0-9]/', '', $contato->cpf);
                if (strlen($cpfLimpo) === 11) {
                    // CPF já está em texto plano
                    $cpfPlano = $cpfLimpo;
                    $cpfValidacao = substr($cpfLimpo, 0, 3);
                } else {
                    // CPF encriptado - tentar decriptar
                    try {
                        $cpfDescriptografado = decrypt($contato->cpf);
                        $cpfPlano = preg_replace('/[^0-9]/', '', $cpfDescriptografado);
                        $cpfValidacao = substr($cpfPlano, 0, 3);
                    } catch (\Exception $e) {
                        Log::warning("[JOB-CPF-DECRYPT] Erro ao descriptografar CPF: {$e->getMessage()}, usando primeiros dígitos");
                        $cpfPlano = $contato->cpf_primeiros_digitos ?? '';
                    }
                }
            }

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
                'customer_id'             => (string) $contato->id,
                'nome_cliente'            => trim($primeiroNome),
                'sobrenome'               => trim($sobrenome),
                'credora'                 => $nomeCredora,
                'cpf'                     => $cpfPlano,
                'documento'               => $cpfValidacao,
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


            $start = microtime(true);

            // Configurações da API (por empresa, com fallback para env global)
            $creds      = IntegracaoService::getCredentials($mailing->empresa_id);
            $apiKey     = $creds['retell_api_key'];
            $agentId    = $creds['retell_agent_id'];
            $fromNumber = $creds['from_number'];


            // Construir request no novo formato da API
            // Não envia override_agent_version para sempre usar a versão mais recente publicada
            $requestBody = [
                'from_number'                  => $fromNumber,
                'to_number'                    => $contato->telefone,
                'override_agent_id'            => $agentId,
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


            // Extrair call_id do resultado
            $callId = $retellBody['call_id'] ?? null;
            if (! $callId) {
                throw new \Exception("Retell API não retornou call_id: " . json_encode($retellBody));
            }


            // Atualiza contato
            $contato->status = 'em_ligacao';
            $contato->save();

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

            // Atualiza estatísticas do mailing
            $mailing->atualizarEstatisticas();

            DB::commit();

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

        // Determina max tentativas (usa do mailing ou do script)
        $maxTentativas  = $mailing->max_tentativas ?? $script->tentativas_max ?? 3;

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

        }

        $queueJob->save();

        // Atualiza estatísticas do mailing
        $mailing->atualizarEstatisticas();
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

}
