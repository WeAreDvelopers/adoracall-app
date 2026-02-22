<?php
namespace Tests\Feature;

use App\Models\Acordo;
use App\Models\Contato;
use App\Models\Mailing;
use App\Models\PropostaPagamento;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class URADevedorControllerTest extends TestCase
{
    protected $mailing;

    public function setUp(): void
    {
        parent::setUp();

        // Create test mailing
        $this->mailing = Mailing::factory()->create([
            'tipo' => 'cobranca',
        ]);
    }

    /**
     * Test: Consultar dívida - CPF válido com débito
     */
    public function test_consultar_divida_cpf_valido()
    {
        // Create contact with debt
        $contato = Contato::factory()->create([
            'mailing_id'   => $this->mailing->id,
            'cpf'          => '12345678901',
            'valor_debito' => 1000.00,
            'telefone'     => '11987654321',
        ]);

        $response = $this->getJson('/api/ura/devedor/12345678901');

        // Should return 200
        $response->assertStatus(200);

        // Should return debt data
        $response->assertJsonStructure([
            'success',
            'data' => [
                'cpf',
                'quantidade_debitos',
                'valor_total_debito',
                'debitos' => [
                    '*' => [
                        'contato_id',
                        'nome',
                        'telefone',
                        'valor_debito',
                    ],
                ],
                'tipo_oferta',
            ],
        ]);

        // Verify data
        $this->assertEquals('12345678901', $response['data']['cpf']);
        $this->assertEquals(1, $response['data']['quantidade_debitos']);
        $this->assertEquals(1000.00, $response['data']['valor_total_debito']);
        $this->assertEquals('singular', $response['data']['tipo_oferta']);
    }

    /**
     * Test: Consultar dívida - CPF inválido
     */
    public function test_consultar_divida_cpf_invalido()
    {
        $response = $this->getJson('/api/ura/devedor/12345');

        // Should return 422 or error
        $response->assertStatus(422);
    }

    /**
     * Test: Consultar dívida - CPF não encontrado
     */
    public function test_consultar_divida_cpf_nao_encontrado()
    {
        $response = $this->getJson('/api/ura/devedor/99999999999');

        // Should return 404
        $response->assertStatus(404);
    }

    /**
     * Test: Consultar dívida - Múltiplos débitos (tipo oferta plural)
     */
    public function test_consultar_divida_multiplos_debitos()
    {
        $cpf = '12345678901';

        // Create multiple contacts with same CPF
        Contato::factory(3)->create([
            'mailing_id'   => $this->mailing->id,
            'cpf'          => $cpf,
            'valor_debito' => 500.00,
        ]);

        $response = $this->getJson("/api/ura/devedor/$cpf");

        // Should return 200
        $response->assertStatus(200);

        // Verify plural
        $this->assertEquals('plural', $response['data']['tipo_oferta']);
        $this->assertEquals(3, $response['data']['quantidade_debitos']);
        $this->assertEquals(1500.00, $response['data']['valor_total_debito']);
    }

    /**
     * Test: Consultar propostas - CPF válido
     */
    public function test_consultar_propostas_cpf_valido()
    {
        // Create contact with debt
        $contato = Contato::factory()->create([
            'mailing_id'   => $this->mailing->id,
            'cpf'          => '12345678901',
            'valor_debito' => 1000.00,
            'nome'         => 'João Silva',
        ]);

        $response = $this->getJson('/api/ura/propostas/12345678901');

        // Should return 200
        $response->assertStatus(200);

        // Should return proposal options
        $response->assertJsonStructure([
            'success',
            'data' => [
                'cpf',
                'nome_cliente',
                'valor_total_divida',
                'opcoes_pagamento' => [
                    '*' => [
                        'opcao',
                        'parcelas',
                        'desconto_percentual',
                        'valor_final',
                        'valor_parcela',
                        'descricao',
                    ],
                ],
                'validade_proposta_horas',
            ],
        ]);

        // Verify options structure
        $opcoes = $response['data']['opcoes_pagamento'];
        $this->assertCount(3, $opcoes);

        // First option should be 1 installment (a vista)
        $this->assertEquals(1, $opcoes[0]['parcelas']);
        $this->assertEquals(15, $opcoes[0]['desconto_percentual']);

        // Second option should be 2 installments
        $this->assertEquals(2, $opcoes[1]['parcelas']);

        // Third option should be 3 installments
        $this->assertEquals(3, $opcoes[2]['parcelas']);
    }

    /**
     * Test: Consultar propostas - CPF sem débito
     */
    public function test_consultar_propostas_cpf_sem_debito()
    {
        // Create contact without debt
        Contato::factory()->create([
            'mailing_id'   => $this->mailing->id,
            'cpf'          => '12345678901',
            'valor_debito' => 0,
        ]);

        $response = $this->getJson('/api/ura/propostas/12345678901');

        // Should return 404
        $response->assertStatus(404);
    }

    /**
     * Test: Aceitar proposta - dados válidos
     */
    public function test_aceitar_proposta_valida()
    {
        // Create contact
        $contato = Contato::factory()->create([
            'mailing_id'   => $this->mailing->id,
            'cpf'          => '12345678901',
            'valor_debito' => 1000.00,
            'nome'         => 'João Silva',
            'telefone'     => '11987654321',
        ]);

        $response = $this->postJson('/api/ura/propostas/aceitar', [
            'cpf'               => '12345678901',
            'contato_id'        => $contato->id,
            'opcao_parcelas'    => 3,
            'valor_total'       => 1000.00,
            'desconto_aplicado' => 50.00,
            'call_id'           => 'call_123456',
            'confirmacao_texto' => 'Cliente confirmou via voz',
        ]);

        // Should return 201 Created
        $response->assertStatus(201);

        // Should return proposta data
        $response->assertJsonStructure([
            'success',
            'data' => [
                'proposta_id',
                'acordo_id',
                'uuid',
                'valor_total',
                'parcelas',
                'valor_parcela',
                'link_pagamento',
            ],
        ]);

        // Verify calculations
        $this->assertEquals(950.00, $response['data']['valor_total']);
        $this->assertEquals(3, $response['data']['parcelas']);
        $this->assertEquals(316.67, round($response['data']['valor_parcela'], 2));

        // Verify database
        $this->assertDatabaseHas('propostas_pagamento', [
            'contato_id'     => $contato->id,
            'valor_proposta' => 950.00,
            'desconto'       => 50.00,
        ]);

        $this->assertDatabaseHas('acordos', [
            'contato_id'         => $contato->id,
            'valor_acordado'     => 950.00,
            'parcelas_acordadas' => 3,
        ]);
    }

    /**
     * Test: Aceitar proposta - CPF inválido
     */
    public function test_aceitar_proposta_cpf_invalido()
    {
        $contato = Contato::factory()->create([
            'mailing_id' => $this->mailing->id,
        ]);

        $response = $this->postJson('/api/ura/propostas/aceitar', [
            'cpf'            => '12345', // Invalid
            'contato_id'     => $contato->id,
            'opcao_parcelas' => 3,
            'valor_total'    => 1000.00,
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Aceitar proposta - Contato não encontrado
     */
    public function test_aceitar_proposta_contato_nao_encontrado()
    {
        $response = $this->postJson('/api/ura/propostas/aceitar', [
            'cpf'            => '12345678901',
            'contato_id'     => 99999,
            'opcao_parcelas' => 3,
            'valor_total'    => 1000.00,
        ]);

        // Should return 404 or 422
        $response->assertStatus(422);
    }

    /**
     * Test: Resumo da proposta - Acordo ativo encontrado
     */
    public function test_resumo_proposta_acordo_ativo()
    {
        // Create contact
        $contato = Contato::factory()->create([
            'mailing_id' => $this->mailing->id,
            'cpf'        => '12345678901',
            'nome'       => 'João Silva',
            'telefone'   => '11987654321',
        ]);

        // Create proposal
        $proposta = PropostaPagamento::factory()->create([
            'contato_id'     => $contato->id,
            'mailing_id'     => $this->mailing->id,
            'valor_proposta' => 950.00,
        ]);

        // Create agreement
        $acordo = Acordo::create([
            'contato_id'             => $contato->id,
            'proposta_id'            => $proposta->id,
            'aceito_em'              => Carbon::now(),
            'valor_acordado'         => 950.00,
            'parcelas_acordadas'     => 3,
            'valor_parcela_acordada' => 316.67,
            'link_pagamento'         => 'https://pagamento.com/123',
            'status'                 => 'ativo',
        ]);

        $response = $this->getJson('/api/ura/propostas/12345678901/resumo');

        // Should return 200
        $response->assertStatus(200);

        // Should return agreement summary
        $response->assertJsonStructure([
            'success',
            'data' => [
                'acordo_id',
                'proposta_id',
                'uuid',
                'cliente'         => [
                    'nome',
                    'cpf',
                    'telefone',
                ],
                'detalhes_acordo' => [
                    'data_aceite',
                    'valor_total_acordado',
                    'quantidade_parcelas',
                    'valor_parcela',
                    'status_acordo',
                ],
                'proximos_passos' => [],
                'contato_suporte' => [],
            ],
        ]);

        // Verify data
        $this->assertEquals($contato->id, $response['data']['cliente']['contato_id'] ?? null);
        $this->assertEquals('João Silva', $response['data']['cliente']['nome']);
        $this->assertEquals(950.00, $response['data']['detalhes_acordo']['valor_total_acordado']);
        $this->assertEquals(3, $response['data']['detalhes_acordo']['quantidade_parcelas']);
    }

    /**
     * Test: Resumo da proposta - CPF não encontrado
     */
    public function test_resumo_proposta_cpf_nao_encontrado()
    {
        $response = $this->getJson('/api/ura/propostas/99999999999/resumo');

        // Should return 404
        $response->assertStatus(404);
    }

    /**
     * Test: Resumo da proposta - Sem acordo ativo
     */
    public function test_resumo_proposta_sem_acordo_ativo()
    {
        // Create contact without agreement
        Contato::factory()->create([
            'mailing_id' => $this->mailing->id,
            'cpf'        => '12345678901',
        ]);

        $response = $this->getJson('/api/ura/propostas/12345678901/resumo');

        // Should return 404
        $response->assertStatus(404);
    }

    /**
     * Test: Opções de parcelamento - Descontos progressivos
     */
    public function test_opcoes_parcelamento_descontos()
    {
        $contato = Contato::factory()->create([
            'mailing_id'   => $this->mailing->id,
            'cpf'          => '12345678901',
            'valor_debito' => 1000.00,
        ]);

        $response = $this->getJson('/api/ura/propostas/12345678901');

        $opcoes = $response['data']['opcoes_pagamento'];

        // À vista com 15% desconto
        $this->assertEquals(850.00, $opcoes[0]['valor_final']);
        $this->assertEquals(150.00, $opcoes[0]['desconto_valor']);

        // 2x com 10% desconto
        $this->assertEquals(900.00, $opcoes[1]['valor_final']);
        $this->assertEquals(100.00, $opcoes[1]['desconto_valor']);

        // 3x com 5% desconto
        $this->assertEquals(950.00, $opcoes[2]['valor_final']);
        $this->assertEquals(50.00, $opcoes[2]['desconto_valor']);
    }
}
