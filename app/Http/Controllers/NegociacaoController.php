<?php

namespace App\Http\Controllers;

use App\Models\Contato;
use App\Services\RetellNegotiationService;
use App\Services\SecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NegociacaoController extends Controller
{
    protected RetellNegotiationService $negotiationService;

    public function __construct(RetellNegotiationService $negotiationService)
    {
        $this->negotiationService = $negotiationService;
    }

    /**
     * POST /api/negociacao/buscar
     *
     * Busca negociação na API Adora e retorna dados normalizados para o Retell.
     */
    public function buscar(Request $request): JsonResponse
    {

        $customerId = $request->input('customer_id');
        $doc = $request->input('doc');

        try {
            $cpf = null;

            // Tentar buscar pelo customer_id (contato_id) se for numérico
            if ($customerId && is_numeric($customerId)) {
                $contato = Contato::find($customerId);

                if ($contato && $contato->cpf) {
                    $cpfRaw = $contato->cpf;

                    // CPF pode estar encriptado ou em texto plano
                    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfRaw);
                    if (strlen($cpfLimpo) === 11) {
                        $cpf = $cpfLimpo;
                    } else {
                        $security = app(SecurityService::class);
                        $cpf = $security->decryptCpf($cpfRaw);
                    }
                }
            }

            // Fallback: usar doc (CPF) enviado diretamente pelo Retell
            if (empty($cpf) && !empty($doc)) {
                $cpfLimpo = preg_replace('/[^0-9]/', '', $doc);
                if (strlen($cpfLimpo) === 11) {
                    $cpf = $cpfLimpo;

                }
            }

            if (empty($cpf)) {
                Log::error("[NEGOCIACAO] CPF não encontrado", [
                    'customer_id' => $customerId,
                    'doc' => $doc,
                ]);
                return response()->json([
                    'error'   => true,
                    'message' => 'CPF/CNPJ não encontrado para este contato.',
                ], 422);
            }

            $resultado = $this->negotiationService->buscarNegociacao($cpf);

            return response()->json($resultado);

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error("[NEGOCIACAO] Timeout na API externa", [
                'customer_id' => $customerId,
                'error'       => $e->getMessage(),
            ]);

            return response()->json([
                'error'   => true,
                'message' => 'API externa indisponível. Tente novamente.',
            ], 504);

        } catch (\RuntimeException $e) {
            Log::error("[NEGOCIACAO] Erro: {$e->getMessage()}", [
                'customer_id' => $customerId,
            ]);

            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 502);

        } catch (\Exception $e) {
            Log::error("[NEGOCIACAO] Erro inesperado: {$e->getMessage()}", [
                'customer_id' => $customerId,
                'trace'       => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error'   => true,
                'message' => 'Erro interno ao processar negociação.',
            ], 500);
        }
    }
}
