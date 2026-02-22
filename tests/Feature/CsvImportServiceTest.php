<?php

namespace Tests\Feature;

use App\Models\Contato;
use App\Models\Mailing;
use App\Models\ImportLog;
use App\Services\CsvImportService;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class CsvImportServiceTest extends TestCase
{
    protected $csvImportService;
    protected $mailing;

    public function setUp(): void
    {
        parent::setUp();

        // Initialize service
        $this->csvImportService = new CsvImportService();

        // Create test mailing
        $this->mailing = Mailing::factory()->create([
            'tipo' => 'cobranca',
        ]);
    }

    /**
     * Test: Validar arquivo CSV válido
     */
    public function test_validate_valid_csv_file()
    {
        $csvContent = "nome,telefone,cpf,email,valor_debito,data_vencimento,data_nascimento\n";
        $csvContent .= "João Silva,11987654321,12345678901,joao@test.com,1000.00,2024-12-31,1990-01-01\n";

        // Create temporary file
        $file = UploadedFile::fromArray([
            'name' => 'test.csv',
            'type' => 'text/csv',
            'tmp_name' => $this->createTempFile($csvContent),
            'error' => 0,
            'size' => strlen($csvContent),
        ]);

        // Should validate without error
        $isValid = $this->csvImportService->validateFile($file);
        $this->assertTrue($isValid);
    }

    /**
     * Test: Rejeitar arquivo com MIME type inválido
     */
    public function test_reject_invalid_mime_type()
    {
        // Create file with wrong MIME type
        $file = UploadedFile::fromArray([
            'name' => 'test.txt',
            'type' => 'application/pdf',
            'tmp_name' => $this->createTempFile('some content'),
            'error' => 0,
            'size' => 100,
        ]);

        // Should return false
        $isValid = $this->csvImportService->validateFile($file);
        $this->assertFalse($isValid);
    }

    /**
     * Test: Validar cabeçalho CSV
     */
    public function test_validate_csv_header()
    {
        $csvContent = "nome,telefone,cpf,email,valor_debito,data_vencimento,data_nascimento\n";

        $isValid = $this->csvImportService->validateHeader($csvContent);
        $this->assertTrue($isValid);
    }

    /**
     * Test: Rejeitar cabeçalho inválido
     */
    public function test_reject_invalid_header()
    {
        $csvContent = "invalid,headers,here\n";

        $isValid = $this->csvImportService->validateHeader($csvContent);
        $this->assertFalse($isValid);
    }

    /**
     * Test: Detectar duplicatas no CSV
     */
    public function test_detect_duplicate_contacts()
    {
        // Create an existing contact
        Contato::factory()->create([
            'mailing_id' => $this->mailing->id,
            'telefone' => '11987654321',
            'cpf' => '12345678901',
        ]);

        $contactData = [
            'telefone' => '11987654321',
            'cpf' => '12345678901',
        ];

        $isDuplicate = $this->csvImportService->isDuplicate($contactData, $this->mailing->id);
        $this->assertTrue($isDuplicate);
    }

    /**
     * Test: Não detectar como duplicata se não existir
     */
    public function test_not_duplicate_if_not_exists()
    {
        $contactData = [
            'telefone' => '11999999999',
            'cpf' => '99999999999',
        ];

        $isDuplicate = $this->csvImportService->isDuplicate($contactData, $this->mailing->id);
        $this->assertFalse($isDuplicate);
    }

    /**
     * Test: Validar dados de contato individual
     */
    public function test_validate_contact_data()
    {
        $contactData = [
            'nome' => 'João Silva',
            'telefone' => '11987654321',
            'cpf' => '12345678901',
            'email' => 'joao@test.com',
            'valor_debito' => '1000.00',
            'data_vencimento' => '2024-12-31',
            'data_nascimento' => '1990-01-01',
        ];

        $errors = $this->csvImportService->validateContactData($contactData);

        // Should have no errors
        $this->assertEmpty($errors);
    }

    /**
     * Test: Rejeitar contato com nome muito curto
     */
    public function test_reject_contact_with_short_name()
    {
        $contactData = [
            'nome' => 'J',
            'telefone' => '11987654321',
            'cpf' => '12345678901',
            'email' => 'joao@test.com',
            'valor_debito' => '1000.00',
            'data_vencimento' => '2024-12-31',
            'data_nascimento' => '1990-01-01',
        ];

        $errors = $this->csvImportService->validateContactData($contactData);

        // Should have error for name
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('nome', $errors);
    }

    /**
     * Test: Rejeitar contato com telefone inválido
     */
    public function test_reject_contact_with_invalid_phone()
    {
        $contactData = [
            'nome' => 'João Silva',
            'telefone' => '123', // Too short
            'cpf' => '12345678901',
            'email' => 'joao@test.com',
            'valor_debito' => '1000.00',
            'data_vencimento' => '2024-12-31',
            'data_nascimento' => '1990-01-01',
        ];

        $errors = $this->csvImportService->validateContactData($contactData);

        // Should have error for phone
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('telefone', $errors);
    }

    /**
     * Test: Formatar telefone com country code
     */
    public function test_format_phone_with_country_code()
    {
        $phone = '11987654321';
        $formatted = $this->csvImportService->formatPhone($phone);

        // Should have +55 prefix
        $this->assertEquals('+5511987654321', $formatted);
    }

    /**
     * Test: Aplicar filtros no CSV
     */
    public function test_apply_csv_filters()
    {
        $row = [
            'nome' => 'João Silva',
            'telefone' => '11987654321',
            'cpf' => '12345678901',
            'valor_debito' => '1000.00',
        ];

        // Apply filters should clean data
        $filtered = $this->csvImportService->applyFilters($row);

        // Should still contain required fields
        $this->assertArrayHasKey('nome', $filtered);
        $this->assertArrayHasKey('telefone', $filtered);
        $this->assertArrayHasKey('cpf', $filtered);
    }

    /**
     * Test: Importar CSV completo
     */
    public function test_complete_csv_import()
    {
        $csvContent = "nome,telefone,cpf,email,valor_debito,data_vencimento,data_nascimento\n";
        $csvContent .= "João Silva,11987654321,12345678901,joao@test.com,1000.00,2024-12-31,1990-01-01\n";
        $csvContent .= "Maria Santos,11987654322,12345678902,maria@test.com,2000.00,2024-12-31,1992-05-15\n";

        $file = UploadedFile::fromArray([
            'name' => 'test.csv',
            'type' => 'text/csv',
            'tmp_name' => $this->createTempFile($csvContent),
            'error' => 0,
            'size' => strlen($csvContent),
        ]);

        // Import contacts
        $result = $this->csvImportService->import($file, $this->mailing->id);

        // Should have processed contacts
        $this->assertIsArray($result);
    }

    /**
     * Test: Gerar relatório de importação
     */
    public function test_import_report_generation()
    {
        $csvContent = "nome,telefone,cpf,email,valor_debito,data_vencimento,data_nascimento\n";
        $csvContent .= "João Silva,11987654321,12345678901,joao@test.com,1000.00,2024-12-31,1990-01-01\n";

        $file = UploadedFile::fromArray([
            'name' => 'test.csv',
            'type' => 'text/csv',
            'tmp_name' => $this->createTempFile($csvContent),
            'error' => 0,
            'size' => strlen($csvContent),
        ]);

        $result = $this->csvImportService->import($file, $this->mailing->id);

        // Should have report fields
        $this->assertArrayHasKey('total_linhas', $result);
        $this->assertArrayHasKey('sucesso', $result);
        $this->assertArrayHasKey('erros', $result);
    }

    /**
     * Helper: Create temporary CSV file
     */
    protected function createTempFile($content)
    {
        $tmpfile = tmpfile();
        fwrite($tmpfile, $content);
        fseek($tmpfile, 0);
        return $tmpfile;
    }
}
