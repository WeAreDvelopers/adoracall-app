<?php

namespace App\Http\Controllers;

use App\Models\Contato;
use App\Models\Empresa;
use App\Models\Ligacao;
use App\Models\QueueJob;
use App\Models\UraCall;
use App\Services\Ai\AiMessageService;
use App\Services\IntegracaoService;
use App\Services\NumberToWordsService;
use App\Services\TwilioUraService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;

class UraIvrController extends Controller
{
    protected TwilioUraService $uraService;
    protected AiMessageService $aiService;
    protected NumberToWordsService $numberToWords;

    /** Opções de voz resolvidas dinamicamente por empresa (cache por request). */
    private array $voiceOpts = ['language' => 'pt-BR', 'voice' => 'Google.pt-BR-Neural2-A'];
    private ?string $speechRate = null;
    private bool $voiceInitialized = false;

    public function __construct()
    {
        $this->uraService = new TwilioUraService();
        $this->aiService = new AiMessageService();
        $this->numberToWords = new NumberToWordsService();
    }

    /**
     * Carrega voz e velocidade configuradas pela empresa.
     * Idempotente — executa apenas uma vez por request.
     */
    private function initVoice(int $empresaId): void
    {
        if ($this->voiceInitialized) {
            return;
        }

        $creds = IntegracaoService::getTwilioCredentials($empresaId);
        $voice = $creds['twilio_voice'] ?? 'Google.pt-BR-Neural2-A';

        $this->voiceOpts = ['language' => 'pt-BR', 'voice' => $voice];
        $this->speechRate = $creds['twilio_speech_rate'] ?: null;
        $this->voiceInitialized = true;
    }

    /**
     * Envolve o texto em SSML <prosody> se uma velocidade foi configurada.
     */
    private function txt(string $text): string
    {
        if (empty($this->speechRate) || $this->speechRate === 'medium') {
            return $text;
        }

        $rate = htmlspecialchars($this->speechRate, ENT_XML1);
        return "<speak><prosody rate=\"{$rate}\">{$text}</prosody></speak>";
    }

    // =========================================================================
    //  STEP 1 — WELCOME: "Posso falar com {nome}?" (speech)
    // =========================================================================

    public function welcome(Request $request)
    {
        $callSid = $request->input('CallSid');
        Log::info("[IVR-WELCOME] CallSid: {$callSid}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            Log::error("[IVR-WELCOME] UraCall não encontrada para CallSid: {$callSid}");
            $response->say($this->txt('Desculpe, ocorreu um erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $contato = Contato::withoutGlobalScopes()->find($uraCall->contato_id);
        $primeiroNome = $contato ? explode(' ', trim($contato->nome))[0] : 'cliente';

        // Gather por voz: "Posso falar com {nome}?"
        $gather = $response->gather([
            'input'         => 'speech',
            'language'      => 'pt-BR',
            'speechTimeout' => 'auto',
            'action'        => '/api/ura/ivr/confirm-identity',
            'method'        => 'POST',
            'hints'         => 'sim, não, sou, pode, isso, sou eu, ele mesmo, ela mesma',
        ]);

        $gather->say(
            $this->txt("Olá, posso falar com {$primeiroNome}, por gentileza?"),
            $this->voiceOpts
        );

        // Timeout — sem resposta, tentar de novo uma vez
        $response->say($this->txt('Não consegui ouvir sua resposta.'), $this->voiceOpts);
        $response->redirect('/api/ura/ivr/welcome', ['method' => 'POST']);

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 2 — CONFIRM IDENTITY: Processa fala → apresenta + pede CPF (dtmf+speech)
    // =========================================================================

    public function confirmIdentity(Request $request)
    {
        $callSid      = $request->input('CallSid');
        $speechResult = $request->input('SpeechResult');
        $confidence   = $request->input('Confidence', 0);
        Log::info("[IVR-CONFIRM-IDENTITY] CallSid: {$callSid} | Speech: '{$speechResult}' | Confidence: {$confidence}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        // Se claramente disse "não" → encerrar educadamente
        if ($this->isSpeechNegative($speechResult)) {
            Log::info("[IVR-CONFIRM-IDENTITY] Pessoa negou identidade | UraCall #{$uraCall->id}");

            $uraCall->step   = UraCall::STEP_FINALIZADO;
            $uraCall->result = 'pessoa_errada';
            $uraCall->save();

            $response->say(
                $this->txt('Peço desculpas pelo incômodo. Tenha um ótimo dia. Até logo!'),
                $this->voiceOpts
            );
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Positivo ou ambíguo → prosseguir (apresentação + pedir CPF)
        $contato = Contato::withoutGlobalScopes()->find($uraCall->contato_id);
        $primeiroNome = $contato ? explode(' ', trim($contato->nome))[0] : 'cliente';
        $empresa = Empresa::withoutGlobalScopes()->with('configuracao')->find($uraCall->empresa_id);
        $nomeCredora = $empresa->nome_credora ?? $empresa->nome;
        $nomeAtendente = $empresa->configuracao->nome_atendente ?? 'Angélica';
        $artigoPrep = ($empresa->configuracao->artigo_empresa ?? 'a') === 'o' ? 'do' : 'da';

        // Apresentação
        $defaultIntro = "Certo! Me chamo {$nomeAtendente}, sou consultora digital {$artigoPrep} {$nomeCredora}. "
            . "Por questão de segurança, preciso confirmar seus dados.";

        $introMsg = $this->aiService->generateMessage(
            $empresa,
            UraCall::STEP_START,
            [
                'nome_cliente'    => $primeiroNome,
                'nome_credora'    => $nomeCredora,
                'nome_atendente'  => $nomeAtendente,
            ],
            $defaultIntro
        );

        $response->say($this->txt($introMsg), $this->voiceOpts);

        // Pedir CPF (DTMF + Speech)
        $gather = $response->gather([
            'input'         => 'dtmf speech',
            'numDigits'     => 3,
            'language'      => 'pt-BR',
            'speechTimeout' => 'auto',
            'action'        => '/api/ura/ivr/verify-cpf',
            'method'        => 'POST',
            'timeout'       => 10,
            'hints'         => 'zero, um, dois, três, quatro, cinco, seis, sete, oito, nove, meia',
        ]);

        $gather->say(
            $this->txt('Me confirma os três primeiros números do seu C P F? '
            . 'Você pode digitar ou falar os números.'),
            $this->voiceOpts
        );

        // Timeout — repetir pedido de CPF
        $response->say(
            $this->txt('Não recebi os números.'),
            $this->voiceOpts
        );
        $response->redirect('/api/ura/ivr/confirm-identity-retry', ['method' => 'POST']);

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 2b — RETRY CPF REQUEST (sem repetir apresentação)
    // =========================================================================

    public function confirmIdentityRetry(Request $request)
    {
        $callSid = $request->input('CallSid');
        Log::info("[IVR-CONFIRM-IDENTITY-RETRY] CallSid: {$callSid}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $gather = $response->gather([
            'input'         => 'dtmf speech',
            'numDigits'     => 3,
            'language'      => 'pt-BR',
            'speechTimeout' => 'auto',
            'action'        => '/api/ura/ivr/verify-cpf',
            'method'        => 'POST',
            'timeout'       => 10,
            'hints'         => 'zero, um, dois, três, quatro, cinco, seis, sete, oito, nove, meia',
        ]);

        $gather->say(
            $this->txt('A confirmação serve para proteger suas informações. '
            . 'Por favor, informe os três primeiros números do seu C P F. Você pode digitar ou falar.'),
            $this->voiceOpts
        );

        // Segundo timeout — encerrar
        $response->say($this->txt('Não recebi os números. Vou encerrar a ligação. Até logo.'), $this->voiceOpts);
        $response->hangup();

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 3 — VERIFY CPF: Valida 3 primeiros dígitos (DTMF ou Speech, até 3 tentativas)
    // =========================================================================

    public function verifyCpf(Request $request)
    {
        $callSid      = $request->input('CallSid');
        $digits       = $request->input('Digits');
        $speechResult = $request->input('SpeechResult');
        Log::info("[IVR-VERIFY-CPF] CallSid: {$callSid} | Digits: {$digits} | Speech: '{$speechResult}'");

        // Se veio por speech, extrair dígitos da fala
        if (empty($digits) && !empty($speechResult)) {
            $digits = $this->extractDigitsFromSpeech($speechResult);
            Log::info("[IVR-VERIFY-CPF] Dígitos extraídos da fala: {$digits}");
        }

        // Garantir que digits é string (nunca null na comparação)
        $digits = $digits ? (string) $digits : '';

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $contato = Contato::withoutGlobalScopes()->find($uraCall->contato_id);

        if (!$contato) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Obter os 3 primeiros dígitos esperados
        $esperado = $contato->cpf_primeiros_digitos;
        if (empty($esperado) && !empty($contato->cpf)) {
            $esperado = substr(preg_replace('/\D/', '', $contato->cpf), 0, 3);
        }
        $esperado = (string) ($esperado ?? '');

        Log::info("[IVR-VERIFY-CPF] Comparando | digits='{$digits}' esperado='{$esperado}'");

        // Controle de tentativas
        $dadosSalvos = $uraCall->selected_option ?? [];
        $tentativas  = ($dadosSalvos['cpf_tentativas'] ?? 0) + 1;
        $dadosSalvos['cpf_tentativas'] = $tentativas;
        $uraCall->selected_option = $dadosSalvos;

        // Comparar dígitos (ambos já são strings)
        if ($digits !== '' && $digits === $esperado) {
            $uraCall->step = UraCall::STEP_IDENTIDADE_CONFIRMADA;
            $uraCall->save();

            Log::info("[IVR-VERIFY-CPF] CPF confirmado | UraCall #{$uraCall->id} | Tentativa {$tentativas}");

            $response->redirect('/api/ura/ivr/debt-info', ['method' => 'POST']);
            return $this->twimlResponse($response);
        }

        // Dígitos incorretos ou não extraídos
        Log::warning("[IVR-VERIFY-CPF] CPF incorreto | UraCall #{$uraCall->id} | Tentativa {$tentativas}/3 | Recebido={$digits} Esperado={$esperado}");

        if ($tentativas >= 3) {
            $uraCall->step   = UraCall::STEP_FINALIZADO;
            $uraCall->result = 'cpf_nao_confirmado';
            $uraCall->save();

            $empresa = Empresa::withoutGlobalScopes()->with('configuracao')->find($uraCall->empresa_id);
            $telefoneContato = $empresa->configuracao->telefone_contato ?? null;

            $msgFinal = 'Infelizmente não consegui validar seu C P F e não posso passar mais informações.';
            if ($telefoneContato) {
                $msgFinal .= " Peço que retorne em nosso número: {$telefoneContato}, repetindo, {$telefoneContato}.";
            }
            $msgFinal .= ' Até logo.';

            $response->say($this->txt($msgFinal), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Ainda tem tentativas
        $uraCall->save();

        $gather = $response->gather([
            'input'         => 'dtmf speech',
            'numDigits'     => 3,
            'language'      => 'pt-BR',
            'speechTimeout' => 'auto',
            'action'        => '/api/ura/ivr/verify-cpf',
            'method'        => 'POST',
            'timeout'       => 10,
            'hints'         => 'zero, um, dois, três, quatro, cinco, seis, sete, oito, nove, meia',
        ]);

        $gather->say(
            $this->txt('Os números informados não conferem. Por favor, tente novamente. '
            . 'Informe os três primeiros números do seu C P F.'),
            $this->voiceOpts
        );

        $response->say($this->txt('Não recebi os números. Vou encerrar a ligação. Até logo.'), $this->voiceOpts);
        $response->hangup();

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 4a — DEBT INFO: Mensagem de espera + redirect para buscar dívida
    //  (retorna imediatamente para o Twilio tocar a mensagem de espera)
    // =========================================================================

    public function debtInfo(Request $request)
    {
        $callSid = $request->input('CallSid');
        Log::info("[IVR-DEBT-INFO] CallSid: {$callSid}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        // Mensagem de espera com pausas ANTES do redirect para preencher o tempo
        $response->say(
            $this->txt('Só um instante enquanto valido seus dados, por favor.'),
            $this->voiceOpts
        );
        $response->pause(['length' => 2]);
        $response->say(
            $this->txt('Estou acessando o sistema.'),
            $this->voiceOpts
        );
        $response->pause(['length' => 3]);
        $response->say(
            $this->txt('Já estou finalizando.'),
            $this->voiceOpts
        );
        $response->pause(['length' => 2]);
        $response->redirect('/api/ura/ivr/fetch-debt', ['method' => 'POST']);

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 4b — FETCH DEBT: Busca dívida na API + apresenta oferta à vista
    //  (chamado após a mensagem de espera ser tocada)
    // =========================================================================

    public function fetchDebt(Request $request)
    {
        $callSid = $request->input('CallSid');
        Log::info("[IVR-FETCH-DEBT] CallSid: {$callSid}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $empresa = Empresa::withoutGlobalScopes()->with('configuracao')->find($uraCall->empresa_id);
        $contato = Contato::withoutGlobalScopes()->find($uraCall->contato_id);
        $nomeCredora = $empresa->nome_credora ?? $empresa->nome;

        if (!$contato || empty($contato->cpf)) {
            Log::error("[IVR-FETCH-DEBT] Contato sem CPF | UraCall #{$uraCall->id}");
            $response->say($this->txt('Desculpe, não foi possível localizar seus dados. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Consultar dívida via API (cliente ouviu música de espera enquanto isso carrega)
        $cpfLimpo = preg_replace('/\D/', '', $contato->cpf);
        $uraCall->cpf = $cpfLimpo;
        $uraCall->step = UraCall::STEP_CPF_RECEBIDO;
        $uraCall->save();

        $divida = $this->uraService->consultarDividaApi($cpfLimpo);

        if (!$divida || empty($divida['opcoes'])) {
            $mensagem = !$divida
                ? 'Não encontrei nenhuma pendência associada ao seu C P F. '
                : 'No momento não há opções de negociação disponíveis para o seu débito. ';

            $response->say(
                $this->txt($mensagem . 'Caso tenha dúvidas, entre em contato conosco pelos canais oficiais. Até logo.'),
                $this->voiceOpts
            );

            $uraCall->step   = UraCall::STEP_FINALIZADO;
            $uraCall->result = !$divida ? 'cpf_nao_encontrado' : 'sem_opcoes';
            $uraCall->save();

            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Separar opções: à vista e parcelado
        $opcaoVista = null;
        $opcaoParcelada = null;
        foreach ($divida['opcoes'] as $opcao) {
            if ($opcao['tipo'] === 'avista' && !$opcaoVista) {
                $opcaoVista = $opcao;
            } elseif ($opcao['tipo'] === 'parcelado' && !$opcaoParcelada) {
                $opcaoParcelada = $opcao;
            }
        }

        // Guardar dados da API
        $dadosSalvos = $uraCall->selected_option ?? [];
        $uraCall->step = UraCall::STEP_PROPOSTA_APRESENTADA;
        $uraCall->selected_option = array_merge($dadosSalvos, [
            'opcoes_disponiveis' => $divida['opcoes'],
            'opcao_vista'        => $opcaoVista,
            'opcao_parcelada'    => $opcaoParcelada,
            'api_data' => [
                'proposal_id'   => $divida['proposal_id'],
                'debit_id'      => $divida['debit_id'],
                'document'      => $divida['document'],
                'nome'          => $divida['nome'],
                'total_debito'  => $divida['total_debito'],
                'total_extenso' => $divida['total_extenso'],
            ],
        ]);
        $uraCall->save();

        $valorTotal = $divida['total_extenso'];
        $dataVista  = Carbon::now()->addDay()->format('d/m');

        if ($opcaoVista) {
            // Apresentar dívida + oferta à vista
            $valorVistaExtenso = $this->numberToWords->valorPorExtenso($opcaoVista['valor_total']);

            $defaultMsg = "Olha, estou entrando em contato referente a uma pendência em seu nome, "
                . "no valor atualizado de {$valorTotal}. "
                . "Hoje temos uma excelente condição para você quitar seu débito por apenas {$valorVistaExtenso}";

            if ($opcaoVista['desconto'] > 0) {
                $defaultMsg .= ", com {$opcaoVista['desconto']} por cento de desconto";
            }

            $defaultMsg .= ". Posso te encaminhar o boleto com vencimento em {$dataVista}?";

            $debtMsg = $this->aiService->generateMessage(
                $empresa,
                UraCall::STEP_PROPOSTA_APRESENTADA,
                [
                    'valor_total'  => $valorTotal,
                    'valor_vista'  => $valorVistaExtenso,
                    'desconto'     => $opcaoVista['desconto'] ?? 0,
                    'data_vista'   => $dataVista,
                    'nome_credora' => $nomeCredora,
                ],
                $defaultMsg
            );

            $gather = $response->gather([
                'input'         => 'speech',
                'language'      => 'pt-BR',
                'speechTimeout' => 'auto',
                'action'        => '/api/ura/ivr/cash-response',
                'method'        => 'POST',
                'hints'         => 'sim, não, pode, quero, aceito, não quero, não posso',
            ]);

            $gather->say($this->txt($debtMsg), $this->voiceOpts);

            // Timeout → tratar como recusa, ir para extensão
            $response->say($this->txt('Não consegui ouvir sua resposta.'), $this->voiceOpts);
            $response->redirect('/api/ura/ivr/cash-response', ['method' => 'POST']);

        } elseif ($opcaoParcelada) {
            // Sem opção à vista — apresentar parcelamento direto
            $response->redirect('/api/ura/ivr/offer-installments', ['method' => 'POST']);

        } else {
            // Sem nenhuma opção válida
            $response->say(
                $this->txt('No momento não há opções de negociação disponíveis. Até logo.'),
                $this->voiceOpts
            );
            $uraCall->step   = UraCall::STEP_FINALIZADO;
            $uraCall->result = 'sem_opcoes';
            $uraCall->save();
            $response->hangup();
        }

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 5 — CASH RESPONSE: Aceita → acordo. Recusa → oferta D+5 (speech)
    // =========================================================================

    public function cashResponse(Request $request)
    {
        $callSid      = $request->input('CallSid');
        $speechResult = $request->input('SpeechResult');
        $confidence   = $request->input('Confidence', 0);
        Log::info("[IVR-CASH-RESPONSE] CallSid: {$callSid} | Speech: '{$speechResult}' | Confidence: {$confidence}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $dadosSalvos = $uraCall->selected_option ?? [];
        $opcaoVista  = $dadosSalvos['opcao_vista'] ?? null;
        $apiData     = $dadosSalvos['api_data'] ?? null;

        // Se disse SIM → firmar acordo à vista
        if ($this->isSpeechPositive($speechResult)) {
            return $this->firmarAcordo($response, $uraCall, $opcaoVista, $apiData, 'avista');
        }

        // Recusou ou sem resposta → oferecer extensão D+5
        $dataEstendida = Carbon::now()->addDays(5)->format('d/m');
        $valorVistaExtenso = $opcaoVista
            ? $this->numberToWords->valorPorExtenso($opcaoVista['valor_total'])
            : 'o valor';

        $gather = $response->gather([
            'input'         => 'speech',
            'language'      => 'pt-BR',
            'speechTimeout' => 'auto',
            'action'        => '/api/ura/ivr/extension-response',
            'method'        => 'POST',
            'hints'         => 'sim, não, pode, quero, aceito, tá bom, não posso, não quero',
        ]);

        $gather->say(
            $this->txt("Olha, eu consigo estender o pagamento deste valor de {$valorVistaExtenso} "
            . "para o dia {$dataEstendida}. Fica bom pra você?"),
            $this->voiceOpts
        );

        // Timeout → tratar como recusa, ir para parcelamento
        $response->redirect('/api/ura/ivr/extension-response', ['method' => 'POST']);

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 6 — EXTENSION RESPONSE: Aceita → acordo. Recusa → parcelamento (speech)
    // =========================================================================

    public function extensionResponse(Request $request)
    {
        $callSid      = $request->input('CallSid');
        $speechResult = $request->input('SpeechResult');
        $confidence   = $request->input('Confidence', 0);
        Log::info("[IVR-EXTENSION-RESPONSE] CallSid: {$callSid} | Speech: '{$speechResult}' | Confidence: {$confidence}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $dadosSalvos    = $uraCall->selected_option ?? [];
        $opcaoVista     = $dadosSalvos['opcao_vista'] ?? null;
        $opcaoParcelada = $dadosSalvos['opcao_parcelada'] ?? null;
        $apiData        = $dadosSalvos['api_data'] ?? null;

        // Se disse SIM → firmar acordo à vista (com data estendida D+5)
        if ($this->isSpeechPositive($speechResult)) {
            return $this->firmarAcordo($response, $uraCall, $opcaoVista, $apiData, 'avista_estendido');
        }

        // Recusou → oferecer parcelamento (se disponível)
        if (!$opcaoParcelada) {
            $uraCall->step   = UraCall::STEP_FINALIZADO;
            $uraCall->result = 'cliente_recusou';
            $uraCall->save();

            $response->say(
                $this->txt('Infelizmente essas são as condições disponíveis que tenho hoje. '
                . 'Eu ligo em outra oportunidade. Obrigada pela atenção! Até logo.'),
                $this->voiceOpts
            );
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Transição → verificar parcelamento
        $response->say(
            $this->txt('Certo, vou verificar uma opção de parcelamento pra você.'),
            $this->voiceOpts
        );
        $response->pause(['length' => 1]);

        // Apresentar parcelamento
        $valorParcelaExtenso = $this->numberToWords->valorPorExtenso($opcaoParcelada['valor_parcela']);
        $parcelasTexto       = $this->numberToWords->converterParcelasPorExtenso($opcaoParcelada['parcelas']);
        $dataParcelada       = Carbon::now()->addDays(5)->format('d/m');

        $gather = $response->gather([
            'input'         => 'speech',
            'language'      => 'pt-BR',
            'speechTimeout' => 'auto',
            'action'        => '/api/ura/ivr/installment-response',
            'method'        => 'POST',
            'hints'         => 'sim, não, pode, quero, aceito, tá bom, não posso, não quero',
        ]);

        $gather->say(
            $this->txt("Consigo fazer pra você {$parcelasTexto} de {$valorParcelaExtenso}, "
            . "com pagamento a partir do dia {$dataParcelada}. Posso te enviar o boleto?"),
            $this->voiceOpts
        );

        // Timeout → tratar como recusa
        $response->redirect('/api/ura/ivr/installment-response', ['method' => 'POST']);

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 6b — OFFER INSTALLMENTS: Quando não há opção à vista (direto parcelado)
    // =========================================================================

    public function offerInstallments(Request $request)
    {
        $callSid = $request->input('CallSid');
        Log::info("[IVR-OFFER-INSTALLMENTS] CallSid: {$callSid}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $dadosSalvos    = $uraCall->selected_option ?? [];
        $opcaoParcelada = $dadosSalvos['opcao_parcelada'] ?? null;

        if (!$opcaoParcelada) {
            $response->say($this->txt('Não há opções disponíveis no momento. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $valorTotal      = $dadosSalvos['api_data']['total_extenso'] ?? '';
        $valorParcelaExt = $this->numberToWords->valorPorExtenso($opcaoParcelada['valor_parcela']);
        $parcelasTexto   = $this->numberToWords->converterParcelasPorExtenso($opcaoParcelada['parcelas']);
        $dataParcelada   = Carbon::now()->addDays(5)->format('d/m');

        $gather = $response->gather([
            'input'         => 'speech',
            'language'      => 'pt-BR',
            'speechTimeout' => 'auto',
            'action'        => '/api/ura/ivr/installment-response',
            'method'        => 'POST',
            'hints'         => 'sim, não, pode, quero, aceito, tá bom, não posso, não quero',
        ]);

        $gather->say(
            $this->txt("Olha, estou entrando em contato referente a uma pendência em seu nome, "
            . "no valor de {$valorTotal}. "
            . "Consigo fazer pra você {$parcelasTexto} de {$valorParcelaExt}, "
            . "com pagamento a partir do dia {$dataParcelada}. Posso te enviar o boleto?"),
            $this->voiceOpts
        );

        // Timeout
        $response->redirect('/api/ura/ivr/installment-response', ['method' => 'POST']);

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 7 — INSTALLMENT RESPONSE: Aceita → acordo. Recusa → encerrar
    // =========================================================================

    public function installmentResponse(Request $request)
    {
        $callSid      = $request->input('CallSid');
        $speechResult = $request->input('SpeechResult');
        $confidence   = $request->input('Confidence', 0);
        Log::info("[IVR-INSTALLMENT-RESPONSE] CallSid: {$callSid} | Speech: '{$speechResult}' | Confidence: {$confidence}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $dadosSalvos    = $uraCall->selected_option ?? [];
        $opcaoParcelada = $dadosSalvos['opcao_parcelada'] ?? null;
        $apiData        = $dadosSalvos['api_data'] ?? null;

        // Se disse SIM → firmar acordo parcelado
        if ($this->isSpeechPositive($speechResult)) {
            return $this->firmarAcordo($response, $uraCall, $opcaoParcelada, $apiData, 'parcelado');
        }

        // Recusou ou sem resposta → encerrar educadamente
        $uraCall->step   = UraCall::STEP_FINALIZADO;
        $uraCall->result = 'cliente_recusou';
        $uraCall->save();

        $response->say(
            $this->txt('Infelizmente essas são as condições disponíveis que tenho hoje. '
            . 'Eu ligo em outra oportunidade. Obrigada pela atenção! Até logo.'),
            $this->voiceOpts
        );
        $response->hangup();

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  FIRMAR ACORDO — Fase 1: salva escolha + retorna mensagem de espera
    //  (retorna imediatamente para o Twilio tocar "Aguarde...")
    // =========================================================================

    private function firmarAcordo(VoiceResponse $response, UraCall $uraCall, ?array $opcaoEscolhida, ?array $apiData, string $tipo)
    {
        if (!$opcaoEscolhida || !$apiData) {
            $response->say($this->txt('Erro ao processar sua escolha. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Salvar escolha e tipo no UraCall para o próximo step usar
        $dadosSalvos = $uraCall->selected_option ?? [];
        $uraCall->step = UraCall::STEP_CONFIRMANDO;
        $uraCall->selected_option = array_merge($dadosSalvos, [
            'opcao_selecionada' => $opcaoEscolhida,
            'tipo_acordo'       => $tipo,
        ]);
        $uraCall->save();

        // Retorna IMEDIATAMENTE — Twilio toca "Aguarde..." + espera ANTES da API call
        $response->say($this->txt('Aguarde enquanto processamos seu acordo.'), $this->voiceOpts);
        $response->pause(['length' => 2]);
        $response->say($this->txt('Estou registrando sua negociação no sistema.'), $this->voiceOpts);
        $response->pause(['length' => 3]);
        $response->say($this->txt('Só mais um instante.'), $this->voiceOpts);
        $response->pause(['length' => 2]);
        $response->redirect('/api/ura/ivr/process-deal', ['method' => 'POST']);

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  FIRMAR ACORDO — Fase 2: processa na API + retorna confirmação
    //  (chamado após a mensagem de espera ser tocada)
    // =========================================================================

    public function processDeal(Request $request)
    {
        $callSid = $request->input('CallSid');
        Log::info("[IVR-PROCESS-DEAL] CallSid: {$callSid}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $dadosSalvos    = $uraCall->selected_option ?? [];
        $opcaoEscolhida = $dadosSalvos['opcao_selecionada'] ?? null;
        $apiData        = $dadosSalvos['api_data'] ?? null;
        $tipo           = $dadosSalvos['tipo_acordo'] ?? 'avista';

        if (!$opcaoEscolhida || !$apiData) {
            $response->say($this->txt('Erro ao processar sua escolha. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $empresa = Empresa::withoutGlobalScopes()->with('configuracao')->find($uraCall->empresa_id);
        $nomeCredora = $empresa->nome_credora ?? $empresa->nome;
        $telefoneContato = $empresa->configuracao->telefone_contato ?? null;
        $artigo = ($empresa->configuracao->artigo_empresa ?? 'a') === 'o' ? 'O' : 'A';

        try {
            $resultado = $this->uraService->firmarAcordoApi($uraCall, $opcaoEscolhida, $apiData);

            if ($uraCall->queue_job_id) {
                $queueJob = QueueJob::withoutGlobalScopes()->find($uraCall->queue_job_id);
                if ($queueJob) {
                    $queueJob->marcarComoCompletado([
                        'acordo_id'   => $resultado['acordo_id'],
                        'proposta_id' => $resultado['proposta_id'],
                        'deal_id'     => $resultado['deal_id'],
                        'tipo'        => 'acordo_ivr_adora',
                        'call_sid'    => $uraCall->call_sid,
                    ]);
                }
            }

            // Data de vencimento baseada no tipo
            $diasVencimento = ($tipo === 'avista') ? 1 : 5;
            $dataVencimento = Carbon::now()->addDays($diasVencimento)->format('d/m/Y');

            // Mensagem de confirmação
            if ($opcaoEscolhida['parcelas'] == 1 || $tipo === 'avista' || $tipo === 'avista_estendido') {
                $valorExtenso = $this->numberToWords->valorPorExtenso($opcaoEscolhida['valor_total']);

                $successMsg = "Prontinho! Então ficou assim: o acordo foi registrado à vista, "
                    . "no valor de {$valorExtenso}, com vencimento para o dia {$dataVencimento}. "
                    . "Em alguns instantes você receberá o código de barras via S M S para realizar seu pagamento. "
                    . "É importante realizar o pagamento até a data combinada para garantir esse desconto.";
            } else {
                $parcelasTexto = $this->numberToWords->converterParcelasPorExtenso($opcaoEscolhida['parcelas']);
                $valorParcelaExtenso = $this->numberToWords->valorPorExtenso($opcaoEscolhida['valor_parcela']);

                $successMsg = "Prontinho! Acordo registrado em {$parcelasTexto} "
                    . "de {$valorParcelaExtenso}. "
                    . "Com vencimento da primeira parcela para o dia {$dataVencimento}. "
                    . "Em alguns instantes você receberá o código de barras via S M S para realizar seu pagamento. "
                    . "É importante realizar o pagamento até a data combinada para garantir esse desconto.";
            }

            if ($telefoneContato) {
                $successMsg .= " Em caso de dúvidas, ligue no {$telefoneContato}, repetindo, {$telefoneContato}.";
            }

            $successMsg .= " {$artigo} {$nomeCredora} agradece sua atenção! Até logo.";

            $response->say($this->txt($successMsg), $this->voiceOpts);

            Log::info("[IVR-ACORDO-OK] UraCall #{$uraCall->id} | Tipo: {$tipo} | DealId: {$resultado['deal_id']}");

        } catch (\Exception $e) {
            Log::error("[IVR-ACORDO-ERRO] Erro: {$e->getMessage()}");

            $uraCall->step          = UraCall::STEP_FINALIZADO;
            $uraCall->result        = 'erro_acordo';
            $uraCall->error_message = substr($e->getMessage(), 0, 500);
            $uraCall->save();

            $response->say(
                $this->txt('Desculpe, ocorreu um erro ao processar seu acordo. '
                . 'Por favor, entre em contato conosco pelos canais oficiais. Até logo.'),
                $this->voiceOpts
            );
        }

        $response->hangup();
        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STATUS CALLBACK
    // =========================================================================

    public function status(Request $request)
    {
        $callSid       = $request->input('CallSid');
        $callStatus    = $request->input('CallStatus');
        $callDuration  = $request->input('CallDuration');
        Log::info("[IVR-STATUS] CallSid: {$callSid} | Status: {$callStatus} | Duration: {$callDuration}");

        $uraCall = UraCall::findByCallSid($callSid);

        if (!$uraCall) {
            Log::warning("[IVR-STATUS] UraCall não encontrada para CallSid: {$callSid}");
            return response('OK', 200);
        }

        $uraCall->status_twilio = $callStatus;
        if ($callDuration) {
            $uraCall->duracao = (int) $callDuration;
        }

        $ligacao = Ligacao::withoutGlobalScopes()
            ->where('sid_twilio', $callSid)
            ->first();

        if ($ligacao) {
            $ligacao->status  = $this->mapTwilioStatus($callStatus);
            $ligacao->duracao = $callDuration ? (int) $callDuration : $ligacao->duracao;
            $ligacao->save();
        }

        if (in_array($callStatus, ['completed', 'busy', 'no-answer', 'failed', 'canceled'])) {
            if ($uraCall->result !== 'acordo_firmado') {
                if ($uraCall->step !== UraCall::STEP_FINALIZADO) {
                    $uraCall->step = UraCall::STEP_FINALIZADO;
                    $uraCall->result = $uraCall->result ?: 'sem_acordo_' . $callStatus;
                }

                if ($uraCall->queue_job_id) {
                    $this->handleRetry($uraCall, $callStatus);
                }
            }

            if ($uraCall->mailing_id) {
                $mailing = \App\Models\Mailing::withoutGlobalScopes()->find($uraCall->mailing_id);
                if ($mailing) {
                    $mailing->atualizarEstatisticas();
                }
            }
        }

        $uraCall->save();

        return response('OK', 200);
    }

    // =========================================================================
    //  HELPERS
    // =========================================================================

    private function twimlResponse(VoiceResponse $response)
    {
        return response($response->asXML(), 200)
            ->header('Content-Type', 'text/xml');
    }

    private function mapTwilioStatus(string $twilioStatus): string
    {
        return [
            'queued'      => 'iniciada',
            'initiated'   => 'iniciada',
            'ringing'     => 'chamando',
            'in-progress' => 'em_andamento',
            'completed'   => 'finalizada',
            'busy'        => 'ocupado',
            'no-answer'   => 'nao_atendida',
            'failed'      => 'falha',
            'canceled'    => 'cancelada',
        ][$twilioStatus] ?? $twilioStatus;
    }

    private function handleRetry(UraCall $uraCall, string $callStatus): void
    {
        $queueJob = QueueJob::withoutGlobalScopes()->find($uraCall->queue_job_id);

        if (!$queueJob || $queueJob->isCompletado()) {
            return;
        }

        if (in_array($callStatus, ['busy', 'no-answer', 'failed'])) {
            $queueJob->marcarComoFalhou("IVR: chamada {$callStatus}");
            Log::info("[IVR-RETRY] QueueJob {$queueJob->id} marcado para retry | status: {$callStatus}");
        } elseif ($callStatus === 'completed' && $uraCall->result !== 'acordo_firmado') {
            $queueJob->marcarComoCompletado([
                'tipo'     => 'ivr_sem_acordo',
                'result'   => $uraCall->result,
                'call_sid' => $uraCall->call_sid,
                'duracao'  => $uraCall->duracao,
            ]);
        }
    }

    /**
     * Extrai dígitos numéricos de texto falado em português.
     * Ex: "um dois três" → "123", "1 2 3" → "123", "cinco meia nove" → "569"
     */
    private function extractDigitsFromSpeech(?string $speechResult): ?string
    {
        if (empty($speechResult)) {
            return null;
        }

        $text = mb_strtolower(trim($speechResult));

        // Mapeia palavras faladas para dígitos
        $numberWords = [
            'zero' => '0', 'um' => '1', 'uma' => '1', 'dois' => '2', 'duas' => '2',
            'três' => '3', 'tres' => '3', 'quatro' => '4', 'cinco' => '5',
            'seis' => '6', 'meia' => '6', 'sete' => '7', 'oito' => '8', 'nove' => '9',
        ];

        // Percorre palavra a palavra, aceitando tanto dígitos ("3") quanto palavras ("oito")
        $words = preg_split('/[\s,\.]+/', $text);
        $result = '';
        foreach ($words as $word) {
            $word = trim($word);
            if ($word === '') continue;

            // Se é um dígito numérico direto (0-9)
            if (preg_match('/^\d$/', $word)) {
                $result .= $word;
            }
            // Se é uma palavra mapeada (oito, três, meia...)
            elseif (isset($numberWords[$word])) {
                $result .= $numberWords[$word];
            }
        }

        return strlen($result) >= 3 ? substr($result, 0, 3) : null;
    }

    /**
     * Detecta se a fala do cliente é positiva (sim, sou eu, pode, etc).
     */
    private function isSpeechPositive(?string $speechResult): bool
    {
        if (empty($speechResult)) {
            return false;
        }

        $text = mb_strtolower(trim($speechResult));

        $patterns = [
            'sim', 'sou', 'sou eu', 'eu mesmo', 'eu mesma',
            'ele mesmo', 'ela mesma', 'pode', 'pode falar',
            'isso', 'isso mesmo', 'correto', 'exato', 'claro',
            'com certeza', 'tudo bem', 'ok', 'pois não',
            'é sim', 'sou sim', 'é ele', 'é ela',
            'quero', 'desejo', 'por favor', 'pode ser',
            'tá bom', 'tá', 'uhum', 'aham',
            'aceito', 'fechado', 'bora', 'vamos',
            'fica bom', 'tá ótimo', 'beleza', 'combinado',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta se a fala do cliente é negativa (não, engano, errado, etc).
     */
    private function isSpeechNegative(?string $speechResult): bool
    {
        if (empty($speechResult)) {
            return false;
        }

        $text = mb_strtolower(trim($speechResult));

        $patterns = [
            'não', 'nao', 'engano', 'errado', 'número errado',
            'não sou', 'não é', 'não quero', 'não desejo',
            'de jeito nenhum', 'negativo', 'nunca', 'jamais',
            'pessoa errada', 'ligação errada', 'não conheço',
            'não posso', 'não tenho', 'não consigo', 'impossível',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
