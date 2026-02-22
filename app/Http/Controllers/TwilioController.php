<?php

namespace App\Http\Controllers;

use App\Services\IntegracaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TwilioController extends Controller
{
    public function voice(Request $request)
    {
        // Tentar resolver empresa_id pelo contexto (metadata da chamada ou app binding)
        $empresaId = app()->bound('empresa_id') ? app('empresa_id') : null;
        $creds = IntegracaoService::getCredentials($empresaId);
        $agentId = $creds['retell_agent_id'];
        $apiKey = $creds['retell_api_key'];

        Log::info('🎤 TwilioController@voice chamado - Gerando TwiML para Retell');
        Log::info("🤖 Agent ID: {$agentId}");
        Log::info("🔑 API Key: " . substr($apiKey, 0, 10) . "...");
        Log::info("📥 Request Twilio: " . json_encode($request->all()));

        $streamUrl = "wss://api.retellai.com/llm-websocket/{$agentId}";

        Log::info("🌐 Stream URL gerada: {$streamUrl}");

        $twiml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
  <Connect>
    <Stream url="{$streamUrl}">
      <Parameter name="api_key" value="{$apiKey}" />
      <Parameter name="agent_id" value="{$agentId}" />
    </Stream>
  </Connect>
</Response>
XML;

        Log::info("📤 TwiML Response: " . $twiml);

        return response($twiml, 200)
            ->header('Content-Type', 'text/xml');
    }

    public function status(Request $request)
    {
        $status = $request->input('CallStatus');
        $to     = $request->input('To');
        $from   = $request->input('From');
        $sid    = $request->input('CallSid');

        Log::info("📞 Status da chamada: {$status} (SID: {$sid}) | To: {$to} | From: {$from}");

        return response('OK', 200);
    }
}
