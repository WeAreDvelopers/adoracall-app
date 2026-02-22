<?php
namespace App\Http\Controllers;

use App\Models\Contato;
use App\Models\Empresa;
use App\Models\ImportLog;
use App\Models\Ligacao;
use App\Models\Mailing;
use App\Models\QueueJob;
use App\Models\Script;
use App\Models\ScriptLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Services\ValidationService;
use App\Services\ApiResponseService;

class MailingController extends Controller
{
    /**
     * Lista todos os mailings com estatísticas
     * GET /api/filas_campanha
     */
    public function index(Request $request)
    {
        $query = Mailing::with('script');

        // Filtros
        if ($request->has('status')) {
            $query->porStatus($request->status);
        }

        if ($request->has('script_id')) {
            $query->where('script_id', $request->script_id);
        }

        if ($request->has('prioridade')) {
            $query->porPrioridade($request->prioridade);
        }

        // Ordenação
        $query->recentes();

        // Paginação
        $perPage  = $request->get('per_page', 15);
        $mailings = $query->paginate($perPage);

        // Enriquecer com estatísticas e transformar resposta
        $filas = [];
        foreach ($mailings->items() as $mailing) {
            $stats = $this->getMailingStats($mailing->id);

            $filas[] = [
                'id'           => $mailing->id,
                'nome'         => $mailing->nome,
                'descricao'    => $mailing->descricao,
                'script'       => $mailing->script ? $mailing->script->nome : 'N/A',
                'script_id'    => $mailing->script_id,
                'status'       => $mailing->status,
                'prioridade'   => $mailing->prioridade,
                'stats'        => $stats,
                'progresso'    => $this->calcularProgresso($stats),
                'taxa_sucesso' => $stats['taxa_sucesso'],
            ];
        }

        return response()->json([
            'data' => $filas,
            'pagination' => [
                'total'        => $mailings->total(),
                'per_page'     => $mailings->perPage(),
                'current_page' => $mailings->currentPage(),
                'last_page'    => $mailings->lastPage(),
            ],
        ]);
    }

    /**
     * Calcula as estatísticas de um mailing baseado em seus QueueJobs
     */
    private function getMailingStats(int $mailingId): array
    {
        $stats = QueueJob::where('mailing_id', $mailingId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status = \'processing\' THEN 1 ELSE 0 END) as processando,
                SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as falhados
            ')
            ->first();

        $processados = ($stats->completados ?? 0) + ($stats->falhados ?? 0);
        $taxaSucesso = 0;
        if ($processados > 0) {
            $taxaSucesso = round((($stats->completados ?? 0) / $processados) * 100, 2);
        }

        return [
            'total'        => (int) ($stats->total ?? 0),
            'pendentes'    => (int) ($stats->pendentes ?? 0),
            'processando'  => (int) ($stats->processando ?? 0),
            'completados'  => (int) ($stats->completados ?? 0),
            'falhados'     => (int) ($stats->falhados ?? 0),
            'processados'  => (int) $processados,
            'taxa_sucesso' => (float) $taxaSucesso,
        ];
    }

    /**
     * Calcula o percentual de progresso de um mailing
     */
    private function calcularProgresso(array $stats): int
    {
        if ($stats['total'] == 0) {
            return 0;
        }
        return (int) round(($stats['processados'] / $stats['total']) * 100);
    }

    /**
     * Cria um novo mailing (campanha)
     * POST /filas_campanha
     *
     * Parâmetros suportados:
     * - nome (string, obrigatório): Nome da campanha
     * - descricao (string): Descrição da campanha
     * - tipo_publico (enum): Tipo de estratégia - atraso_leve, atraso_medio, atraso_alto, inadimplencia_critica, leads_novos
     * - velocidade_contatos_hora (int): Velocidade de processamento (5-500 contatos/hora)
     * - prioridade (enum): baixa, normal, alta, urgente
     * - max_tentativas (int): Número máximo de tentativas (1-10)
     * - intervalo_retry (int): Intervalo entre tentativas em minutos (5-1440)
     * - data_inicio_agendado (datetime): Data/hora de início agendado (Y-m-d\TH:i)
     * - data_fim_agendado (datetime): Data/hora de término agendado (Y-m-d\TH:i)
     * - script_id (int): ID do script (padrão: 1)
     *
     * Tipos de público (estratégias):
     * - atraso_leve: 1-15 dias de atraso (6 tentativas, 50 contatos/hora)
     * - atraso_medio: 16-60 dias de atraso (6 tentativas, 35 contatos/hora)
     * - atraso_alto: 61-180 dias de atraso (5 tentativas, 25 contatos/hora)
     * - inadimplencia_critica: Pré-jurídico (3 tentativas, 20 contatos/hora)
     * - leads_novos: Validação de contatos novos (4 tentativas, 55 contatos/hora)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome'                     => 'required|string|max:255',
            'script_id'                => 'nullable|integer|exists:scripts,id',
            'descricao'                => 'nullable|string|max:1000',
            'tipo_publico'             => 'nullable|in:atraso_leve,atraso_medio,atraso_alto,inadimplencia_critica,leads_novos',
            'velocidade_contatos_hora' => 'nullable|integer|min:5|max:500',
            'prioridade'               => 'nullable|in:baixa,normal,alta,urgente',
            'max_tentativas'           => 'nullable|integer|min:1|max:10',
            'intervalo_retry'          => 'nullable|integer|min:5|max:1440',
            'filtros'                  => 'nullable|array',
            'status'                   => 'nullable|in:pronto,ativo,pausado,concluido,cancelado',
            'data_inicio_agendado'     => 'nullable|date_format:Y-m-d\TH:i',
            'data_fim_agendado'        => 'nullable|date_format:Y-m-d\TH:i|after:data_inicio_agendado',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $dados = $request->all();

            // Definir status padrão
            if (! isset($dados['status'])) {
                $dados['status'] = 'pronto';
            }

            // Definir script padrão se não fornecido
            if (! isset($dados['script_id']) || is_null($dados['script_id'])) {
                $dados['script_id'] = 1; // ID do Script Padrão
            }

            // Converter datetime-local para datetime
            if (isset($dados['data_inicio_agendado']) && $dados['data_inicio_agendado']) {
                $dados['data_inicio_agendado'] = str_replace('T', ' ', $dados['data_inicio_agendado']);
            }
            if (isset($dados['data_fim_agendado']) && $dados['data_fim_agendado']) {
                $dados['data_fim_agendado'] = str_replace('T', ' ', $dados['data_fim_agendado']);
            }

            // Definir valores padrão
            $dados['velocidade_contatos_hora'] = $dados['velocidade_contatos_hora'] ?? 60;
            $dados['prioridade']               = $dados['prioridade'] ?? 'normal';
            $dados['max_tentativas']           = $dados['max_tentativas'] ?? 3;
            $dados['intervalo_retry']          = $dados['intervalo_retry'] ?? 60;
            $dados['total_contatos']           = $dados['total_contatos'] ?? 0;
            $dados['processados']              = $dados['processados'] ?? 0;
            $dados['sucesso']                  = $dados['sucesso'] ?? 0;
            $dados['falhas']                   = $dados['falhas'] ?? 0;

            $mailing = Mailing::create($dados);

            // Log da criação
            Log::info('Campanha criada', [
                'mailing_id' => $mailing->id,
                'nome' => $mailing->nome,
                'tipo_publico' => $mailing->tipo_publico,
                'prioridade' => $mailing->prioridade,
                'max_tentativas' => $mailing->max_tentativas,
                'velocidade_contatos_hora' => $mailing->velocidade_contatos_hora,
            ]);

            return response()->json([
                'message' => 'Campanha criada com sucesso',
                'mailing' => [
                    'id' => $mailing->id,
                    'uuid' => $mailing->uuid,
                    'nome' => $mailing->nome,
                    'descricao' => $mailing->descricao,
                    'tipo_publico' => $mailing->tipo_publico,
                    'status' => $mailing->status,
                    'script_id' => $mailing->script_id,
                    'prioridade' => $mailing->prioridade,
                    'max_tentativas' => $mailing->max_tentativas,
                    'velocidade_contatos_hora' => $mailing->velocidade_contatos_hora,
                    'intervalo_retry' => $mailing->intervalo_retry,
                    'data_inicio_agendado' => $mailing->data_inicio_agendado,
                    'data_fim_agendado' => $mailing->data_fim_agendado,
                    'created_at' => $mailing->created_at,
                ],
                'stats' => [
                    'total_contatos' => 0,
                    'processados' => 0,
                    'sucesso' => 0,
                    'falhas' => 0,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao criar campanha',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibe detalhes de um mailing
     * GET /api/mailings/{id}
     */
    public function show($id)
    {
        $mailing = Mailing::with(['script', 'queueJobs'])->find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        // Estatísticas detalhadas
        $stats = [
            'total_contatos' => $mailing->total_contatos,
            'processados'    => $mailing->processados,
            'sucesso'        => $mailing->sucesso,
            'falhas'         => $mailing->falhas,
            'pendentes'      => $mailing->total_contatos - $mailing->processados,
            'taxa_sucesso'   => $mailing->taxa_sucesso,
            'progresso'      => $mailing->progresso,
        ];

        return response()->json([
            'mailing' => $mailing,
            'stats'   => $stats,
        ]);
    }

    /**
     * Atualiza um mailing
     * PUT /api/mailings/{id}
     */
    public function update(Request $request, $id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        // Não permite editar se estiver ativo
        if ($mailing->isAtivo()) {
            return response()->json([
                'error' => 'Não é possível editar um mailing ativo. Pause-o primeiro.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'nome'                     => 'sometimes|required|string|max:255',
            'descricao'                => 'nullable|string',
            'velocidade_contatos_hora' => 'nullable|integer|min:1|max:1000',
            'prioridade'               => 'nullable|in:alto,normal,baixo',
            'max_tentativas'           => 'nullable|integer|min:1|max:10',
            'filtros'                  => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $mailing->update($request->all());
            $mailing->atualizado_por = $request->user_id ?? null;
            $mailing->save();

            return response()->json([
                'message' => 'Mailing atualizado com sucesso',
                'mailing' => $mailing->load('script'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao atualizar mailing',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove um mailing
     * DELETE /api/mailings/{id}
     */
    public function destroy($id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        if ($mailing->isAtivo()) {
            return response()->json([
                'error' => 'Não é possível excluir um mailing ativo',
            ], 400);
        }

        try {
            $mailing->delete();

            return response()->json([
                'message' => 'Mailing removido com sucesso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao remover mailing',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Importa CSV para um mailing com logging detalhado
     * POST /api/mailings/{id}/importar
     */
    public function importarCSV(Request $request, $id)
    {
        $mailing = Mailing::find($id);

        if (!$mailing) {
            return ApiResponseService::notFound('Mailing');
        }

        $validator = Validator::make($request->all(), [
            'arquivo' => 'required|file|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->toArray());
        }

        $arquivo          = $request->file('arquivo');
        $nomeArquivo      = $arquivo->getClientOriginalName();
        $inicioImportacao = microtime(true);

        // Validar MIME type real do arquivo
        $mimeType = $arquivo->getMimeType();
        $allowedMimes = [
            'text/csv', 'text/plain', 'application/csv', 'application/x-csv', 'text/x-csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-excel', // .xls
            'application/octet-stream', // fallback genérico
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            return ApiResponseService::validationError([
                'arquivo' => 'Tipo de arquivo inválido. Apenas CSV, TXT ou Excel (.xlsx) são permitidos.'
            ]);
        }

        Log::info("Iniciando importação de CSV", [
            'mailing_id'    => $mailing->id,
            'arquivo'       => $nomeArquivo,
            'tamanho_bytes' => $arquivo->getSize(),
            'mime_type'     => $mimeType,
        ]);

        try {
            // Verificar se é Excel (o frontend converte para CSV antes de enviar,
            // mas caso chegue XLSX direto via API, rejeitar com mensagem clara)
            $ext = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
            if (in_array($ext, ['xlsx', 'xls'])) {
                return ApiResponseService::validationError([
                    'arquivo' => 'Arquivos Excel devem ser convertidos para CSV antes do envio. Use a interface web que faz a conversão automaticamente.'
                ]);
            }

            // Processar arquivo do upload direto sem usar store()
            $handle = fopen($arquivo->getRealPath(), 'r');

            // Lê cabeçalho
            $cabecalho = fgetcsv($handle, 0, ',');

            if (! $cabecalho) {
                throw new \Exception('Arquivo CSV vazio ou inválido');
            }

            // Valida colunas obrigatórias
            $colunasObrigatorias = ['nome', 'telefone'];
            $colunasFaltando     = array_diff($colunasObrigatorias, $cabecalho);

            if (! empty($colunasFaltando)) {
                fclose($handle);
                throw new \Exception('Colunas obrigatórias faltando: ' . implode(', ', $colunasFaltando));
            }

            // Estatísticas
            $stats = [
                'total_linhas' => 0,
                'sucesso'      => 0,
                'erros'        => 0,
                'avisos'       => 0,
                'ignoradas'    => 0,
                'duplicadas'   => 0,
            ];

            // Buscar nome da credora da empresa logada
            $empresaCredora = null;
            if (app()->bound('empresa_id')) {
                $empresa = Empresa::withoutGlobalScopes()->find(app('empresa_id'));
                if ($empresa) {
                    $config = $empresa->configuracoes ?? [];
                    $empresaCredora = $config['nome_credora'] ?? $empresa->nome;
                }
            }

            DB::beginTransaction();

            $linhaNumero   = 1; // Linha 1 é o cabeçalho
            $batchContatos = [];
            $batchSize     = 100; // Insere em lotes de 100

            while (($linha = fgetcsv($handle, 0, ',')) !== false) {
                $linhaNumero++;
                $stats['total_linhas']++;

                $inicioLinha = microtime(true);

                // Ignora linhas vazias
                if (empty(array_filter($linha))) {
                    ImportLog::logSkipped(
                        $mailing->id,
                        $linhaNumero,
                        ['linha' => $linha],
                        'Linha vazia'
                    );
                    $stats['ignoradas']++;
                    continue;
                }

                // Combina cabeçalho com valores
                if (count($cabecalho) !== count($linha)) {
                    ImportLog::logError(
                        $mailing->id,
                        $linhaNumero,
                        ['linha' => $linha],
                        'Número de colunas não corresponde ao cabeçalho',
                        ['esperado' => count($cabecalho), 'recebido' => count($linha)]
                    );
                    $stats['erros']++;
                    continue;
                }

                $dados = array_combine($cabecalho, $linha);

                try {
                    // Validação de dados obrigatórios
                    $validacao = $this->validarDadosContato($dados);

                    if (! $validacao['valido']) {
                        ImportLog::logError(
                            $mailing->id,
                            $linhaNumero,
                            $dados,
                            'Erro de validação',
                            $validacao['erros']
                        );
                        $stats['erros']++;
                        continue;
                    }

                    // Formata telefone
                    $telefoneFormatado = $this->formatarTelefone($dados['telefone']);

                    // Verifica duplicação no mailing
                    $contatoExistente = Contato::where('telefone', $telefoneFormatado)
                        ->where('mailing_id', $mailing->id)
                        ->first();

                    if ($contatoExistente) {
                        ImportLog::logDuplicated(
                            $mailing->id,
                            $linhaNumero,
                            $dados,
                            $contatoExistente->id
                        );
                        $stats['duplicadas']++;
                        continue;
                    }

                    // Aplica filtros do mailing
                    $resultadoFiltro = $this->aplicarFiltrosComDetalhes($dados, $mailing->filtros);

                    if (! $resultadoFiltro['passou']) {
                        ImportLog::logSkipped(
                            $mailing->id,
                            $linhaNumero,
                            $dados,
                            'Filtrado: ' . $resultadoFiltro['motivo']
                        );
                        $stats['ignoradas']++;
                        continue;
                    }

                    // Cria contato
                    $cpf = $dados['cpf'] ?? null;
                    $dadosProcessados = [
                        'nome'                  => trim($dados['nome']),
                        'telefone'              => $telefoneFormatado,
                        'valor_debito'          => floatval($dados['valor_debito'] ?? 0),
                        'vencimento'            => isset($dados['vencimento']) && ! empty($dados['vencimento'])
                            ? date('Y-m-d', strtotime($dados['vencimento']))
                            : null,
                        'campanha'              => $dados['campanha'] ?? $mailing->nome,
                        'cpf'                   => $cpf,
                        'cpf_primeiros_digitos' => $cpf && ! empty($cpf) ? substr($cpf, 0, 3) : null,
                        'data_nascimento'       => isset($dados['data_nascimento']) && ! empty($dados['data_nascimento'])
                            ? date('Y-m-d', strtotime($dados['data_nascimento']))
                            : null,
                        'empresa_credora'       => $empresaCredora ?? $dados['empresa_credora'] ?? null,
                    ];

                    $contato = new Contato(array_merge($dadosProcessados, [
                        'mailing_id' => $mailing->id,
                        'status'     => 'pendente',
                    ]));

                    $contato->save();
                    $batchContatos[] = $contato->id;

                    // Log de sucesso
                    $tempoProcessamento = (microtime(true) - $inicioLinha) * 1000;
                    ImportLog::create([
                        'mailing_id'             => $mailing->id,
                        'arquivo_nome'           => $nomeArquivo,
                        'linha_numero'           => $linhaNumero,
                        'status'                 => ImportLog::STATUS_SUCCESS,
                        'dados_originais'        => $dados,
                        'dados_processados'      => $dadosProcessados,
                        'mensagem'               => 'Contato importado com sucesso',
                        'acao_tomada'            => ImportLog::ACAO_INSERTED,
                        'contato_id'             => $contato->id,
                        'tempo_processamento_ms' => round($tempoProcessamento, 2),
                        'ip_address'             => $request->ip(),
                        'usuario_id'             => null,
                    ]);

                    $stats['sucesso']++;

                    // Commit em lotes para performance
                    if (count($batchContatos) >= $batchSize) {
                        DB::commit();
                        DB::beginTransaction();
                        $batchContatos = [];

                        Log::info("Lote processado", [
                            'linhas_processadas' => $linhaNumero,
                            'sucesso'            => $stats['sucesso'],
                        ]);
                    }

                } catch (\Exception $e) {
                    $tempoProcessamento = (microtime(true) - $inicioLinha) * 1000;

                    ImportLog::create([
                        'mailing_id'             => $mailing->id,
                        'arquivo_nome'           => $nomeArquivo,
                        'linha_numero'           => $linhaNumero,
                        'status'                 => ImportLog::STATUS_ERROR,
                        'dados_originais'        => $dados,
                        'mensagem'               => 'Erro ao processar: ' . $e->getMessage(),
                        'erros_validacao'        => [
                            'exception' => get_class($e),
                            'message'   => $e->getMessage(),
                            'line'      => $e->getLine(),
                        ],
                        'acao_tomada'            => ImportLog::ACAO_REJECTED,
                        'tempo_processamento_ms' => round($tempoProcessamento, 2),
                        'ip_address'             => $request->ip(),
                        'usuario_id'             => null,
                    ]);

                    $stats['erros']++;

                    Log::error("Erro ao processar linha {$linhaNumero}", [
                        'dados' => $dados,
                        'erro'  => $e->getMessage(),
                    ]);
                }
            }

            fclose($handle);

            // Atualiza mailing com estatísticas
            $tempoTotal = microtime(true) - $inicioImportacao;

            $mailing->arquivo_csv_path         = $nomeArquivo;
            $mailing->total_contatos           = $stats['sucesso'];
            $mailing->status                   = $stats['sucesso'] > 0 ? 'pronto' : 'rascunho';
            $mailing->ultimo_arquivo_importado = $nomeArquivo;
            $mailing->ultima_importacao_em     = \Carbon\Carbon::now();
            $mailing->estatisticas_importacao  = array_merge($stats, [
                'tempo_total_segundos' => round($tempoTotal, 2),
                'linhas_por_segundo'   => $stats['total_linhas'] > 0 ? round($stats['total_linhas'] / $tempoTotal, 2) : 0,
            ]);
            $mailing->save();

            DB::commit();

            // Log successful import
            ApiResponseService::logAction('import_csv', 'Mailing', $mailing->id, null, [
                'arquivo' => $nomeArquivo,
                'total_contatos' => $stats['sucesso'],
                'erros' => $stats['erros'],
            ]);

            Log::info("Importação concluída com sucesso", [
                'mailing_id'   => $mailing->id,
                'arquivo'      => $nomeArquivo,
                'estatisticas' => $stats,
                'tempo_total'  => round($tempoTotal, 2) . 's',
            ]);

            return ApiResponseService::success([
                'arquivo'      => $nomeArquivo,
                'estatisticas' => $mailing->estatisticas_importacao,
                'mailing'      => $mailing->fresh()->load('script'),
                'detalhes_url' => '/api/mailings/' . $mailing->id . '/import-logs',
            ], 'Importação concluída com sucesso', 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Erro crítico na importação", [
                'mailing_id' => $mailing->id,
                'arquivo'    => $nomeArquivo ?? 'desconhecido',
                'erro'       => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return ApiResponseService::serverError(
                'Erro ao importar CSV. Entre em contato com o suporte.',
                debug: config('app.debug'),
                exception: $e
            );
        }
    }

    /**
     * Valida dados de contato usando ValidationService centralizado
     */
    private function validarDadosContato(array $dados): array
    {
        $erros = [];

        // Validar nome (obrigatório, 2-255 caracteres)
        $nome = ValidationService::validateString($dados['nome'] ?? null, 2, 255);
        if (!$nome) {
            $erros['nome'] = 'Nome obrigatório (mínimo 2 caracteres)';
        }

        // Validar telefone (obrigatório, formato brasileiro)
        $telefone = ValidationService::validatePhone($dados['telefone'] ?? null);
        if (!$telefone) {
            $erros['telefone'] = 'Telefone em formato inválido (deve ter DDD + número)';
        }

        // Validar CPF (opcional, mas se fornecido deve ser válido)
        if (isset($dados['cpf']) && !empty($dados['cpf'])) {
            $cpf = ValidationService::validateCpf($dados['cpf']);
            if (!$cpf) {
                $erros['cpf'] = 'CPF inválido (deve ter 11 dígitos)';
            }
        }

        // Validar valor de débito (opcional, mas se fornecido deve ser positivo)
        if (isset($dados['valor_debito']) && !empty($dados['valor_debito'])) {
            $valor = ValidationService::validateNumericRange($dados['valor_debito'], 0, PHP_INT_MAX);
            if ($valor === null) {
                $erros['valor_debito'] = 'Valor deve ser numérico e não negativo';
            }
        }

        // Validar vencimento (opcional, mas se fornecido deve ser data válida)
        if (isset($dados['vencimento']) && !empty($dados['vencimento'])) {
            $data = ValidationService::validateDate($dados['vencimento']);
            if (!$data) {
                $erros['vencimento'] = 'Data de vencimento em formato inválido (use DD/MM/YYYY ou YYYY-MM-DD)';
            }
        }

        // Validar data de nascimento (opcional)
        if (isset($dados['data_nascimento']) && !empty($dados['data_nascimento'])) {
            $data = ValidationService::validateDate($dados['data_nascimento']);
            if (!$data) {
                $erros['data_nascimento'] = 'Data de nascimento em formato inválido';
            }
        }

        return [
            'valido' => empty($erros),
            'erros'  => $erros,
        ];
    }

    /**
     * Aplica filtros com detalhes do motivo
     */
    private function aplicarFiltrosComDetalhes(array $dados, ?array $filtros): array
    {
        if (empty($filtros)) {
            return ['passou' => true, 'motivo' => null];
        }

        // Filtro por DDD
        if (isset($filtros['ddd']) && ! empty($filtros['ddd'])) {
            $telefone = preg_replace('/[^0-9]/', '', $dados['telefone']);
            $ddd      = substr($telefone, 0, 2);

            if (! in_array($ddd, $filtros['ddd'])) {
                return [
                    'passou' => false,
                    'motivo' => "DDD {$ddd} não está na lista permitida",
                ];
            }
        }

        // Filtro por valor mínimo
        if (isset($filtros['valor_min'])) {
            $valor = floatval($dados['valor_debito'] ?? 0);
            if ($valor < $filtros['valor_min']) {
                return [
                    'passou' => false,
                    'motivo' => "Valor R$ {$valor} menor que mínimo R$ {$filtros['valor_min']}",
                ];
            }
        }

        // Filtro por valor máximo
        if (isset($filtros['valor_max'])) {
            $valor = floatval($dados['valor_debito'] ?? 0);
            if ($valor > $filtros['valor_max']) {
                return [
                    'passou' => false,
                    'motivo' => "Valor R$ {$valor} maior que máximo R$ {$filtros['valor_max']}",
                ];
            }
        }

        return ['passou' => true, 'motivo' => null];
    }

    /**
     * Ativa um mailing (inicia processamento)
     * POST /api/mailings/{id}/ativar
     */
    public function ativar($id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        if (!in_array($mailing->status, ['pronto', 'rascunho'])) {
            return response()->json([
                'error' => 'Apenas mailings com status "pronto" ou "rascunho" podem ser ativados',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Cria jobs para todos os contatos
            $contatos = Contato::where('mailing_id', $mailing->id)
                ->where('status', 'pendente')
                ->get();

            foreach ($contatos as $contato) {
                QueueJob::create([
                    'mailing_id'        => $mailing->id,
                    'contato_id'        => $contato->id,
                    'empresa_id'        => $mailing->empresa_id,
                    'status'            => 'pending',
                    'proxima_tentativa' => \Carbon\Carbon::now(),
                ]);
            }

            // Ativa o mailing
            $mailing->ativar();

            // Log
            ScriptLog::mailingIniciado($mailing, request()->user_id ?? null, request()->ip());

            DB::commit();

            return response()->json([
                'message'      => 'Mailing ativado com sucesso',
                'jobs_criados' => $contatos->count(),
                'mailing'      => $mailing,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => 'Erro ao ativar mailing',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pausa um mailing
     * POST /api/mailings/{id}/pausar
     */
    public function pausar($id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        try {
            $mailing->pausar();

            // Log
            ScriptLog::mailingPausado($mailing, request()->user_id ?? null, request()->ip());

            return response()->json([
                'message' => 'Mailing pausado com sucesso',
                'mailing' => $mailing,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao pausar mailing',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retoma um mailing pausado e cria jobs para contatos pendentes
     * POST /api/mailings/{id}/retomar
     */
    public function retomar($id)
    {
        $mailing = Mailing::findOrFail($id);

        if ($mailing->status !== 'pausado') {
            return response()->json([
                'error' => 'Campanha deve estar no status "pausado"',
            ], 400);
        }

        try {
            \Log::info("🔄 [RETOMAR] Iniciando retomada da campanha {$mailing->id} ({$mailing->nome})");

            // Buscar contatos que ainda não foram completamente processados
            // (sem job OU com job pending que não foi processado)
            $contatosSemJob = $mailing->contatos()
                ->where('status', 'importado')
                ->whereDoesntHave('queueJobs', function ($q) {
                    $q->whereIn('status', ['completed', 'failed']);
                })
                ->get();

            \Log::info("🔄 [RETOMAR] Encontrados {$contatosSemJob->count()} contatos para reprocessar");

            // Criar QueueJobs para contatos que ainda não têm jobs ou têm jobs pendentes
            $jobsCriados = 0;
            foreach ($contatosSemJob as $contato) {
                // Verificar se já tem job pending para este contato
                $jobExistente = QueueJob::where('mailing_id', $mailing->id)
                    ->where('contato_id', $contato->id)
                    ->where('status', 'pending')
                    ->first();

                if (! $jobExistente) {
                    QueueJob::create([
                        'mailing_id'        => $mailing->id,
                        'contato_id'        => $contato->id,
                        'empresa_id'        => $mailing->empresa_id,
                        'status'            => 'pending',
                        'tentativas'        => 0,
                        'proxima_tentativa' => \Carbon\Carbon::now(),
                    ]);
                    $jobsCriados++;
                    \Log::debug("✅ [RETOMAR] Job criado para contato {$contato->id} ({$contato->nome})");
                }
            }

            \Log::info("✅ [RETOMAR] {$jobsCriados} jobs criados para processamento");

            // Atualizar status do mailing para 'ativo'
            $mailing->retomar();
            \Log::info("✅ [RETOMAR] Campanha {$mailing->id} retomada com status ATIVO");

            $totalNaFila = $mailing->queueJobs()->where('status', 'pending')->count();
            \Log::info("✅ [RETOMAR] Campanha {$mailing->id} tem {$totalNaFila} jobs pendentes na fila");

            return response()->json([
                'message'       => 'Campanha retomada com sucesso',
                'mailing'       => $mailing,
                'jobs_criados'  => $jobsCriados,
                'total_na_fila' => $totalNaFila,
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ [RETOMAR] Erro ao retomar campanha {$id}: " . $e->getMessage());
            return response()->json([
                'error'   => 'Erro ao retomar mailing',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancela um mailing
     * POST /api/mailings/{id}/cancelar
     */
    public function cancelar($id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        try {
            $mailing->cancelar();

            return response()->json([
                'message' => 'Mailing cancelado com sucesso',
                'mailing' => $mailing,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao cancelar mailing',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry de jobs falhados
     * POST /api/mailings/{id}/retry-falhas
     */
    public function retryFalhas($id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        try {
            $jobs = QueueJob::porMailing($mailing->id)
                ->falhos()
                ->get();

            $resetados = 0;
            foreach ($jobs as $job) {
                $job->resetarParaRetry();
                $resetados++;
            }

            return response()->json([
                'message' => 'Jobs resetados para retry',
                'total'   => $resetados,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Erro ao fazer retry',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retorna logs de importação de um mailing
     * GET /api/mailings/{id}/import-logs
     */
    public function getImportLogs(Request $request, $id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        $query = ImportLog::where('mailing_id', $id)
            ->orderBy('linha_numero', 'asc');

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('arquivo')) {
            $query->where('arquivo_nome', $request->arquivo);
        }

        if ($request->has('acao_tomada')) {
            $query->where('acao_tomada', $request->acao_tomada);
        }

        // Busca por telefone ou nome (parametrizado para evitar SQL injection)
        if ($request->has('busca')) {
            $busca = $request->busca;
            $query->where(function ($q) use ($busca) {
                $q->whereRaw("JSON_EXTRACT(dados_originais, '$.nome') LIKE ?", ['%' . $busca . '%'])
                    ->orWhereRaw("JSON_EXTRACT(dados_originais, '$.telefone') LIKE ?", ['%' . $busca . '%']);
            });
        }

        $perPage = $request->get('per_page', 50);
        $logs    = $query->paginate($perPage);

        return response()->json($logs);
    }

    /**
     * Retorna estatísticas detalhadas de importação
     * GET /api/mailings/{id}/import-stats
     */
    public function getImportStats($id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        $stats          = ImportLog::estatisticasPorMailing($id);
        $errosAgrupados = ImportLog::errosAgrupados($id);

        // Estatísticas por arquivo
        $porArquivo = ImportLog::where('mailing_id', $id)
            ->select('arquivo_nome')
            ->distinct()
            ->get()
            ->map(function ($item) use ($id) {
                return [
                    'arquivo'      => $item->arquivo_nome,
                    'estatisticas' => ImportLog::estatisticasPorMailing($id, $item->arquivo_nome),
                ];
            });

        return response()->json([
            'mailing'             => $mailing->only(['id', 'nome', 'status', 'ultimo_arquivo_importado', 'ultima_importacao_em', 'estatisticas_importacao']),
            'estatisticas_gerais' => $stats,
            'erros_agrupados'     => $errosAgrupados,
            'por_arquivo'         => $porArquivo,
            'distribuicao_status' => [
                'sucesso'   => ImportLog::where('mailing_id', $id)->success()->count(),
                'erros'     => ImportLog::where('mailing_id', $id)->errors()->count(),
                'avisos'    => ImportLog::where('mailing_id', $id)->warnings()->count(),
                'ignoradas' => ImportLog::where('mailing_id', $id)->skipped()->count(),
            ],
        ]);
    }

    /**
     * Retorna resumo rápido do status de importação (para polling)
     * GET /api/mailings/{id}/import-status
     */
    public function getImportStatus($id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        return response()->json([
            'mailing_id'        => $mailing->id,
            'nome'              => $mailing->nome,
            'status'            => $mailing->status,
            'ultimo_arquivo'    => $mailing->ultimo_arquivo_importado,
            'ultima_importacao' => $mailing->ultima_importacao_em,
            'estatisticas'      => $mailing->estatisticas_importacao,
            'total_contatos'    => $mailing->total_contatos,
        ]);
    }

    /**
     * Exporta logs de importação em CSV
     * GET /api/mailings/{id}/export-import-logs
     */
    public function exportImportLogs(Request $request, $id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        $query = ImportLog::where('mailing_id', $id)
            ->orderBy('linha_numero', 'asc');

        // Filtros opcionais
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('arquivo')) {
            $query->where('arquivo_nome', $request->arquivo);
        }

        $logs = $query->get();

        // Gera CSV
        $csvData = "Linha,Status,Nome,Telefone,Valor,Mensagem,Ação,Tempo (ms)\n";

        foreach ($logs as $log) {
            $dados    = $log->dados_originais ?? [];
            $csvData .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s\n",
                $log->linha_numero,
                $log->status,
                $dados['nome'] ?? '',
                $dados['telefone'] ?? '',
                $dados['valor_debito'] ?? '',
                str_replace(["\n", "\r", ','], [' ', ' ', ';'], $log->mensagem ?? ''),
                $log->acao_tomada ?? '',
                $log->tempo_processamento_ms ?? ''
            );
        }

        $filename = "import_logs_{$mailing->id}_" . date('Y-m-d_His') . ".csv";

        return response($csvData, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Gera relatório detalhado de importação
     * GET /api/mailings/{id}/import-report
     */
    public function getImportReport($id)
    {
        $mailing = Mailing::with('script')->find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        // Estatísticas gerais
        $stats          = ImportLog::estatisticasPorMailing($id);
        $errosAgrupados = ImportLog::errosAgrupados($id);

        // Top 10 erros mais comuns
        $topErros = $errosAgrupados->take(10);

        // Distribuição de tempo de processamento
        $temposProcessamento = ImportLog::where('mailing_id', $id)
            ->whereNotNull('tempo_processamento_ms')
            ->select('tempo_processamento_ms')
            ->get()
            ->pluck('tempo_processamento_ms');

        $distribuicaoTempo = [
            'minimo'  => $temposProcessamento->min(),
            'maximo'  => $temposProcessamento->max(),
            'media'   => round($temposProcessamento->avg(), 2),
            'mediana' => $temposProcessamento->median(),
        ];

        // Amostras de linhas processadas
        $amostras = [
            'sucesso'    => ImportLog::where('mailing_id', $id)
                ->success()
                ->take(5)
                ->get(['linha_numero', 'dados_originais', 'mensagem']),
            'erros'      => ImportLog::where('mailing_id', $id)
                ->errors()
                ->take(5)
                ->get(['linha_numero', 'dados_originais', 'mensagem', 'erros_validacao']),
            'duplicadas' => ImportLog::where('mailing_id', $id)
                ->where('acao_tomada', ImportLog::ACAO_DUPLICATED)
                ->take(5)
                ->get(['linha_numero', 'dados_originais', 'mensagem']),
        ];

        // Resumo por DDD (se disponível)
        $resumoPorDDD = ImportLog::where('mailing_id', $id)
            ->success()
            ->get()
            ->map(function ($log) {
                $telefone = $log->dados_processados['telefone'] ?? '';
                $ddd      = substr(preg_replace('/[^0-9]/', '', $telefone), 0, 2);
                return $ddd;
            })
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10);

        // Performance metrics
        $performance = [
            'total_tempo_segundos'     => $mailing->estatisticas_importacao['tempo_total_segundos'] ?? 0,
            'linhas_por_segundo'       => $mailing->estatisticas_importacao['linhas_por_segundo'] ?? 0,
            'tempo_medio_por_linha_ms' => $stats['tempo_medio_ms'],
            'throughput'               => $stats['total_linhas'] > 0
                ? round($stats['total_linhas'] / ($mailing->estatisticas_importacao['tempo_total_segundos'] ?? 1), 2)
                : 0,
        ];

        // Recomendações baseadas nos resultados
        $recomendacoes = $this->gerarRecomendacoes($stats, $errosAgrupados, $mailing);

        return response()->json([
            'mailing'            => [
                'id'              => $mailing->id,
                'nome'            => $mailing->nome,
                'status'          => $mailing->status,
                'script'          => $mailing->script->nome ?? null,
                'arquivo'         => $mailing->ultimo_arquivo_importado,
                'data_importacao' => $mailing->ultima_importacao_em,
            ],
            'estatisticas'       => $stats,
            'performance'        => $performance,
            'distribuicao_tempo' => $distribuicaoTempo,
            'top_erros'          => $topErros,
            'resumo_ddd'         => $resumoPorDDD,
            'amostras'           => $amostras,
            'recomendacoes'      => $recomendacoes,
        ]);
    }

    /**
     * Gera recomendações baseadas nos resultados da importação
     */
    private function gerarRecomendacoes(array $stats, $errosAgrupados, $mailing): array
    {
        $recomendacoes = [];

        // Taxa de sucesso baixa
        $taxaSucesso = $stats['total_linhas'] > 0
            ? ($stats['sucesso'] / $stats['total_linhas']) * 100
            : 0;

        if ($taxaSucesso < 50) {
            $recomendacoes[] = [
                'tipo'     => 'critico',
                'mensagem' => 'Taxa de sucesso muito baixa (' . round($taxaSucesso, 1) . '%)',
                'acao'     => 'Verifique o formato do arquivo CSV e os filtros aplicados',
            ];
        } elseif ($taxaSucesso < 80) {
            $recomendacoes[] = [
                'tipo'     => 'alerta',
                'mensagem' => 'Taxa de sucesso abaixo do ideal (' . round($taxaSucesso, 1) . '%)',
                'acao'     => 'Revise os erros mais comuns e ajuste os dados de entrada',
            ];
        }

        // Muitos duplicados
        if ($stats['duplicadas'] > ($stats['total_linhas'] * 0.2)) {
            $recomendacoes[] = [
                'tipo'     => 'alerta',
                'mensagem' => 'Alto número de registros duplicados (' . $stats['duplicadas'] . ')',
                'acao'     => 'Remova duplicatas do arquivo CSV antes de importar',
            ];
        }

        // Erros de validação comuns
        if ($errosAgrupados->count() > 0) {
            $erroMaisComum = $errosAgrupados->first();
            if ($erroMaisComum['ocorrencias'] > 10) {
                $recomendacoes[] = [
                    'tipo'     => 'info',
                    'mensagem' => 'Erro comum: ' . $erroMaisComum['mensagem'],
                    'acao'     => 'Corrija este erro em ' . $erroMaisComum['ocorrencias'] . ' linhas e reimporte',
                ];
            }
        }

        // Performance
        if ($stats['tempo_medio_ms'] > 100) {
            $recomendacoes[] = [
                'tipo'     => 'info',
                'mensagem' => 'Processamento lento detectado',
                'acao'     => 'Considere otimizar validações ou importar em horários de menor carga',
            ];
        }

        // Nenhum contato importado
        if ($stats['sucesso'] === 0) {
            $recomendacoes[] = [
                'tipo'     => 'critico',
                'mensagem' => 'Nenhum contato foi importado com sucesso',
                'acao'     => 'Verifique os filtros do mailing e o formato dos dados no CSV',
            ];
        }

        // Sucesso total
        if ($taxaSucesso >= 95 && $stats['sucesso'] > 0) {
            $recomendacoes[] = [
                'tipo'     => 'sucesso',
                'mensagem' => 'Importação realizada com excelência!',
                'acao'     => 'Mailing pronto para ativação',
            ];
        }

        return $recomendacoes;
    }

    /**
     * Limpa logs de importação antigos
     * DELETE /api/mailings/{id}/import-logs
     */
    public function clearImportLogs(Request $request, $id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        // Permite especificar arquivo específico
        $query = ImportLog::where('mailing_id', $id);

        if ($request->has('arquivo')) {
            $query->where('arquivo_nome', $request->arquivo);
        }

        $count = $query->count();
        $query->delete();

        Log::info("Logs de importação removidos", [
            'mailing_id'     => $id,
            'arquivo'        => $request->arquivo ?? 'todos',
            'logs_removidos' => $count,
            'usuario_id'     => auth()->id(),
        ]);

        return response()->json([
            'message'        => 'Logs removidos com sucesso',
            'total_removido' => $count,
        ]);
    }

    /**
     * Método legado: importar CSV sem mailing
     * @deprecated Use importarCSV com mailing_id
     */
    public function importar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'arquivo' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $arquivo = $request->file('arquivo');
        $handle  = fopen($arquivo->path(), 'r');

        $cabecalho = fgetcsv($handle, 1000, ',');

        $contatos = [];
        $erros    = [];

        while (($linha = fgetcsv($handle, 1000, ',')) !== false) {
            $dados = array_combine($cabecalho, $linha);

            try {
                $contato = new Contato([
                    'nome'         => $dados['nome'],
                    'telefone'     => $this->formatarTelefone($dados['telefone']),
                    'valor_debito' => floatval($dados['valor_debito']),
                    'vencimento'   => date('Y-m-d', strtotime($dados['vencimento'])),
                    'campanha'     => $dados['campanha'],
                ]);

                $contato->save();
                $contatos[] = $contato;
            } catch (\Exception $e) {
                $erros[] = "Erro na linha: " . implode(',', $linha) . " - " . $e->getMessage();
            }
        }

        fclose($handle);

        return response()->json([
            'importados' => count($contatos),
            'erros'      => $erros,
        ]);
    }

    public function enviarParaFila(Request $request)
    {
        $query = Contato::where('status', 'pendente')
            ->where('tentativas', '<', 3);

        if ($request->has('campanha')) {
            $query->where('campanha', $request->campanha);
        }

        $limit    = $request->input('limit', 50);
        $contatos = $query->limit($limit)->get();

        $enviados = 0;

        foreach ($contatos as $contato) {
            try {
                $payload = [
                    'id_contato'   => $contato->id,
                    'nome'         => $contato->nome,
                    'telefone'     => $contato->telefone,
                    'campanha'     => $contato->campanha,
                    'valor_debito' => $contato->valor_debito,
                    'vencimento'   => $contato->vencimento->format('Y-m-d'),
                ];

                $client   = new \GuzzleHttp\Client();
                $response = $client->post('http://n8n:5678/webhook/ligacao', [
                    'json' => $payload,
                ]);

                $contato->status            = 'em_processamento';
                $contato->tentativas       += 1;
                $contato->ultima_tentativa  = \Carbon\Carbon::now();
                $contato->save();

                $enviados++;

            } catch (\Exception $e) {
                Log::error("Erro ao enviar contato {$contato->id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'enviados' => $enviados,
            'total'    => $contatos->count(),
        ]);
    }

    /**
     * Formata telefone para padrão internacional (+55...)
     * Usa ValidationService para garantir formato válido
     */
    private function formatarTelefone($telefone): ?string
    {
        // Valida usando ValidationService
        $telefoneLimpo = ValidationService::validatePhone($telefone);

        if (!$telefoneLimpo) {
            return null;
        }

        // Adiciona código do Brasil se não tiver (máximo 11 dígitos = com DDD)
        if (strlen($telefoneLimpo) <= 11) {
            $telefoneLimpo = '55' . $telefoneLimpo;
        }

        return '+' . $telefoneLimpo;
    }

    /**
     * Aplica filtros do mailing nos dados
     */
    private function aplicarFiltros(array $dados, ?array $filtros): bool
    {
        if (empty($filtros)) {
            return true;
        }

        // Filtro por DDD
        if (isset($filtros['ddd']) && ! empty($filtros['ddd'])) {
            $telefone = preg_replace('/[^0-9]/', '', $dados['telefone']);
            $ddd      = substr($telefone, 0, 2);

            if (! in_array($ddd, $filtros['ddd'])) {
                return false;
            }
        }

        // Filtro por valor mínimo
        if (isset($filtros['valor_min'])) {
            $valor = floatval($dados['valor_debito'] ?? 0);
            if ($valor < $filtros['valor_min']) {
                return false;
            }
        }

        // Filtro por valor máximo
        if (isset($filtros['valor_max'])) {
            $valor = floatval($dados['valor_debito'] ?? 0);
            if ($valor > $filtros['valor_max']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Lista contatos de um mailing com filtros e paginação
     * GET /api/mailings/{id}/contatos
     */
    public function contatosDoMailing(Request $request, $id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        $query = $mailing->contatos();

        // Filtro por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Busca por nome ou telefone
        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nome', 'like', "%$buscar%")
                    ->orWhere('telefone', 'like', "%$buscar%")
                    ->orWhere('cpf_primeiros_digitos', 'like', "%$buscar%");
            });
        }

        // Ordenação
        $ordenar_por = $request->get('ordenar_por', 'created_at');
        $ordenacao   = $request->get('ordenacao', 'desc');

        $query->orderBy($ordenar_por, $ordenacao);

        // Paginação
        $perPage  = $request->get('per_page', 10);
        $contatos = $query->paginate($perPage);

        return response()->json($contatos);
    }

    /**
     * Retorna estatísticas dos contatos de um mailing por status
     * GET /api/mailings/{id}/contatos/stats
     */
    public function contatosStats($id)
    {
        $mailing = Mailing::find($id);

        if (! $mailing) {
            return response()->json(['error' => 'Mailing não encontrado'], 404);
        }

        $stats = $mailing->contatos()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return response()->json([
            'mailing_id'       => $mailing->id,
            'total_contatos'   => $mailing->total_contatos,
            'stats_por_status' => $stats,
            'resumo'           => [
                'importado'          => $stats->get('importado', ['total' => 0])->total ?? 0,
                'enviado_a_discagem' => $stats->get('enviado_a_discagem', ['total' => 0])->total ?? 0,
                'em_ligacao'         => $stats->get('em_ligacao', ['total' => 0])->total ?? 0,
                'finalizado'         => $stats->get('finalizado', ['total' => 0])->total ?? 0,
                'retentar'           => $stats->get('retentar', ['total' => 0])->total ?? 0,
            ],
        ]);
    }

    /**
     * Ativa campanha e cria jobs de discagem
     * POST /api/filas_campanha/{id}/ativar
     */
    public function ativar2($id)
    {
        $mailing = Mailing::findOrFail($id);

        if ($mailing->status !== 'pronto') {
            return response()->json([
                'error' => 'Campanha deve estar no status "pronto"',
            ], 400);
        }

        // Verificar se tem contatos
        $totalContatos = $mailing->contatos()->count();
        if ($totalContatos === 0) {
            return response()->json([
                'error' => 'Campanha não tem contatos importados',
            ], 400);
        }

        // Criar QueueJobs para contatos que ainda não foram processados
        $contatosSemJob = $mailing->contatos()
            ->whereDoesntHave('queueJobs')
            ->where('status', 'importado')
            ->get();

        foreach ($contatosSemJob as $contato) {
            QueueJob::create([
                'mailing_id'        => $mailing->id,
                'contato_id'        => $contato->id,
                'empresa_id'        => $mailing->empresa_id,
                'status'            => 'pending',
                'tentativas'        => 0,
                'proxima_tentativa' => \Carbon\Carbon::now(),
            ]);
        }

        // Atualizar status do mailing
        $mailing->ativar();

        return response()->json([
            'message'       => 'Campanha ativada',
            'mailing'       => $mailing,
            'jobs_criados'  => $contatosSemJob->count(),
            'total_na_fila' => $mailing->queueJobs()->pendentes()->count(),
        ]);
    }

    /**
     * Retorna estatísticas de ligações em tempo real
     * GET /api/filas_campanha/{id}/ligacoes/stats
     */
    public function getLigacoesStats($id)
    {
        $mailing = Mailing::findOrFail($id);

        // Estatísticas de QueueJobs
        $jobsStats = QueueJob::where('mailing_id', $id)
            ->selectRaw('
                status,
                COUNT(*) as total
            ')
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        // Estatísticas de Ligações
        $ligacoesStats = Ligacao::whereHas('contato', function ($q) use ($id) {
            $q->where('mailing_id', $id);
        })
            ->selectRaw('
                status,
                COUNT(*) as total,
                AVG(duracao) as duracao_media
            ')
            ->groupBy('status')
            ->get();

        // Contatos por status
        $contatosStats = Contato::where('mailing_id', $id)
            ->selectRaw('
                status,
                COUNT(*) as total
            ')
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        return response()->json([
            'mailing'   => $mailing,
            'queue'     => [
                'pending'    => $jobsStats['pending'] ?? 0,
                'processing' => $jobsStats['processing'] ?? 0,
                'completed'  => $jobsStats['completed'] ?? 0,
                'failed'     => $jobsStats['failed'] ?? 0,
            ],
            'ligacoes'  => $ligacoesStats,
            'contatos'  => [
                'importado'  => $contatosStats['importado'] ?? 0,
                'em_ligacao' => $contatosStats['em_ligacao'] ?? 0,
                'finalizado' => $contatosStats['finalizado'] ?? 0,
                'falhou'     => $contatosStats['falhou'] ?? 0,
            ],
            'progresso' => [
                'total'        => $mailing->total_contatos,
                'processados'  => $mailing->processados,
                'sucesso'      => $mailing->sucesso,
                'falhas'       => $mailing->falhas,
                'taxa_sucesso' => $mailing->taxa_sucesso,
                'percentual'   => $mailing->progresso,
            ],
        ]);
    }

    /**
     * Lista ligações de uma campanha
     * GET /api/filas_campanha/{id}/ligacoes
     */
    public function getLigacoes($id, Request $request)
    {
        $mailing = Mailing::findOrFail($id);

        $status = $request->input('status');

        $query = Ligacao::with(['contato'])
            ->whereHas('contato', function ($q) use ($id) {
                $q->where('mailing_id', $id);
            })
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        $ligacoes = $query->paginate(50);

        return response()->json($ligacoes);
    }

    /**
     * Lista contatos na fila de ligações de todas as camapnhas
     */
    public function listarContatosNaFila(Request $request)
    {
        // Determinar quais status procurar
        $statusFilter = $request->has('status') && ! empty($request->status)
            ? [$request->status]
            : ['pending', 'processing'];

        $query = Contato::whereHas('queueJobs', function ($q) use ($statusFilter) {
            $q->whereIn('status', $statusFilter);
        })->with(['mailing', 'queueJobs' => function ($q) use ($statusFilter) {
            $q->whereIn('status', $statusFilter);
        }]);

        // Filtros
        if ($request->has('mailing_id')) {
            $query->where('mailing_id', $request->mailing_id);
        }

        // Filtro por busca (nome ou telefone)
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'LIKE', "%{$search}%")
                    ->orWhere('telefone', 'LIKE', "%{$search}%");
            });
        }

        // Filtro por data inicial
        if ($request->has('date_start') && ! empty($request->date_start)) {
            $query->where('created_at', '>=', $request->date_start . ' 00:00:00');
        }

        // Filtro por data final
        if ($request->has('date_end') && ! empty($request->date_end)) {
            $query->where('created_at', '<=', $request->date_end . ' 23:59:59');
        }

        // Paginação
        $perPage  = $request->get('per_page', 20);
        $contatos = $query->paginate($perPage);

        // Calcular estatísticas gerais
        $stats = [
            'total'      => QueueJob::count(),
            'pending'    => QueueJob::where('status', 'pending')->count(),
            'processing' => QueueJob::where('status', 'processing')->count(),
            'completed'  => QueueJob::where('status', 'completed')->count(),
            'failed'     => QueueJob::where('status', 'failed')->count(),
        ];

        return response()->json([
            'data'       => $contatos->items(),
            'stats'      => $stats,
            'pagination' => [
                'total'        => $contatos->total(),
                'per_page'     => $contatos->perPage(),
                'current_page' => $contatos->currentPage(),
                'last_page'    => $contatos->lastPage(),
            ],
        ]);
    }

    /**
     * Retorna estatísticas de ligações em tempo real para todas as campanhas
     * GET /api/filas_campanha/ligacoes/stats
     */
    public function getAllLigacoesStats(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 20); // Padrão 20 itens por página
            $page    = $request->get('page', 1);      // Página atual

            Log::info('📞 [LIGACOES-STATS] Buscando estatísticas de ligações');

            // Estatísticas de QueueJobs (não paginado - estatísticas gerais)
            $jobsStats = QueueJob::selectRaw('
                status,
                COUNT(*) as total
            ')
                ->groupBy('status')
                ->get()
                ->pluck('total', 'status');

            // Estatísticas de Ligações por status (não paginado - estatísticas gerais)
            $ligacoesStats = Ligacao::selectRaw('
                status,
                COUNT(*) as total,
                AVG(duracao) as duracao_media
            ')
                ->groupBy('status')
                ->get();

            // Ligações paginadas com detalhes (incluindo dados do Retell)
            $ligacoesPaginadas = Ligacao::with(['contato.mailing', 'callHistory']) // Carrega relacionamentos
                ->orderBy('created_at', 'desc')                                      // Ordena por data de criação (mais recentes primeiro)
                ->paginate($perPage);

            Log::info('✅ [LIGACOES-STATS] Ligações recuperadas: ' . $ligacoesPaginadas->count());

        // Contatos por status (não paginado - estatísticas gerais)
        $contatosStats = Contato::selectRaw('
            status,
            COUNT(*) as total
        ')
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        // Estatísticas gerais de todos os mailings combinados
        $totalMailings    = Mailing::count();
        $totalContatos    = Mailing::sum('total_contatos');
        $totalProcessados = Mailing::sum('processados');
        $totalSucesso     = Mailing::sum('sucesso');
        $totalFalhas      = Mailing::sum('falhas');

        // Calcular taxa de sucesso geral
        $taxaSucessoGeral = $totalProcessados > 0
            ? round(($totalSucesso / $totalProcessados) * 100, 2)
            : 0;

        // Calcular percentual de progresso geral
        $percentualGeral = $totalContatos > 0
            ? round(($totalProcessados / $totalContatos) * 100, 2)
            : 0;

            return response()->json([
                'success' => true,
                // Dados paginados
                'ligacoes_paginadas'  => [
                    'data'       => $ligacoesPaginadas->items(),
                    'pagination' => [
                        'current_page' => $ligacoesPaginadas->currentPage(),
                        'last_page'    => $ligacoesPaginadas->lastPage(),
                        'per_page'     => $ligacoesPaginadas->perPage(),
                        'total'        => $ligacoesPaginadas->total(),
                        'from'         => $ligacoesPaginadas->firstItem(),
                        'to'           => $ligacoesPaginadas->lastItem(),
                        'links'        => $ligacoesPaginadas->linkCollection(), // Links de paginação
                    ],
                ],

                // Estatísticas gerais (não paginadas)
                'estatisticas_gerais' => [
                    'queue'               => [
                        'pending'    => $jobsStats['pending'] ?? 0,
                        'processing' => $jobsStats['processing'] ?? 0,
                        'completed'  => $jobsStats['completed'] ?? 0,
                        'failed'     => $jobsStats['failed'] ?? 0,
                    ],
                    'ligacoes_por_status' => $ligacoesStats,
                    'contatos_por_status' => [
                        'importado'  => $contatosStats['importado'] ?? 0,
                        'em_ligacao' => $contatosStats['em_ligacao'] ?? 0,
                        'finalizado' => $contatosStats['finalizado'] ?? 0,
                        'falhou'     => $contatosStats['falhou'] ?? 0,
                    ],
                    'progresso'           => [
                        'total_mailings' => $totalMailings,
                        'total_contatos' => $totalContatos,
                        'processados'    => $totalProcessados,
                        'sucesso'        => $totalSucesso,
                        'falhas'         => $totalFalhas,
                        'taxa_sucesso'   => $taxaSucessoGeral,
                        'percentual'     => $percentualGeral,
                    ],
                    'resumo'              => [
                        'total_ligacoes'       => $ligacoesStats->sum('total'),
                        'duracao_media_geral'  => $ligacoesStats->avg('duracao_media'),
                        'total_contatos_geral' => $contatosStats->sum(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Erro ao buscar estatísticas de ligações: ' . $e->getMessage());
            Log::error('Stack: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => 'Erro ao buscar estatísticas',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
