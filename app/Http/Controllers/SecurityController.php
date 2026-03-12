<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Contato;
use App\Models\Ligacao;
use App\Services\SecurityService;

class SecurityController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Endpoint chamado pelo Retell AI via Custom LLM Function
     * para validar CPF e Data de Nascimento durante a chamada
     *
     * POST /api/ura/validate-security
     */
    public function validateSecurity(Request $request)
    {
        $validated = $this->validate($request, [
            'call_id' => 'required|string',
            'contato_id' => 'required|integer',
            'cpf_informado' => 'required|string',
            'data_nascimento_informada' => 'required|date',
        ]);

        try {

            // Buscar contato
            $contato = Contato::findOrFail($validated['contato_id']);

            // Buscar ligação
            $ligacao = Ligacao::where('call_id_retell', $validated['call_id'])
                              ->where('contato_id', $contato->id)
                              ->firstOrFail();

            // Verificar se já atingiu limite de tentativas
            if ($ligacao->atingiuLimiteValidacao(3)) {

                return response()->json([
                    'valid' => false,
                    'message' => 'Limite de tentativas de validação atingido',
                    'tentativas_restantes' => 0,
                    'bloqueado' => true
                ], 429);
            }

            // Validar CPF (primeiros 3 dígitos)
            $cpfValido = $this->securityService->compararPrimeirosDigitosCpf(
                $contato->cpf,
                $validated['cpf_informado']
            );

            // Validar data de nascimento
            $dataValida = $this->securityService->compararDataNascimento(
                $contato->data_nascimento->format('Y-m-d'),
                $validated['data_nascimento_informada']
            );

            // Validação bem-sucedida
            if ($cpfValido && $dataValida) {
                $ligacao->registrarValidacaoSucesso(
                    $validated['cpf_informado'],
                    $validated['data_nascimento_informada']
                );

                // Atualizar status do contato
                $contato->status = 'validado';
                $contato->save();


                return response()->json([
                    'valid' => true,
                    'message' => 'Dados validados com sucesso',
                    'contato' => [
                        'nome' => $contato->nome_completo,
                        'valor_devido' => number_format($contato->valor_debito, 2, ',', '.'),
                        'empresa_credora' => $contato->empresa_credora,
                    ]
                ]);
            }

            // Validação falhou
            $ligacao->registrarValidacaoFalha(
                $validated['cpf_informado'],
                $validated['data_nascimento_informada']
            );

            $tentativasRestantes = 3 - $ligacao->tentativas_validacao;


            return response()->json([
                'valid' => false,
                'message' => 'Dados não conferem',
                'tentativas_restantes' => $tentativasRestantes,
                'bloqueado' => $tentativasRestantes <= 0
            ], 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('❌ Contato ou ligação não encontrado');

            return response()->json([
                'valid' => false,
                'message' => 'Contato ou ligação não encontrado',
            ], 404);
        } catch (\Exception $e) {
            Log::error('❌ Erro ao validar segurança: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'valid' => false,
                'message' => 'Erro interno ao processar validação',
            ], 500);
        }
    }
}
