<?php
namespace App\Http\Controllers;

use App\Models\Acordo;
use App\Models\Contato;
use App\Models\Ligacao;
use App\Models\PropostaPagamento;
use App\Services\ApiResponseService;
use App\Services\ValidationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class URADevedorController extends Controller
{
    /**
     * GET /api/ura/devedor/{cpf}
     *
     * Valida CPF e retorna dados de dívidas do cliente
     * Usado pelo agente de voz Retell (tool-validate-cpf e tool-fetch-debts)
     *
     * @param string $cpf CPF do cliente (11 dígitos)
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultarDevida($cpf)
    {
        try {
            // Valida$this->validate($request, [
            $cpfValidado = ValidationService::validateCpf($cpf);
            if (! $cpfValidado) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF inválido',
                    'data'    => [
                        'cpf_valido'  => 'não',
                        'tem_dividas' => 'não',
                    ],
                ], 422);
            }

            // Buscar contato principal (primeiro resultado)
            $contato = Contato::where('cpf', $cpf)
                ->first();

            if (! $contato) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF não encontrado',
                    'data'    => [
                        'cpf_valido'  => 'não',
                        'tem_dividas' => 'não',
                    ],
                ], 404);
            }

            // Buscar TODOS os contatos com este CPF para agregar dívidas
            $contatos = Contato::where('cpf', $cpf)->get();

            // Agregar dados de débito
            $totalDebito       = 0;
            $quantidadeDebitos = 0;
            $debitos           = [];
            $debtIds           = [];

            foreach ($contatos as $c) {
                if ($c->valor_debito && $c->valor_debito > 0) {
                    $quantidadeDebitos++;
                    $totalDebito += $c->valor_debito;

                    $debitos[] = [
                        'debt_id' => "debt_{$c->id}",
                        'contato_id'      => $c->id,
                        'nome'            => $c->nome,
                        'valor'           => $this->valorPorExtenso((float) $c->valor_debito),
                        'data_vencimento' => $c->data_vencimento,
                    ];

                    $debtIds[] = "debt_{$c->id}";
                }
            }

            // Preparar resposta no formato esperado pelo agente Retell
            $temDividas  = $quantidadeDebitos > 0;

            return response()->json([
                'success' => true,
                'message' => $temDividas ? 'Débitos encontrados' : 'Nenhum débito encontrado',
                'data'    => [
                    // Para tool-validate-cpf
                    'cpf_valido' => 'sim',
                    'name'       => $contato->nome,
                    'email'      => $contato->email ?? '',
                    'phone'      => $contato->telefone,
                    'has_debts'  => $temDividas ? 'sim' : 'não',

                    // Para tool-fetch-debts
                    'debt_count' => $quantidadeDebitos,
                    'total_debt' => $this->valorPorExtenso(round($totalDebito, 2)),
                    'debts'      => $debitos,
                    'debt_ids'   => $debtIds,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erro ao consultar dívida:', [
                'cpf'  => $cpf,
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar dívida',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/ura/propostas/{cpf}
     *
     * Retorna propostas de quitação disponíveis (à vista e parceladas)
     * Usado pelo agente de voz Retell (tool-fetch-proposals)
     *
     * @param string $cpf CPF do cliente
     * @return \Illuminate\Http\JsonResponse
     */
    public function consultarPropostas($cpf)
    {
        try {
            // Valida$this->validate($request, [
            $cpfValidado = ValidationService::validateCpf($cpf);
            if (! $cpfValidado) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF inválido',
                ], 422);
            }

            // Buscar contatos
            $contatos = Contato::where('cpf', $cpf)->get();
            if ($contatos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF não encontrado',
                ], 404);
            }

            // Calcular valor total de débito
            $valorTotal = $contatos->sum('valor_debito');
            if ($valorTotal <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum débito ativo',
                ], 404);
            }

            // Gerar opções de parcelamento
            $propostas = $this->gerarOpcoesParcelamentoRetell($valorTotal);

            return response()->json([
                'success' => true,
                'message' => 'Propostas encontradas',
                'data'    => [
                    'cpf'           => $cpf,
                    'customer_name' => $contatos->first()->nome,
                    'total_debt'    => $this->valorPorExtenso(round($valorTotal, 2)),
                    'proposals'     => $propostas,
                ],
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Erro ao consultar propostas:', [
                'cpf'  => $cpf,
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar propostas',
            ], 500);
        }
    }

    /**
     * POST /api/ura/propostas/aceitar
     *
     * Formaliza a proposta escolhida pelo cliente (registra o aceite)
     * Cria registros em PropostaPagamento e Acordo
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function aceitarProposta(Request $request)
    {
        try {
            $dados = $this->validate($request, [
                'cpf'               => 'required|string|size:11',
                'contato_id'        => 'required|integer|exists:contatos,id',
                'opcao_parcelas'    => 'required|integer|in:1,2,3,6,12',
                'valor_total'       => 'required|numeric|min:0.01',
                'desconto_aplicado' => 'nullable|numeric|min:0',
                'call_id'           => 'nullable|string',
                'confirmacao_texto' => 'nullable|string',
            ]);

            $cpfValidado = ValidationService::validateCpf($dados['cpf']);
            if (! $cpfValidado) {
                return ApiResponseService::validationError(
                    'CPF inválido',
                    ['cpf' => 'CPF inválido']
                );
            }

            // Buscar contato
            $contato = Contato::findOrFail($dados['contato_id']);

            // Calcular valor final
            $desconto     = $dados['desconto_aplicado'] ?? 0;
            $valorFinal   = $dados['valor_total'] - $desconto;
            $valorParcela = $valorFinal / $dados['opcao_parcelas'];

            // Criar proposta de pagamento
            $proposta = PropostaPagamento::create([
                'contato_id'     => $contato->id,
                'empresa_id'     => $contato->empresa_id,
                'mailing_id'     => $contato->mailing_id,
                'uuid'           => Str::uuid()->toString(),
                'telefone'       => $contato->telefone,
                'nome_cliente'   => $contato->nome,
                'valor_original' => $dados['valor_total'],
                'valor_proposta' => $valorFinal,
                'desconto'       => $desconto,
                'tipo_proposta'  => 'acordado_voz',
                'status'         => 'proposta_aceita_voz',
                'link_pagamento' => route('pagamento.show', ['uuid' => Str::uuid()]),
                'metadata'       => [
                    'parcelas'      => $dados['opcao_parcelas'],
                    'valor_parcela' => $valorParcela,
                    'call_id'       => $dados['call_id'] ?? null,
                    'aceito_em'     => Carbon::now()->toIso8601String(),
                ],
            ]);

            // Criar registro de acordo
            $acordo = Acordo::create([
                'contato_id'             => $contato->id,
                'empresa_id'             => $contato->empresa_id,
                'proposta_id'            => $proposta->id,
                'aceito_em'              => Carbon::now(),
                'confirmacao_texto'      => $dados['confirmacao_texto'] ?? 'Confirmado via agente de voz Retell',
                'valor_acordado'         => $valorFinal,
                'parcelas_acordadas'     => $dados['opcao_parcelas'],
                'valor_parcela_acordada' => $valorParcela,
                'link_pagamento'         => $proposta->link_pagamento,
                'status'                 => 'ativo',
            ]);

            // Log da ação

            // Converter número de parcelas para extenso
            $parcelasEmExtenso = $this->converterParcelasPorExtenso($dados['opcao_parcelas']);

            return ApiResponseService::success(
                'Proposta formalizada com sucesso',
                [
                    'proposta_id'          => $proposta->id,
                    'acordo_id'            => $acordo->id,
                    'uuid'                 => $proposta->uuid,
                    'valor_total'          => $this->valorPorExtenso(round($valorFinal, 2)),
                    'parcelas'             => $dados['opcao_parcelas'],
                    'parcelas_por_extenso' => $parcelasEmExtenso,
                    'valor_parcela'        => $this->valorPorExtenso(round($valorParcela, 2)),
                    'link_pagamento'       => $proposta->link_pagamento,
                    'proximas_instrucoes'  => 'Proposta registrada. Você receberá SMS com link de pagamento.',
                ],
                201
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseService::validationError(
                'Dados inválidos para formalização',
                $e->errors()
            );
        } catch (\Throwable $e) {
            \Log::error('Erro ao aceitar proposta:', [
                'cpf'   => $request->input('cpf'),
                'erro'  => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return ApiResponseService::serverError(
                'Erro ao formalizar proposta'
            );
        }
    }

    /**
     * GET /api/ura/propostas/{cpf}/resumo
     *
     * Retorna resumo da proposta aceita (acordo ativo)
     * Usado para lembrar o cliente dos detalhes do acordo
     *
     * @param string $cpf CPF do cliente
     * @return \Illuminate\Http\JsonResponse
     */
    public function resumoProposta($cpf)
    {
        try {
            // Validar CPF
            $cpfValidado = ValidationService::validateCpf($cpf);
            if (! $cpfValidado) {
                return ApiResponseService::validationError(
                    'CPF inválido',
                    ['cpf' => 'CPF inválido']
                );
            }

            // Buscar contato
            $contato = Contato::where('cpf', $cpf)->first();
            if (! $contato) {
                return ApiResponseService::notFound('CPF não encontrado');
            }

            // Buscar acordo ativo mais recente
            $acordo = Acordo::where('contato_id', $contato->id)
                ->where('status', 'ativo')
                ->latest('aceito_em')
                ->first();

            if (! $acordo) {
                return ApiResponseService::notFound(
                    'Nenhum acordo ativo encontrado para este CPF'
                );
            }

            // Buscar proposta associada
            $proposta = $acordo->proposta;

            // Formatar dados para apresentação
            $dataVencimento    = $acordo->aceito_em->addDays(30);
            $diasRestantes     = Carbon::now()->diffInDays($dataVencimento, false);
            $statusEmExtenso   = $acordo->status === 'ativo' ? 'acordo ativo' : $acordo->status;
            $parcelasEmExtenso = $this->converterParcelasPorExtenso($acordo->parcelas_acordadas);

            return ApiResponseService::success(
                'Resumo do acordo formalizado',
                [
                    'acordo_id'       => $acordo->id,
                    'proposta_id'     => $proposta->id,
                    'uuid'            => $proposta->uuid,
                    'cliente'         => [
                        'nome'     => $contato->nome,
                        'cpf'      => $cpf,
                        'telefone' => $contato->telefone,
                    ],
                    'detalhes_acordo' => [
                        'data_aceite'               => $acordo->aceito_em->format('d/m/Y H:i'),
                        'valor_total_acordado'      => $this->valorPorExtenso(round($acordo->valor_acordado, 2)),
                        'quantidade_parcelas'       => $acordo->parcelas_acordadas,
                        'quantidade_parcelas_texto' => $parcelasEmExtenso,
                        'valor_parcela'             => $this->valorPorExtenso(round($acordo->valor_parcela_acordada, 2)),
                        'status_acordo'             => $statusEmExtenso,
                    ],
                    'proximos_passos' => [
                        'Link de pagamento: ' . $acordo->link_pagamento,
                        'Verifique seu SMS para o link de pagamento',
                        'Você tem 30 dias para realizar o pagamento',
                    ],
                    'contato_suporte' => [
                        'telefone' => '4020-4020',
                        'email'    => 'suporte@redebrasil.com',
                    ],
                ]
            );

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar resumo de proposta:', [
                'cpf'  => $cpf,
                'erro' => $e->getMessage(),
            ]);

            return ApiResponseService::serverError(
                'Erro ao consultar resumo da proposta'
            );
        }
    }

    /**
     * POST /api/ura/acordos
     *
     * Formaliza e registra o acordo de pagamento escolhido pelo cliente
     * Usado pelo agente de voz Retell (tool-confirm-agreement)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function criarAcordo(Request $request)
    {
        try {
            $dados = $this->validate($request, [
                'cpf'                 => 'required|string|size:11',
                'debt_ids'            => 'required|array',
                'payment_type'        => 'required|string|in:cash,installment',
                'plan_id'             => 'required|string',
                'installments_number' => 'nullable|integer',
                'total_value'         => 'required|numeric|min:0.01',
            ]);

            // Validar CPF
            $cpfValidado = ValidationService::validateCpf($dados['cpf']);
            if (! $cpfValidado) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF inválido',
                ], 422);
            }

            // Buscar contato
            $contato = Contato::where('cpf', $dados['cpf'])->first();
            if (! $contato) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contato não encontrado',
                ], 404);
            }

            // Buscar a última ligação do contato (a que está sendo processada agora)
            $ligacao = Ligacao::where('contato_id', $contato->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $ligacao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma ligação encontrada para este contato',
                ], 404);
            }

            // Calcular dados do acordo
            $numeroParcelas = $dados['installments_number'] ?? 1;
            $valorTotal     = $dados['total_value'];
            $valorParcela   = $numeroParcelas > 1 ? round($valorTotal / $numeroParcelas, 2) : $valorTotal;

            // Converter payment_type para extenso
            $tipoPagementoPorExtenso = $dados['payment_type'] === 'cash' ? 'à vista' : 'parcelado';

            // Gerar IDs únicos
            $acordoId          = Str::uuid()->toString();
            $codigoConfirmacao = strtoupper(Str::random(8));
            $ticketId          = 'AGR-' . date('YmdHis') . '-' . Str::random(4);

            // Criar proposta de pagamento
            $proposta = PropostaPagamento::create([
                'contato_id'              => $contato->id,
                'empresa_id'              => $contato->empresa_id,
                'mailing_id'              => $contato->mailing_id,
                'uuid'                    => $acordoId,
                'telefone'                => $contato->telefone,
                'nome_cliente'            => $contato->nome,
                'valor_original'          => $this->valorPorExtenso($valorTotal),
                'valor_original_numerico' => $valorTotal,
                'valor_proposta'          => $this->valorPorExtenso($valorTotal),
                'valor_proposta_numerico' => $valorTotal,
                'desconto'                => 0,
                'tipo_proposta'           => 'acordado_voz_retell',
                'status'                  => 'aguardando_pagamento',
                'link_pagamento'          => "https://pagamento.redebrasil.com.br/pay/{$acordoId}",
                'metadata' => [
                    'payment_type'      => $dados['payment_type'],
                    'installments'      => $numeroParcelas,
                    'plan_id'           => $dados['plan_id'],
                    'confirmation_code' => $codigoConfirmacao,
                    'agreement_id'      => $ticketId,
                ],
            ]);

            // Criar acordo
            $acordo = Acordo::create([
                'contato_id'             => $contato->id,
                'empresa_id'             => $contato->empresa_id,
                'ligacao_id'             => $ligacao->id,
                'proposta_id'            => $proposta->id,
                'aceito_em'              => Carbon::now(),
                'valor_acordado'         => $this->valorPorExtenso($valorTotal),
                'parcelas_acordadas'     => $numeroParcelas,
                'valor_parcela_acordada' => $this->valorPorExtenso($valorParcela),
                'link_pagamento'         => $proposta->link_pagamento,
                'status'                 => 'pendente_pagamento',
                'confirmacao_texto'      => 'Acordo formalizado via agente de voz Retell',
            ]);

            // Log

            return response()->json([
                'success' => true,
                'message' => 'Acordo formalizado com sucesso',
                'data'    => [
                    'agreement'       => [
                        'agreement_id'      => $ticketId,
                        'confirmation_code' => $codigoConfirmacao,
                        'payment_type'      => $tipoPagementoPorExtenso,
                        'installments'      => $numeroParcelas,
                    ],
                    'payment_details' => [
                        'payment_methods' => [
                            'boleto' => [
                                'barcode' => $this->gerarCodigoBarras($proposta->id, $valorTotal),
                            ],
                            'pix'    => [
                                'pix_key' => 'chave_pix_' . strtolower($codigoConfirmacao),
                            ],
                        ],
                    ],
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Erro ao criar acordo:', [
                'cpf'   => $request->input('cpf'),
                'erro'  => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao formalizar acordo',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/ura/acordo/{tipoAcordo}/{cpf}
     *
     * Cria acordo automaticamente buscando informações do contato via CPF
     * Com tipo de acordo dinâmico (avista, 3vezes, 6vezes, 12vezes, etc)
     * Usado pelo agente de voz Retell (tool-create-agreement)
     *
     * @param string $tipoAcordo Tipo do acordo (avista, 3vezes, 6vezes, 12vezes, etc)
     * @param string $cpf CPF do cliente (11 dígitos)
     * @return \Illuminate\Http\JsonResponse
     */
    public function criarAcordoGet($tipoAcordo, $cpf)
    {
        try {
            // Mapear tipo de acordo para configurações
            $configAcordo = $this->obterConfiguracaoAcordo($tipoAcordo);
            if ($configAcordo === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de acordo inválido. Use: avista, 3vezes, 6vezes, 12vezes ou um número entre 1 e 120 (ex: 36, 48, 60)',
                ], 422);
            }

            // Validar CPF
            $cpfValidado = ValidationService::validateCpf($cpf);
            if (! $cpfValidado) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF inválido',
                ], 422);
            }

            // Buscar contato principal
            $contato = Contato::where('cpf', $cpf)->first();
            if (! $contato) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contato não encontrado',
                ], 404);
            }

            // Buscar TODOS os contatos com este CPF para agregar dívidas
            $contatos = Contato::where('cpf', $cpf)->get();

            // Agregar dados de débito
            $totalDebito = 0;
            $debtIds     = [];

            foreach ($contatos as $c) {
                if ($c->valor_debito && $c->valor_debito > 0) {
                    $totalDebito += $c->valor_debito;
                    $debtIds[]    = "debt_{$c->id}";
                }
            }

            // Validar se há dívidas para criar acordo
            if ($totalDebito <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma dívida encontrada para criar acordo',
                ], 404);
            }

            // Buscar a última ligação do contato (a que está sendo processada agora)
            $ligacao = Ligacao::where('contato_id', $contato->id)
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $ligacao) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma ligação encontrada para este contato',
                ], 404);
            }

            // Calcular dados do acordo com base no tipo de acordo
            $numeroParcelas          = $configAcordo['parcelas'];
            $valorTotal              = round($totalDebito, 2);
            $valorParcela            = $numeroParcelas > 1 ? round($valorTotal / $numeroParcelas, 2) : $valorTotal;
            $paymentType             = $configAcordo['payment_type'];
            $planId                  = $configAcordo['plan_id'];
            $tipoPagementoPorExtenso = $configAcordo['descricao'];

            // Gerar IDs únicos
            $acordoId          = Str::uuid()->toString();
            $codigoConfirmacao = strtoupper(Str::random(8));
            $ticketId          = 'AGR-' . date('YmdHis') . '-' . Str::random(4);

            // Criar proposta de pagamento
            $proposta = PropostaPagamento::create([
                'contato_id'              => $contato->id,
                'empresa_id'              => $contato->empresa_id,
                'mailing_id'              => $contato->mailing_id,
                'uuid'                    => $acordoId,
                'telefone'                => $contato->telefone,
                'nome_cliente'            => $contato->nome,
                'valor_original'          => $valorTotal,
                'valor_original_numerico' => $valorTotal,
                'valor_proposta'          => $valorTotal,
                'valor_proposta_numerico' => $valorTotal,
                'desconto'                => 0,
                'tipo_proposta'           => 'acordado_voz_retell',
                'status'                  => 'aguardando_pagamento',
                'link_pagamento'          => "https://pagamento.redebrasil.com.br/pay/{$acordoId}",
                'metadata' => [
                    'payment_type'      => $paymentType,
                    'installments'      => $numeroParcelas,
                    'plan_id'           => $planId,
                    'confirmation_code' => $codigoConfirmacao,
                    'agreement_id'      => $ticketId,
                ],
            ]);

            // Criar acordo
            $acordo = Acordo::create([
                'contato_id'             => $contato->id,
                'empresa_id'             => $contato->empresa_id,
                'ligacao_id'             => $ligacao->id,
                'proposta_id'            => $proposta->id,
                'aceito_em'              => Carbon::now(),
                'valor_acordado'         => $valorTotal,
                'parcelas_acordadas'     => $numeroParcelas,
                'valor_parcela_acordada' => $valorParcela,
                'link_pagamento'         => $proposta->link_pagamento,
                'status'                 => 'pendente_pagamento',
                'confirmacao_texto'      => 'Acordo formalizado via agente de voz Retell',
            ]);

            // Log

            // ✅ Marcar ligação como tendo acordo firmado
            $ligacao->update(['acordo_id' => $acordo->id]);

                                                                         // Calcular informações de desconto (similar SMARTCOB)
            $descontoPercentual = $numeroParcelas === 1 ? 87.29 : 83.30; // Exemplo de desconto
            $valorComDesconto   = round($valorTotal * (1 - $descontoPercentual / 100), 2);
            $valorDesconto      = round($valorTotal - $valorComDesconto, 2);
            $firstValue         = $numeroParcelas > 1 ? $valorParcela : $valorComDesconto;

            return response()->json([
                'success' => true,
                'message' => 'Acordo formalizado com sucesso',
                'data'    => [
                    'agreement'       => [
                        'id'                => $acordo->id,
                        'agreement_id'      => $ticketId,
                        'confirmation_code' => $codigoConfirmacao,
                        'debitId'           => $contato->id,
                        'status'            => 'pendente_pagamento',
                        'aceito_em'         => $acordo->aceito_em,
                    ],
                    'paymentOption'   => [
                        'id'                => $proposta->id,
                        'firstValue'        => $this->valorPorExtenso(round($firstValue, 2)),
                        'debitValue'        => $this->valorPorExtenso($valorTotal),
                        'installmentNumber' => $numeroParcelas,
                        'installmentValue'  => $this->valorPorExtenso($numeroParcelas > 1 ? $valorParcela : 0),
                        'model'             => $paymentType === 'cash' ? 'ONE_PAYMENT' : 'INSTALLMENTS',
                        'totalValue'        => $this->valorPorExtenso($valorComDesconto),
                        'discountValue'     => $this->valorPorExtenso($valorDesconto),
                        'percDiscount'      => $this->valorPorExtenso(round($descontoPercentual, 2)),
                        'identifier'        => $tipoPagementoPorExtenso,
                        'suggested'         => $numeroParcelas === 1,
                    ],
                    'payment_details' => [
                        'payment_methods' => [
                            'boleto' => [
                                'barcode' => $this->gerarCodigoBarras($proposta->id, $valorTotal),
                            ],
                            'pix'    => [
                                'pix_key' => 'chave_pix_' . strtolower($codigoConfirmacao),
                            ],
                        ],
                    ],
                    'processing'      => false,
                    'completed'       => true,
                ],
            ], 201);

        } catch (\Throwable $e) {
            \Log::error('Erro ao criar acordo via GET:', [
                'cpf'   => $cpf,
                'erro'  => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao formalizar acordo',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/ura/acordos/{cpf}/opcoes
     *
     * Retorna opções de pagamento disponíveis para um CPF
     * Usado pelo agente de voz Retell (tool-fetch-payment-options)
     *
     * @param string $cpf CPF do cliente (11 dígitos)
     * @return \Illuminate\Http\JsonResponse
     */
    public function obterOpcoesAcordo($cpf)
    {
        try {
            // Validar CPF
            $cpfValidado = ValidationService::validateCpf($cpf);
            if (! $cpfValidado) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF inválido',
                ], 422);
            }

            // Buscar contato principal
            $contato = Contato::where('cpf', $cpf)->first();
            if (! $contato) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contato não encontrado',
                ], 404);
            }

            // Buscar TODOS os contatos com este CPF para agregar dívidas
            $contatos = Contato::where('cpf', $cpf)->get();

            // Agregar dados de débito
            $totalDebito = 0;
            foreach ($contatos as $c) {
                if ($c->valor_debito && $c->valor_debito > 0) {
                    $totalDebito += $c->valor_debito;
                }
            }

            // Validar se há dívidas
            if ($totalDebito <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma dívida encontrada',
                ], 404);
            }

            $valorTotal = round($totalDebito, 2);

            // Gerar opções de pagamento similares a SMARTCOB
            $opcoes = $this->gerarOpcoesAcordo($valorTotal);

            return response()->json([
                'success' => true,
                'message' => 'Opções de pagamento consultadas com sucesso',
                'data'    => [
                    'cpf'            => $cpf,
                    'nome'           => $contato->nome,
                    'total_divida'   => $this->valorPorExtenso($valorTotal),
                    'valor_numerico' => $valorTotal,
                    'opcoes'         => $opcoes,
                ],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erro ao consultar opções de pagamento', [
                'cpf'   => $cpf,
                'erro'  => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar opções de pagamento',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/ura/acordos/{acordoId}/status
     *
     * Retorna status de um acordo em processamento
     * Usado pelo agente de voz Retell (tool-check-agreement-status)
     *
     * @param string $acordoId ID do acordo (UUID ou ID)
     * @return \Illuminate\Http\JsonResponse
     */
    public function obterStatusAcordo($acordoId)
    {
        try {
            // Buscar acordo pelo UUID ou ID
            $acordo = Acordo::where('id', $acordoId)
                ->orWhere('uuid', $acordoId)
                ->first();

            if (! $acordo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acordo não encontrado',
                ], 404);
            }

            // Buscar proposta relacionada
            $proposta = PropostaPagamento::find($acordo->proposta_id);

            // Determinar status de processamento
            $processing = $acordo->status === 'pendente_pagamento' || $acordo->status === 'processando';
            $completed  = $acordo->status === 'concluido' || $acordo->status === 'pago';

            return response()->json([
                'success' => true,
                'message' => 'Status do acordo consultado com sucesso',
                'data'    => [
                    'id'                 => $acordo->id,
                    'agreement_id'       => $acordo->uuid,
                    'status'             => $acordo->status,
                    'processing'         => $processing,
                    'completed'          => $completed,
                    'aceito_em'          => $acordo->aceito_em,
                    'valor_acordado'     => $this->valorPorExtenso($acordo->valor_acordado),
                    'parcelas_acordadas' => $acordo->parcelas_acordadas,
                    'link_pagamento'     => $acordo->link_pagamento,
                    'proposta'           => [
                        'id'     => $proposta->id ?? null,
                        'uuid'   => $proposta->uuid ?? null,
                        'status' => $proposta->status ?? null,
                    ],
                ],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erro ao consultar status do acordo', [
                'acordoId' => $acordoId,
                'erro'     => $e->getMessage(),
                'stack'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar status do acordo',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/ura/escalar
     *
     * Escala para um supervisor humano
     * Usado pelo agente de voz Retell (tool-escalate-supervisor)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function escalarSupervisor(Request $request)
    {
        try {
            $dados = $this->validate($request, [
                'cpf'                  => 'required|string|size:11',
                'customer_name'        => 'nullable|string',
                'escalation_reason'    => 'nullable|string',
                'conversation_summary' => 'nullable|string',
                'negotiation_attempts' => 'nullable|integer',
            ]);

            // Validar CPF
            $cpfValidado = ValidationService::validateCpf($dados['cpf']);
            if (! $cpfValidado) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF inválido',
                ], 422);
            }

            // Buscar contato
            $contato = Contato::where('cpf', $dados['cpf'])->first();
            if (! $contato) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contato não encontrado',
                ], 404);
            }

            // Gerar ID do ticket
            $ticketId = 'TICKET-' . date('YmdHis') . '-' . Str::random(6);

            // Log de escalonamento

            return response()->json([
                'success' => true,
                'message' => 'Escalonamento iniciado',
                'data'    => [
                    'escalation'       => [
                        'ticket_id' => $ticketId,
                    ],
                    'transfer_details' => [
                        'supervisor_available'          => true,
                        'estimated_wait_time_formatted' => '2-3 minutos',
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Erro ao escalar para supervisor:', [
                'cpf'  => $request->input('cpf'),
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao escalar para supervisor',
            ], 500);
        }
    }

    /**
     * Gera opções de parcelamento no formato esperado pelo Retell
     *
     * @param float $valorTotal Valor total da dívida
     * @return array Propostas com cash e installments
     */
    private function gerarOpcoesParcelamentoRetell(float $valorTotal): array
    {
        $hoje            = Carbon::now();
        $vencimentoVista = $hoje->addDays(7)->format('d-m-Y');

        return [
            'cash'         => [
                'description'     => 'Pagamento à vista',
                'payment_type'    => 'à vista',
                'value'           => $this->valorPorExtenso(round($valorTotal * 0.85, 2)), // 15% desconto à vista
                'due_date'        => $this->dataPorExtenso($vencimentoVista),
                'discount_amount' => $this->valorPorExtenso(round($valorTotal * 0.15, 2)),
                'discount_label'  => 'quinze por cento de desconto',
            ],
            'installments' => [
                [
                    'description'           => 'Sessenta parcelas mensais',
                    'plan_id'               => 'plan-60x',
                    'installments_number'   => 60,
                    'installments_label'    => 'sessenta parcelas',
                    'value_per_installment' => $this->valorPorExtenso(round($valorTotal / 60, 2)),
                    'total_value'           => $this->valorPorExtenso($valorTotal),
                    'interest_rate'         => 0,
                ],
                [
                    'description'           => 'Quarenta e oito parcelas mensais',
                    'plan_id'               => 'plan-48x',
                    'installments_number'   => 48,
                    'installments_label'    => 'quarenta e oito parcelas',
                    'value_per_installment' => $this->valorPorExtenso(round($valorTotal / 48, 2)),
                    'total_value'           => $this->valorPorExtenso($valorTotal),
                    'interest_rate'         => 0,
                ],
                [
                    'description'           => 'Trinta e seis parcelas mensais',
                    'plan_id'               => 'plan-36x',
                    'installments_number'   => 36,
                    'installments_label'    => 'trinta e seis parcelas',
                    'value_per_installment' => $this->valorPorExtenso(round($valorTotal / 36, 2)),
                    'total_value'           => $this->valorPorExtenso($valorTotal),
                    'interest_rate'         => 0,
                ],
            ],
        ];
    }

    /**
     * Gera código de barras simulado para boleto
     *
     * @param int $propostaId
     * @param float $valor
     * @return string
     */
    private function gerarCodigoBarras(int $propostaId, float $valor): string
    {
        $numero          = str_pad($propostaId, 5, '0', STR_PAD_LEFT);
        $valor_formatado = str_pad(intval($valor * 100), 10, '0', STR_PAD_LEFT);
        return "33790.12345 {$numero}000 {$valor_formatado}1 12345678901234";
    }

    /**
     * Converte número de parcelas para extenso
     *
     * @param int $numero
     * @return string
     */
    private function converterParcelasPorExtenso(int $numero): string
    {
        $mapa = [
            1  => 'uma parcela',
            2  => 'duas parcelas',
            3  => 'três parcelas',
            6  => 'seis parcelas',
            12 => 'doze parcelas',
            24 => 'vinte e quatro parcelas',
            36 => 'trinta e seis parcelas',
            48 => 'quarenta e oito parcelas',
            60 => 'sessenta parcelas',
        ];

        return $mapa[$numero] ?? "{$numero} parcelas";
    }

    public function valorPorExtenso($valor)
    {
        // Separa reais e centavos
        $partes   = explode('.', number_format($valor, 2, '.', ''));
        $reais    = $partes[0];
        $centavos = isset($partes[1]) ? $partes[1] : '00';

        // Converte reais por extenso
        $extensoReais = $this->converterNumero($reais);

        // Adiciona "Real" ou "Reais"
        if ($reais == '1') {
            $extensoReais .= ' Real';
        } elseif ($reais == '0') {
            $extensoReais = '';
        } else {
            $extensoReais .= ' Reais';
        }

        // Converte centavos por extenso
        $extensoCentavos = '';
        if ($centavos != '00' && $centavos != '0') {
            $extensoCentavos = $this->converterNumero($centavos);

            // Adiciona "Centavo" ou "Centavos"
            if ($centavos == '01' || $centavos == '1') {
                $extensoCentavos .= ' Centavo';
            } else {
                $extensoCentavos .= ' Centavos';
            }
        }

        // Combina reais e centavos
        $resultado = trim($extensoReais);
        if ($extensoCentavos != '') {
            if ($resultado != '') {
                $resultado .= ' e ' . $extensoCentavos;
            } else {
                $resultado = $extensoCentavos;
            }
        }

        // Caso seja zero reais e zero centavos
        if ($resultado == '') {
            $resultado = 'Zero Reais';
        }

        return ucfirst($resultado);
    }

    public function converterNumero($numero)
    {
        $unidades = [
            '', 'Um', 'Dois', 'Três', 'Quatro', 'Cinco', 'Seis', 'Sete', 'Oito', 'Nove',
            'Dez', 'Onze', 'Doze', 'Treze', 'Catorze', 'Quinze', 'Dezesseis', 'Dezessete',
            'Dezoito', 'Dezenove',
        ];

        $dezenas = [
            '', '', 'Vinte', 'Trinta', 'Quarenta', 'Cinquenta', 'Sessenta',
            'Setenta', 'Oitenta', 'Noventa',
        ];

        $centenas = [
            '', 'Cem', 'Duzentos', 'Trezentos', 'Quatrocentos', 'Quinhentos',
            'Seiscentos', 'Setecentos', 'Oitocentos', 'Novecentos',
        ];

        $milhares = [
            '', 'Mil', 'Milhão', 'Bilhão', 'Trilhão',
        ];

        $numero = ltrim($numero, '0');
        if ($numero == '') {
            return 'Zero';
        }

        // Converte string para inteiro para manipulação
        $num = intval($numero);

        if ($num < 20) {
            return $unidades[$num];
        } elseif ($num < 100) {
            $dezena    = floor($num / 10);
            $unidade   = $num % 10;
            $resultado = $dezenas[$dezena];
            if ($unidade > 0) {
                $resultado .= ' e ' . $unidades[$unidade];
            }
            return $resultado;
        } elseif ($num < 1000) {
            $centena   = floor($num / 100);
            $resto     = $num % 100;
            $resultado = $centenas[$centena];

            // Tratamento especial para "Cem" -> "Cento"
            if ($centena == 1 && $resto > 0) {
                $resultado = 'Cento';
            }

            if ($resto > 0) {
                if ($resto < 20) {
                    $resultado .= ' e ' . $unidades[$resto];
                } else {
                    $dezena     = floor($resto / 10);
                    $unidade    = $resto % 10;
                    $resultado .= ' e ' . $dezenas[$dezena];
                    if ($unidade > 0) {
                        $resultado .= ' e ' . $unidades[$unidade];
                    }
                }
            }
            return $resultado;
        } elseif ($num < 1000000) {
            $mil   = floor($num / 1000);
            $resto = $num % 1000;

            if ($mil == 1) {
                $resultado = 'Mil';
            } else {
                $resultado = $this->converterNumero($mil) . ' Mil';
            }

            if ($resto > 0) {
                if ($resto < 100 || ($resto % 100 == 0)) {
                    $resultado .= ' e ' . $this->converterNumero($resto);
                } else {
                    $resultado .= ' ' . $this->converterNumero($resto);
                }
            }
            return $resultado;
        } else {
            // Para números maiores (milhões, bilhões, etc.)
            return 'Número muito grande para conversão';
        }
    }

    public function dataPorExtenso($data)
    {
        // Validação do formato da data (DD-MM-YYYY)
        if (! preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $data, $matches)) {
            return "Formato de data inválido. Use DD-MM-YYYY";
        }

        $dia = (int) $matches[1];
        $mes = (int) $matches[2];
        $ano = (int) $matches[3];

        // Validação dos valores
        if (! checkdate($mes, $dia, $ano)) {
            return "Data inválida";
        }

        // Arrays com nomes dos dias, meses, etc.
        $diasDaSemana = [
            'Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira',
            'Quinta-feira', 'Sexta-feira', 'Sábado',
        ];

        $meses = [
            1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro',
        ];

        // Dia da semana
        $timestamp   = mktime(0, 0, 0, $mes, $dia, $ano);
        $diaDaSemana = $diasDaSemana[date('w', $timestamp)];

        // Dia por extenso
        $diaExtenso = $this->numeroParaExtenso($dia);

        // Mês por extenso
        $mesExtenso = $meses[$mes];

        // Ano por extenso
        $anoExtenso = $this->anoPorExtenso($ano);

        // Formatação final
        return "$diaDaSemana, $diaExtenso de $mesExtenso de $anoExtenso";
    }

    public function numeroParaExtenso($numero)
    {
        $unidades = [
            '', 'Um', 'Dois', 'Três', 'Quatro', 'Cinco', 'Seis', 'Sete', 'Oito', 'Nove',
            'Dez', 'Onze', 'Doze', 'Treze', 'Catorze', 'Quinze', 'Dezesseis', 'Dezessete',
            'Dezoito', 'Dezenove',
        ];

        $dezenas = [
            '', '', 'Vinte', 'Trinta', 'Quarenta', 'Cinquenta', 'Sessenta',
            'Setenta', 'Oitenta', 'Noventa',
        ];

        if ($numero < 20) {
            return $unidades[$numero];
        } elseif ($numero < 100) {
            $dezena    = floor($numero / 10);
            $unidade   = $numero % 10;
            $resultado = $dezenas[$dezena];
            if ($unidade > 0) {
                $resultado .= ' e ' . $unidades[$unidade];
            }
            return $resultado;
        } else {
            return "Dia inválido";
        }
    }

    public function anoPorExtenso($ano)
    {
        // Separa o ano em séculos e anos
        if ($ano < 1000) {
            return $this->numeroParaExtenso($ano);
        }

        $milhar  = floor($ano / 1000);
        $centena = floor(($ano % 1000) / 100);
        $dezena  = $ano % 100;

        $extenso = '';

        // Milhar (para anos como 2000, 3000, etc.)
        if ($milhar > 0) {
            if ($milhar == 1) {
                $extenso .= 'Mil';
            } else {
                $extenso .= $this->numeroParaExtenso($milhar) . ' Mil';
            }
        }

        // Centena
        if ($centena > 0) {
            if (! empty($extenso)) {
                $extenso .= ' ';
            }

            if ($centena == 1) {
                if ($dezena == 0) {
                    $extenso .= 'Cem';
                } else {
                    $extenso .= 'Cento';
                }
            } else {
                $centenas  = [
                    '', 'Cem', 'Duzentos', 'Trezentos', 'Quatrocentos',
                    'Quinhentos', 'Seiscentos', 'Setecentos', 'Oitocentos', 'Novecentos',
                ];
                $extenso .= $centenas[$centena];
            }
        }

        // Dezena e unidade
        if ($dezena > 0) {
            if (! empty($extenso)) {
                $extenso .= ' e ';
            }
            $extenso .= $this->numeroParaExtenso($dezena);
        }

        return $extenso;
    }

    /**
     * Gera opções de pagamento para um valor total
     * Similares ao modelo SMARTCOB com entrada e parcelas
     *
     * @param float $valorTotal Valor total da dívida
     * @return array Opções de pagamento
     */
    private function gerarOpcoesAcordo($valorTotal)
    {
        $opcoes             = [];
        $descontoPercentual = 0.15; // Desconto de 15% como exemplo

        // Configurações de opções (parcelas e descrições)
        $configuracoes = [
            [
                'id'                  => 'opt_avista',
                'parcelas'            => 1,
                'descricao'           => 'À Vista',
                'desconto_percentual' => 0.87, // 87% de desconto
            ],
            [
                'id'                  => 'opt_2x',
                'parcelas'            => 2,
                'descricao'           => '2 parcelas',
                'desconto_percentual' => 0.833, // 83.3% de desconto
            ],
            [
                'id'                  => 'opt_3x',
                'parcelas'            => 3,
                'descricao'           => '3 parcelas',
                'desconto_percentual' => 0.83,
            ],
            [
                'id'                  => 'opt_6x',
                'parcelas'            => 6,
                'descricao'           => '6 parcelas',
                'desconto_percentual' => 0.8313,
            ],
            [
                'id'                  => 'opt_12x',
                'parcelas'            => 12,
                'descricao'           => '12 parcelas',
                'desconto_percentual' => 0.8289,
            ],
            [
                'id'                  => 'opt_24x',
                'parcelas'            => 24,
                'descricao'           => '24 parcelas',
                'desconto_percentual' => 0.824,
            ],
        ];

        foreach ($configuracoes as $config) {
            // Calcular valor com desconto
            $valorComDesconto = $valorTotal * (1 - $config['desconto_percentual']);
            $valorParcela     = $config['parcelas'] > 0 ? round($valorComDesconto / $config['parcelas'], 2) : $valorComDesconto;
            $firstValue       = $config['parcelas'] > 1 ? $valorParcela : $valorComDesconto;

            $opcoes[] = [
                'id'                   => $config['id'],
                'parcelas'             => $config['parcelas'],
                'descricao'            => $config['descricao'],
                'valor_total_original' => $valorTotal,
                'valor_com_desconto'   => round($valorComDesconto, 2),
                'desconto_valor'       => round($valorTotal - $valorComDesconto, 2),
                'desconto_percentual'  => round($config['desconto_percentual'] * 100, 2),
                'firstValue'           => round($firstValue, 2), // Entrada ou valor à vista
                'installmentValue'     => $config['parcelas'] > 1 ? $valorParcela : 0,
                'model'                => $config['parcelas'] === 1 ? 'ONE_PAYMENT' : 'INSTALLMENTS',
                'identifier'           => $config['parcelas'] === 1
                    ? "À Vista R$ " . number_format($valorComDesconto, 2, ',', '.')
                    : "Entrada de R$ " . number_format($firstValue, 2, ',', '.') . " + " . $config['parcelas'] . "x de R$ " . number_format($valorParcela, 2, ',', '.'),
                'suggested'            => $config['parcelas'] === 1, // À vista é sugerido por padrão
            ];
        }

        return $opcoes;
    }

    /**
     * Obtém configuração de acordo baseado no tipo
     * Suporta tipos predefinidos (avista, 3vezes, etc) ou números dinâmicos (36, 48, 60, etc)
     *
     * @param string $tipoAcordo Tipo de acordo (avista, 3vezes, 6vezes, 12vezes, 36, 48, 60, etc)
     * @return array|null Configuração do acordo ou null se inválido
     */
    private function obterConfiguracaoAcordo($tipoAcordo)
    {
        // Configurações predefinidas com IDs de plano específicos
        $configuracoesPredefinidas = [
            'avista' => [
                'parcelas'     => 1,
                'payment_type' => 'cash',
                'plan_id'      => 'plan_avista',
                'descricao'    => 'à vista',
            ],
            '3x'     => [
                'parcelas'     => 3,
                'payment_type' => 'installment',
                'plan_id'      => 'plan_3x',
                'descricao'    => 'em 3 parcelas',
            ],
            '6x'     => [
                'parcelas'     => 6,
                'payment_type' => 'installment',
                'plan_id'      => 'plan_6x',
                'descricao'    => 'em 6 parcelas',
            ],
            '10x'    => [
                'parcelas'     => 10,
                'payment_type' => 'installment',
                'plan_id'      => 'plan_10x',
                'descricao'    => 'em 10 parcelas',
            ],
            '12x'    => [
                'parcelas'     => 12,
                'payment_type' => 'installment',
                'plan_id'      => 'plan_12x',
                'descricao'    => 'em 12 parcelas',
            ],
            '36x'    => [
                'parcelas'     => 36,
                'payment_type' => 'installment',
                'plan_id'      => 'plan_36x',
                'descricao'    => 'em 36 parcelas',
            ],
            '48x'    => [
                'parcelas'     => 48,
                'payment_type' => 'installment',
                'plan_id'      => 'plan_48x',
                'descricao'    => 'em 48 parcelas',
            ],
            '60x'    => [
                'parcelas'     => 60,
                'payment_type' => 'installment',
                'plan_id'      => 'plan_60x',
                'descricao'    => 'em 60 parcelas',
            ],
        ];

        $tipoAcordoLower = strtolower($tipoAcordo);

        // Verifica se é um tipo predefinido
        if (isset($configuracoesPredefinidas[$tipoAcordoLower])) {
            return $configuracoesPredefinidas[$tipoAcordoLower];
        }

        // Tenta parsear como número de parcelas dinâmico
        if (is_numeric($tipoAcordo)) {
            $parcelas = (int) $tipoAcordo;

            // Valida se é um número válido (entre 1 e 120)
            if ($parcelas >= 1 && $parcelas <= 120) {
                if ($parcelas == 1) {
                    // Se for 1 parcela, é à vista
                    return [
                        'parcelas'     => 1,
                        'payment_type' => 'cash',
                        'plan_id'      => 'plan_avista',
                        'descricao'    => 'à vista',
                    ];
                } else {
                    // Para múltiplas parcelas
                    return [
                        'parcelas'     => $parcelas,
                        'payment_type' => 'installment',
                        'plan_id'      => "plan_{$parcelas}x",
                        'descricao' => "em {$parcelas} parcelas",
                    ];
                }
            }
        }

        return null;
    }
}
