<?php

namespace App\Http\Controllers;

use App\Models\Contato;
use App\Services\RetellNegotiationService;
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
        $this->validate($request, [
            'customer_id' => 'required|integer',
        ]);

        $customerId = $request->input('customer_id');

        try {
            // Buscar contato no sistema para obter o CPF
            $contato = Contato::find($customerId);

            if (!$contato) {
                return response()->json([
                    'error'   => true,
                    'message' => 'Contato não encontrado.',
                ], 404);
            }

            $cpf = $contato->cpf;

            if (!$cpf) {
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
