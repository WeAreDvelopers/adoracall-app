<?php

namespace App\Http\Controllers;

use App\Models\Contato;
use App\Models\Empresa;
use App\Models\Ligacao;
use App\Models\QueueJob;
use App\Models\UraCall;
use App\Services\Ai\AiMessageService;
use App\Services\IntegracaoService;
use App\Services\TwilioUraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;

class UraIvrController extends Controller
{
    protected TwilioUraService $uraService;
    protected AiMessageService $aiService;

    /** Opções de voz resolvidas dinamicamente por empresa (cache por request). */
    private array $voiceOpts = ['language' => 'pt-BR', 'voice' => 'Polly.Camila'];
    private ?string $speechRate = null;
    private bool $voiceInitialized = false;

    public function __construct()
    {
        $this->uraService = new TwilioUraService();
        $this->aiService = new AiMessageService();
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
        $voice = $creds['twilio_voice'] ?? 'Polly.Camila';

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
    //  STEP 2 — CONFIRM IDENTITY: Processa fala, se positivo → apresenta + CPF
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

        // Apresentação (fala antes do Gather — não captura DTMF durante isso)
        $defaultIntro = "Olá, tudo bem? Me chamo Angélica, sou consultora da {$nomeCredora}. "
            . "Estou entrando em contato para falar sobre uma negociação especial dos seus débitos.";

        $introMsg = $this->aiService->generateMessage(
            $empresa,
            UraCall::STEP_START,
            [
                'nome_cliente' => $primeiroNome,
                'nome_credora' => $nomeCredora,
            ],
            $defaultIntro
        );

        $response->say($this->txt($introMsg), $this->voiceOpts);

        // Pedir CPF (Gather DTMF — captura os 3 dígitos)
        $gather = $response->gather([
            'numDigits' => 3,
            'action'    => '/api/ura/ivr/verify-cpf',
            'method'    => 'POST',
            'timeout'   => 10,
        ]);

        $gather->say(
            $this->txt('Para eu conseguir verificar sua situação, preciso que você me confirme o seu C P F. '
            . 'Por favor, me informe os três primeiros dígitos do seu documento.'),
            $this->voiceOpts
        );

        // Timeout — repetir pedido de CPF
        $response->say(
            $this->txt('Não recebi os dígitos. Por favor, digite os três primeiros números do seu C P F.'),
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
            'numDigits' => 3,
            'action'    => '/api/ura/ivr/verify-cpf',
            'method'    => 'POST',
            'timeout'   => 10,
        ]);

        $gather->say(
            $this->txt('Por favor, digite os três primeiros números do seu C P F.'),
            $this->voiceOpts
        );

        // Segundo timeout — encerrar
        $response->say($this->txt('Não recebi os dígitos. Vou encerrar a ligação. Até logo.'), $this->voiceOpts);
        $response->hangup();

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 3 — VERIFY CPF: Valida 3 primeiros dígitos (até 3 tentativas)
    // =========================================================================

    public function verifyCpf(Request $request)
    {
        $callSid = $request->input('CallSid');
        $digits  = $request->input('Digits');
        Log::info("[IVR-VERIFY-CPF] CallSid: {$callSid} | Digits: {$digits}");

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

        // Controle de tentativas
        $dadosSalvos = $uraCall->selected_option ?? [];
        $tentativas  = ($dadosSalvos['cpf_tentativas'] ?? 0) + 1;
        $dadosSalvos['cpf_tentativas'] = $tentativas;
        $uraCall->selected_option = $dadosSalvos;

        // Comparar dígitos
        if ($digits === $esperado) {
            $uraCall->step = UraCall::STEP_IDENTIDADE_CONFIRMADA;
            $uraCall->save();

            Log::info("[IVR-VERIFY-CPF] CPF confirmado | UraCall #{$uraCall->id} | Tentativa {$tentativas}");

            $response->redirect('/api/ura/ivr/debt-info', ['method' => 'POST']);
            return $this->twimlResponse($response);
        }

        // Dígitos incorretos
        Log::warning("[IVR-VERIFY-CPF] CPF incorreto | UraCall #{$uraCall->id} | Tentativa {$tentativas}/3 | Recebido={$digits} Esperado={$esperado}");

        if ($tentativas >= 3) {
            $uraCall->step   = UraCall::STEP_FINALIZADO;
            $uraCall->result = 'cpf_nao_confirmado';
            $uraCall->save();

            $response->say(
                $this->txt('Não foi possível confirmar sua identidade. Agradecemos sua atenção. Até logo.'),
                $this->voiceOpts
            );
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Ainda tem tentativas
        $uraCall->save();

        $gather = $response->gather([
            'numDigits' => 3,
            'action'    => '/api/ura/ivr/verify-cpf',
            'method'    => 'POST',
            'timeout'   => 10,
        ]);

        $gather->say(
            $this->txt('Os números digitados não conferem. Por favor, tente novamente. '
            . 'Digite os três primeiros números do seu C P F.'),
            $this->voiceOpts
        );

        $response->say($this->txt('Não recebi os dígitos. Vou encerrar a ligação. Até logo.'), $this->voiceOpts);
        $response->hangup();

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 4 — DEBT INFO: "Obrigado! Motivo da dívida... está ciente?" (speech)
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

        $empresa = Empresa::withoutGlobalScopes()->find($uraCall->empresa_id);

        $uraCall->step = UraCall::STEP_DIVIDA_INFORMADA;
        $uraCall->save();

        // Agradecimento + informar motivo (speech para captar resposta)
        $gather = $response->gather([
            'input'         => 'speech',
            'language'      => 'pt-BR',
            'speechTimeout' => 'auto',
            'action'        => '/api/ura/ivr/ask-proposals',
            'method'        => 'POST',
            'hints'         => 'sim, não, sei, sabia, ciente, conheço, desconheço',
        ]);

        $defaultDebtInfo = "Muito obrigada por confirmar seus dados! "
            . "O motivo do meu contato é referente a uma dívida que consta em seu nome. "
            . "Você está ciente desta dívida?";

        $debtMsg = $this->aiService->generateMessage(
            $empresa,
            UraCall::STEP_DIVIDA_INFORMADA,
            [],
            $defaultDebtInfo
        );

        $gather->say($this->txt($debtMsg), $this->voiceOpts);

        // Timeout — prosseguir mesmo assim (assume que ouviu)
        $response->redirect('/api/ura/ivr/ask-proposals', ['method' => 'POST']);

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 5 — ASK PROPOSALS: "Deseja ouvir as propostas?" (speech)
    // =========================================================================

    public function askProposals(Request $request)
    {
        $callSid      = $request->input('CallSid');
        $speechResult = $request->input('SpeechResult');
        Log::info("[IVR-ASK-PROPOSALS] CallSid: {$callSid} | Speech: '{$speechResult}'");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        // Independente da resposta sobre ciência, perguntar sobre propostas
        $gather = $response->gather([
            'input'         => 'speech',
            'language'      => 'pt-BR',
            'speechTimeout' => 'auto',
            'action'        => '/api/ura/ivr/proposal-response',
            'method'        => 'POST',
            'hints'         => 'sim, não, quero, pode, claro, não quero',
        ]);

        $gather->say(
            $this->txt('Deseja ouvir as propostas de quitação que temos para você?'),
            $this->voiceOpts
        );

        // Timeout — encerrar educadamente
        $response->say(
            $this->txt('Tudo bem, sem problemas. Caso queira negociar futuramente, estamos à disposição. Até logo!'),
            $this->voiceOpts
        );
        $response->hangup();

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 6 — PROPOSAL RESPONSE: Processa fala → buscar ou encerrar
    // =========================================================================

    public function proposalResponse(Request $request)
    {
        $callSid      = $request->input('CallSid');
        $speechResult = $request->input('SpeechResult');
        $confidence   = $request->input('Confidence', 0);
        Log::info("[IVR-PROPOSAL-RESPONSE] CallSid: {$callSid} | Speech: '{$speechResult}' | Confidence: {$confidence}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        // Se claramente disse "não" → encerrar
        if ($this->isSpeechNegative($speechResult)) {
            $uraCall->step   = UraCall::STEP_FINALIZADO;
            $uraCall->result = 'cliente_recusou';
            $uraCall->save();

            $response->say(
                $this->txt('Tudo bem, sem problemas. Caso queira negociar futuramente, estamos à disposição. '
                . 'Tenha um ótimo dia. Até logo!'),
                $this->voiceOpts
            );
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Positivo ou ambíguo → buscar dívida
        $response->say(
            $this->txt('Perfeito! Vou buscar as informações da sua dívida. Um momento, por favor.'),
            $this->voiceOpts
        );

        $response->redirect('/api/ura/ivr/fetch-debt', ['method' => 'POST']);

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 7 — FETCH DEBT: Busca API + "Localizei!" + apresenta opções (DTMF)
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

        $empresa = Empresa::withoutGlobalScopes()->find($uraCall->empresa_id);
        $contato = Contato::withoutGlobalScopes()->find($uraCall->contato_id);

        if (!$contato || empty($contato->cpf)) {
            Log::error("[IVR-FETCH-DEBT] Contato sem CPF | UraCall #{$uraCall->id}");
            $response->say($this->txt('Desculpe, não foi possível localizar seus dados. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Usar CPF completo do contato (do mailing)
        $cpfLimpo = preg_replace('/\D/', '', $contato->cpf);
        $uraCall->cpf = $cpfLimpo;
        $uraCall->step = UraCall::STEP_CPF_RECEBIDO;
        $uraCall->save();

        // Consultar dívida via API Adora
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

        $opcoes = $divida['opcoes'];

        // Guardar dados da API
        $dadosSalvos = $uraCall->selected_option ?? [];
        $uraCall->step            = UraCall::STEP_PROPOSTA_APRESENTADA;
        $uraCall->selected_option = array_merge($dadosSalvos, [
            'opcoes_disponiveis' => $opcoes,
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

        // "Localizei!" + opções via Gather DTMF
        $gather = $response->gather([
            'numDigits' => 1,
            'action'    => '/api/ura/ivr/select',
            'method'    => 'POST',
            'timeout'   => 15,
        ]);

        $defaultOptions = "Pronto, localizei sua dívida! "
            . "Encontrei uma pendência no valor de {$divida['total_extenso']}. "
            . "Temos as seguintes opções de negociação: ";
        foreach ($opcoes as $opcao) {
            $defaultOptions .= $opcao['descricao_voz'] . ' ';
        }
        $defaultOptions .= "Para escolher, digite o número da opção desejada.";

        $optionsMsg = $this->aiService->generateMessage(
            $empresa,
            UraCall::STEP_PROPOSTA_APRESENTADA,
            [],
            $defaultOptions
        );

        $gather->say($this->txt($optionsMsg), $this->voiceOpts);

        // Timeout — repetir
        $response->say($this->txt('Não recebi sua escolha. Vou repetir as opções.'), $this->voiceOpts);
        $response->redirect('/api/ura/ivr/repeat-options', ['method' => 'POST']);

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 7b — REPEAT OPTIONS (sem nova chamada API)
    // =========================================================================

    public function repeatOptions(Request $request)
    {
        $callSid = $request->input('CallSid');
        Log::info("[IVR-REPEAT-OPTIONS] CallSid: {$callSid}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $dadosSalvos = $uraCall->selected_option ?? [];
        $opcoes      = $dadosSalvos['opcoes_disponiveis'] ?? [];

        if (empty($opcoes)) {
            $response->say($this->txt('Não há opções disponíveis. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $gather = $response->gather([
            'numDigits' => 1,
            'action'    => '/api/ura/ivr/select',
            'method'    => 'POST',
            'timeout'   => 15,
        ]);

        $textoOpcoes = 'As opções são: ';
        foreach ($opcoes as $opcao) {
            $textoOpcoes .= $opcao['descricao_voz'] . ' ';
        }
        $textoOpcoes .= 'Digite o número da opção desejada.';

        $gather->say($this->txt($textoOpcoes), $this->voiceOpts);

        $response->say($this->txt('Não recebi sua escolha. Vou encerrar a ligação. Até logo.'), $this->voiceOpts);
        $response->hangup();

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 8 — SELECT: Escolha de opção (DTMF) + pede confirmação (DTMF)
    // =========================================================================

    public function select(Request $request)
    {
        $callSid = $request->input('CallSid');
        $digits  = $request->input('Digits');
        Log::info("[IVR-SELECT] CallSid: {$callSid} | Digits: {$digits}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        $dadosSalvos = $uraCall->selected_option ?? [];
        $opcoes      = $dadosSalvos['opcoes_disponiveis'] ?? [];
        $escolha     = intval($digits);

        $opcaoEscolhida = null;
        foreach ($opcoes as $opcao) {
            if ($opcao['numero'] === $escolha) {
                $opcaoEscolhida = $opcao;
                break;
            }
        }

        if (!$opcaoEscolhida) {
            $gather = $response->gather([
                'numDigits' => 1,
                'action'    => '/api/ura/ivr/select',
                'method'    => 'POST',
                'timeout'   => 15,
            ]);

            $textoOpcoes = 'Opção inválida. As opções são: ';
            foreach ($opcoes as $opcao) {
                $textoOpcoes .= $opcao['descricao_voz'] . ' ';
            }
            $textoOpcoes .= 'Digite o número da opção desejada.';

            $gather->say($this->txt($textoOpcoes), $this->voiceOpts);

            $response->say($this->txt('Não recebi sua escolha. Vou encerrar a ligação. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Salvar opção selecionada
        $uraCall->step = UraCall::STEP_CONFIRMANDO;
        $uraCall->selected_option = array_merge($dadosSalvos, ['opcao_selecionada' => $opcaoEscolhida]);
        $uraCall->save();

        // Pedir confirmação via DTMF
        $gather = $response->gather([
            'numDigits' => 1,
            'action'    => '/api/ura/ivr/confirm',
            'method'    => 'POST',
            'timeout'   => 10,
        ]);

        $defaultConfirm = "Você escolheu a {$opcaoEscolhida['descricao_voz']} "
            . "Para confirmar, digite 1. Para voltar às opções, digite 2.";

        $empresa = Empresa::withoutGlobalScopes()->find($uraCall->empresa_id);
        $confirmMsg = $this->aiService->generateMessage(
            $empresa,
            UraCall::STEP_CONFIRMANDO,
            ['opcao_escolhida' => $opcaoEscolhida['descricao_voz']],
            $defaultConfirm
        );

        $gather->say($this->txt($confirmMsg), $this->voiceOpts);

        $response->say($this->txt('Não recebi sua confirmação. Vou encerrar a ligação. Até logo.'), $this->voiceOpts);
        $response->hangup();

        return $this->twimlResponse($response);
    }

    // =========================================================================
    //  STEP 9 — CONFIRM: Confirma acordo (DTMF) ou volta
    // =========================================================================

    public function confirm(Request $request)
    {
        $callSid = $request->input('CallSid');
        $digits  = $request->input('Digits');
        Log::info("[IVR-CONFIRM] CallSid: {$callSid} | Digits: {$digits}");

        $uraCall = UraCall::findByCallSid($callSid);
        $response = new VoiceResponse();

        if (!$uraCall) {
            $response->say($this->txt('Erro interno. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $this->initVoice($uraCall->empresa_id);

        if ($digits === '2') {
            $uraCall->step = UraCall::STEP_PROPOSTA_APRESENTADA;
            $uraCall->save();
            $response->redirect('/api/ura/ivr/repeat-options', ['method' => 'POST']);
            return $this->twimlResponse($response);
        }

        if ($digits !== '1') {
            $response->say($this->txt('Opção inválida. Vou encerrar a ligação. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        // Confirmar acordo
        $dadosSalvos    = $uraCall->selected_option ?? [];
        $opcaoEscolhida = $dadosSalvos['opcao_selecionada'] ?? null;
        $apiData        = $dadosSalvos['api_data'] ?? null;

        if (!$opcaoEscolhida || !$apiData) {
            $response->say($this->txt('Erro ao processar sua escolha. Até logo.'), $this->voiceOpts);
            $response->hangup();
            return $this->twimlResponse($response);
        }

        $empresa = Empresa::withoutGlobalScopes()->find($uraCall->empresa_id);

        try {
            $response->say($this->txt('Aguarde enquanto processamos seu acordo.'), $this->voiceOpts);

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

            $parcelasTexto = $opcaoEscolhida['parcelas'] == 1
                ? 'pagamento à vista'
                : "{$opcaoEscolhida['parcelas']} parcelas";

            $defaultSuccess = "Perfeito! Seu acordo foi registrado com sucesso. "
                . "Você optou por {$parcelasTexto}. "
                . "Em breve você receberá as instruções de pagamento por S M S. "
                . "Agradecemos sua atenção e desejamos um ótimo dia. Até logo!";

            $successMsg = $this->aiService->generateMessage(
                $empresa,
                'acordo_confirmado',
                ['tipo_pagamento' => $parcelasTexto],
                $defaultSuccess
            );

            $response->say($this->txt($successMsg), $this->voiceOpts);

            Log::info("[IVR-ACORDO-OK] UraCall #{$uraCall->id} | DealId: {$resultado['deal_id']}");

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
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
