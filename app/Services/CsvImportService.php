<?php

namespace App\Services;

use App\Models\Contato;
use App\Models\Empresa;
use App\Models\ImportLog;
use App\Models\Mailing;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling CSV import operations
 * Validates, processes, and stores contact data from CSV files
 */
class CsvImportService
{
    const BATCH_SIZE = 100;
    const MAX_FILE_SIZE = 10240; // 10MB in KB

    private $mailing;
    private $file;
    private $stats = [];
    private $startTime;

    /**
     * Initialize service with mailing and file
     */
    public function __construct(Mailing $mailing, UploadedFile $file)
    {
        $this->mailing = $mailing;
        $this->file = $file;
        $this->stats = [
            'total_linhas' => 0,
            'sucesso' => 0,
            'erros' => 0,
            'avisos' => 0,
            'ignoradas' => 0,
            'duplicadas' => 0,
        ];
    }

    /**
     * Validate file before import
     */
    public function validateFile(): array
    {
        $errors = [];

        // Check file size
        if ($this->file->getSize() > (self::MAX_FILE_SIZE * 1024)) {
            $errors[] = 'Arquivo excede tamanho máximo de ' . self::MAX_FILE_SIZE . 'KB';
        }

        // Check MIME type
        $allowedMimes = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/x-csv',
            'text/x-csv',
            'application/octet-stream',
        ];

        if (!in_array($this->file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Tipo de arquivo inválido. Apenas CSV ou TXT são permitidos.';
        }

        // Check if file is readable
        if (!is_readable($this->file->getRealPath())) {
            $errors[] = 'Arquivo não pode ser lido';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate CSV header
     */
    public function validateHeader(array $header): array
    {
        $errors = [];
        $requiredColumns = ['nome', 'telefone'];
        $missingColumns = array_diff($requiredColumns, $header);

        if (!empty($missingColumns)) {
            $errors['header'] = 'Colunas obrigatórias faltando: ' . implode(', ', $missingColumns);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Process and import CSV file
     */
    public function import(callable $progressCallback = null): array
    {
        $this->startTime = microtime(true);

        try {
            DB::beginTransaction();

            $handle = fopen($this->file->getRealPath(), 'r');

            if (!$handle) {
                throw new \Exception('Não foi possível abrir o arquivo');
            }

            // Read and validate header
            $header = fgetcsv($handle, 0, ',');

            if (!$header) {
                fclose($handle);
                throw new \Exception('Arquivo CSV vazio ou inválido');
            }

            $headerValidation = $this->validateHeader($header);
            if (!$headerValidation['valid']) {
                fclose($handle);
                throw new \Exception($headerValidation['errors']['header']);
            }

            // Process rows
            $lineNumber = 1;
            $batch = [];

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $lineNumber++;
                $this->stats['total_linhas']++;

                $processedRow = $this->processRow($row, $header, $lineNumber);

                if ($processedRow === null) {
                    continue; // Row was skipped or had errors
                }

                $batch[] = $processedRow;

                // Commit in batches for performance
                if (count($batch) >= self::BATCH_SIZE) {
                    $this->saveBatch($batch);
                    $batch = [];

                    if ($progressCallback) {
                        $progressCallback($this->stats['total_linhas'], $this->stats);
                    }
                }
            }

            // Save remaining batch
            if (!empty($batch)) {
                $this->saveBatch($batch);
            }

            fclose($handle);

            // Update mailing with statistics
            $this->updateMailingStats();

            DB::commit();

            // Log success
            ApiResponseService::logAction('import_csv_completed', 'Mailing', $this->mailing->id, null, [
                'arquivo' => $this->file->getClientOriginalName(),
                'total_contatos' => $this->stats['sucesso'],
                'erros' => $this->stats['erros'],
                'tempo_segundos' => round(microtime(true) - $this->startTime, 2),
            ]);

            return [
                'success' => true,
                'stats' => $this->stats,
                'tempo_total' => round(microtime(true) - $this->startTime, 2),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('CSV Import Error', [
                'mailing_id' => $this->mailing->id,
                'arquivo' => $this->file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->stats,
            ];
        }
    }

    /**
     * Process individual CSV row
     */
    private function processRow(array $row, array $header, int $lineNumber): ?array
    {
        $inicioLinha = microtime(true);

        // Skip empty rows
        if (empty(array_filter($row))) {
            $this->stats['ignoradas']++;
            return null;
        }

        // Validate column count
        if (count($header) !== count($row)) {
            ImportLog::logError(
                $this->mailing->id,
                $lineNumber,
                ['row' => $row],
                'Número de colunas não corresponde ao cabeçalho',
                ['esperado' => count($header), 'recebido' => count($row)]
            );
            $this->stats['erros']++;
            return null;
        }

        $dados = array_combine($header, $row);

        // Validate contact data
        $validacao = $this->validateContactData($dados);
        if (!$validacao['valid']) {
            ImportLog::logError(
                $this->mailing->id,
                $lineNumber,
                $dados,
                'Erro de validação',
                $validacao['errors']
            );
            $this->stats['erros']++;
            return null;
        }

        // Format phone
        $telefoneFormatado = $this->formatPhone($dados['telefone']);
        if (!$telefoneFormatado) {
            ImportLog::logError(
                $this->mailing->id,
                $lineNumber,
                $dados,
                'Telefone não pôde ser formatado',
                []
            );
            $this->stats['erros']++;
            return null;
        }

        // Check for duplicates
        if ($this->isDuplicate($telefoneFormatado, $lineNumber, $dados)) {
            $this->stats['duplicadas']++;
            return null;
        }

        // Apply mailing filters
        $filterResult = $this->applyFilters($dados);
        if (!$filterResult['passed']) {
            ImportLog::logSkipped(
                $this->mailing->id,
                $lineNumber,
                $dados,
                'Filtrado: ' . $filterResult['reason']
            );
            $this->stats['ignoradas']++;
            return null;
        }

        // Prepare contact data
        return $this->prepareContactData($dados, $telefoneFormatado, $lineNumber);
    }

    /**
     * Validate contact data using ValidationService
     */
    private function validateContactData(array $dados): array
    {
        $errors = [];

        // Validate name
        $nome = ValidationService::validateString($dados['nome'] ?? null, 2, 255);
        if (!$nome) {
            $errors['nome'] = 'Nome obrigatório (2-255 caracteres)';
        }

        // Validate phone
        $telefone = ValidationService::validatePhone($dados['telefone'] ?? null);
        if (!$telefone) {
            $errors['telefone'] = 'Telefone em formato inválido';
        }

        // Validate CPF if provided
        if (isset($dados['cpf']) && !empty($dados['cpf'])) {
            $cpf = ValidationService::validateCpf($dados['cpf']);
            if (!$cpf) {
                $errors['cpf'] = 'CPF inválido';
            }
        }

        // Validate numeric values
        if (isset($dados['valor_debito']) && !empty($dados['valor_debito'])) {
            $valor = ValidationService::validateNumericRange($dados['valor_debito'], 0);
            if ($valor === null) {
                $errors['valor_debito'] = 'Valor deve ser numérico e não negativo';
            }
        }

        // Validate dates
        foreach (['vencimento', 'data_nascimento'] as $dateField) {
            if (isset($dados[$dateField]) && !empty($dados[$dateField])) {
                $data = ValidationService::validateDate($dados[$dateField]);
                if (!$data) {
                    $errors[$dateField] = "Data inválida (use DD/MM/YYYY ou YYYY-MM-DD)";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Format phone number for storage
     */
    private function formatPhone(string $telefone): ?string
    {
        $telefoneLimpo = ValidationService::validatePhone($telefone);

        if (!$telefoneLimpo) {
            return null;
        }

        // Add Brazil country code if not present
        if (strlen($telefoneLimpo) <= 11) {
            $telefoneLimpo = '55' . $telefoneLimpo;
        }

        return '+' . $telefoneLimpo;
    }

    /**
     * Check if contact already exists in mailing
     */
    private function isDuplicate(string $telefone, int $lineNumber, array $dados): bool
    {
        $existing = Contato::where('telefone', $telefone)
            ->where('mailing_id', $this->mailing->id)
            ->first();

        if ($existing) {
            ImportLog::logDuplicated(
                $this->mailing->id,
                $lineNumber,
                $dados,
                $existing->id
            );
            return true;
        }

        return false;
    }

    /**
     * Apply mailing filters to contact data
     */
    private function applyFilters(array $dados): array
    {
        if (empty($this->mailing->filtros)) {
            return ['passed' => true];
        }

        $filtros = $this->mailing->filtros;

        // Filter by DDD if specified
        if (isset($filtros['ddd']) && !empty($filtros['ddd'])) {
            $ddd = ValidationService::extractDdd($dados['telefone']);
            if (!$ddd || !in_array($ddd, $filtros['ddd'])) {
                return ['passed' => false, 'reason' => 'DDD não corresponde'];
            }
        }

        // Add more filters as needed

        return ['passed' => true];
    }

    /**
     * Prepare contact data for database storage
     */
    private function prepareContactData(array $dados, string $telefoneFormatado, int $lineNumber): array
    {
        $cpf = $dados['cpf'] ?? null;

        return [
            'mailing_id' => $this->mailing->id,
            'empresa_id' => $this->mailing->empresa_id,
            'nome' => trim($dados['nome']),
            'telefone' => $telefoneFormatado,
            'valor_debito' => floatval($dados['valor_debito'] ?? 0),
            'vencimento' => isset($dados['vencimento']) && !empty($dados['vencimento'])
                ? date('Y-m-d', strtotime($dados['vencimento']))
                : null,
            'data_nascimento' => isset($dados['data_nascimento']) && !empty($dados['data_nascimento'])
                ? date('Y-m-d', strtotime($dados['data_nascimento']))
                : null,
            'campanha' => $dados['campanha'] ?? $this->mailing->nome,
            'empresa_credora' => $this->getEmpresaCredora($dados),
            'cpf' => $cpf,
            'cpf_primeiros_digitos' => $cpf ? substr($cpf, 0, 3) : null,
            'status' => 'pendente',
            'linha_numero' => $lineNumber,
            'dados_originais' => $dados,
        ];
    }

    /**
     * Save batch of contacts to database
     */
    private function saveBatch(array $batch): void
    {
        foreach ($batch as $data) {
            $linhaNumero = $data['linha_numero'];
            $dadosOriginais = $data['dados_originais'];
            unset($data['linha_numero'], $data['dados_originais']);

            try {
                $contato = Contato::create($data);

                // Log successful import
                ImportLog::create([
                    'mailing_id' => $this->mailing->id,
                    'empresa_id' => $this->mailing->empresa_id,
                    'arquivo_nome' => $this->file->getClientOriginalName(),
                    'linha_numero' => $linhaNumero,
                    'status' => ImportLog::STATUS_SUCCESS,
                    'dados_originais' => $dadosOriginais,
                    'dados_processados' => $data,
                    'mensagem' => 'Contato importado com sucesso',
                    'contato_id' => $contato->id,
                ]);

                $this->stats['sucesso']++;

            } catch (\Exception $e) {
                ImportLog::logError(
                    $this->mailing->id,
                    $linhaNumero,
                    $dadosOriginais,
                    'Erro ao salvar: ' . $e->getMessage(),
                    []
                );
                $this->stats['erros']++;
            }
        }
    }

    /**
     * Update mailing with import statistics
     */
    private function updateMailingStats(): void
    {
        $tempoTotal = microtime(true) - $this->startTime;

        $this->mailing->update([
            'arquivo_csv_path' => $this->file->getClientOriginalName(),
            'total_contatos' => $this->stats['sucesso'],
            'status' => $this->stats['sucesso'] > 0 ? 'pronto' : 'rascunho',
            'ultimo_arquivo_importado' => $this->file->getClientOriginalName(),
            'ultima_importacao_em' => Carbon::now(),
            'estatisticas_importacao' => array_merge($this->stats, [
                'tempo_total_segundos' => round($tempoTotal, 2),
                'linhas_por_segundo' => $this->stats['total_linhas'] > 0
                    ? round($this->stats['total_linhas'] / $tempoTotal, 2)
                    : 0,
            ]),
        ]);
    }

    /**
     * Get current statistics
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    private function getEmpresaCredora(array $dados): ?string
    {
        $empresa = Empresa::withoutGlobalScopes()->find($this->mailing->empresa_id);
        if ($empresa) {
            $config = $empresa->configuracoes ?? [];
            return $config['nome_credora'] ?? $empresa->nome;
        }
        return $dados['empresa_credora'] ?? null;
    }
}
