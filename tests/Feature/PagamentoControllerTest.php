<?php

namespace Tests\Feature;

use App\Models\Contato;
use App\Models\Mailing;
use App\Models\PropostaPagamento;
use App\Models\User;
use Tests\TestCase;

class PagamentoControllerTest extends TestCase
{
    protected $user;
    protected $mailing;
    protected $contato;
    protected $token;

    public function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create test mailing
        $this->mailing = Mailing::factory()->create([
            'nome' => 'Teste Pagamentos',
            'tipo' => 'cobranca',
        ]);

        // Create test contact
        $this->contato = Contato::factory()->create([
            'mailing_id' => $this->mailing->id,
            'nome' => 'João Silva',
            'telefone' => '+5511987654321',
            'valor_debito' => 1000.00,
        ]);

        // Generate token
        $this->token = 'test_token_' . uniqid();
    }

    /**
     * Test: Criar proposta com dados válidos
     */
    public function test_create_proposal_with_valid_data()
    {
        $response = $this->postJson('/api/propostas-pagamento', [
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
            'valor' => 1000.00,
            'desconto' => 100.00,
            'parcelas' => 3,
        ]);

        // Should return 201 Created
        $response->assertStatus(201);

        // Should return proposal data
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'uuid',
                'contato_id',
                'valor',
                'desconto',
                'valor_final',
                'parcelas',
                'status',
                'link_pagamento',
                'expires_at',
            ]
        ]);

        // Verify UUID is unique
        $this->assertNotNull($response['data']['uuid']);
        $this->assertTrue(strlen($response['data']['uuid']) > 10);

        // Verify valor_final = valor - desconto
        $esperado = 1000.00 - 100.00;
        $this->assertEquals($esperado, $response['data']['valor_final']);
    }

    /**
     * Test: Criar proposta com contato inválido
     */
    public function test_create_proposal_with_invalid_contact()
    {
        $response = $this->postJson('/api/propostas-pagamento', [
            'contato_id' => 99999,
            'mailing_id' => $this->mailing->id,
            'valor' => 1000.00,
        ]);

        // Should return 404
        $response->assertStatus(404);
    }

    /**
     * Test: Validação de dados obrigatórios
     */
    public function test_create_proposal_missing_required_fields()
    {
        // Missing valor
        $response = $this->postJson('/api/propostas-pagamento', [
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Validação de valor negativo
     */
    public function test_create_proposal_with_negative_value()
    {
        $response = $this->postJson('/api/propostas-pagamento', [
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
            'valor' => -1000.00,
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Listar propostas
     */
    public function test_list_proposals()
    {
        // Create test proposals
        PropostaPagamento::factory(3)->create([
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
        ]);

        $response = $this->getJson('/api/propostas-pagamento');

        // Should return 200
        $response->assertStatus(200);

        // Should return array of proposals
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'uuid',
                    'contato_id',
                    'valor',
                    'status',
                ]
            ]
        ]);
    }

    /**
     * Test: Obter detalhes da proposta
     */
    public function test_get_proposal_details()
    {
        $proposta = PropostaPagamento::factory()->create([
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
        ]);

        $response = $this->getJson("/api/propostas-pagamento/{$proposta->id}");

        // Should return 200
        $response->assertStatus(200);

        // Verify data matches
        $this->assertEquals($proposta->id, $response['data']['id']);
        $this->assertEquals($proposta->valor, $response['data']['valor']);
    }

    /**
     * Test: Obter proposta inexistente
     */
    public function test_get_nonexistent_proposal()
    {
        $response = $this->getJson('/api/propostas-pagamento/99999');

        // Should return 404
        $response->assertStatus(404);
    }

    /**
     * Test: Enviar SMS com link de pagamento
     */
    public function test_send_sms_with_payment_link()
    {
        $proposta = PropostaPagamento::factory()->create([
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
            'uuid' => 'test-uuid-123',
        ]);

        $response = $this->postJson(
            "/api/propostas-pagamento/{$proposta->id}/reenviar-sms",
            [
                'telefone' => '5511987654321',
            ]
        );

        // Should return 200
        $response->assertStatus(200);

        // Should return SMS info
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'sms_id',
                'status',
                'telefone',
            ]
        ]);
    }

    /**
     * Test: Enviar SMS com telefone inválido
     */
    public function test_send_sms_with_invalid_phone()
    {
        $proposta = PropostaPagamento::factory()->create([
            'contato_id' => $this->contato->id,
        ]);

        $response = $this->postJson(
            "/api/propostas-pagamento/{$proposta->id}/reenviar-sms",
            [
                'telefone' => '111', // Invalid
            ]
        );

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: UUID é único para cada proposta
     */
    public function test_proposal_uuid_is_unique()
    {
        $proposta1 = PropostaPagamento::factory()->create([
            'contato_id' => $this->contato->id,
        ]);

        $proposta2 = PropostaPagamento::factory()->create([
            'contato_id' => $this->contato->id,
        ]);

        // UUIDs should be different
        $this->assertNotEquals($proposta1->uuid, $proposta2->uuid);
    }

    /**
     * Test: Link de pagamento contém UUID
     */
    public function test_payment_link_contains_uuid()
    {
        $proposta = PropostaPagamento::factory()->create([
            'contato_id' => $this->contato->id,
            'uuid' => 'test-uuid-12345',
        ]);

        $response = $this->getJson("/api/propostas-pagamento/{$proposta->id}");

        // Link should contain UUID
        $this->assertStringContainsString('test-uuid-12345', $response['data']['link_pagamento']);
    }

    /**
     * Test: Proposta tem status inicial "aguardando_pagamento"
     */
    public function test_proposal_initial_status()
    {
        $proposta = PropostaPagamento::factory()->create([
            'contato_id' => $this->contato->id,
        ]);

        // Status should be "aguardando_pagamento"
        $this->assertEquals('aguardando_pagamento', $proposta->status);
    }

    /**
     * Test: Desconto é opcional
     */
    public function test_proposal_without_discount()
    {
        $response = $this->postJson('/api/propostas-pagamento', [
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
            'valor' => 1000.00,
            'parcelas' => 1,
        ]);

        // Should return 201
        $response->assertStatus(201);

        // valor_final should equal valor (no discount)
        $this->assertEquals(1000.00, $response['data']['valor_final']);
    }

    /**
     * Test: Parcelas é obrigatório e validado
     */
    public function test_proposal_parcelas_validation()
    {
        // Test with 0 parcelas (invalid)
        $response = $this->postJson('/api/propostas-pagamento', [
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
            'valor' => 1000.00,
            'parcelas' => 0,
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: SMS é registrado no banco
     */
    public function test_sms_is_logged_in_database()
    {
        $proposta = PropostaPagamento::factory()->create([
            'contato_id' => $this->contato->id,
        ]);

        $this->postJson(
            "/api/propostas-pagamento/{$proposta->id}/reenviar-sms",
            ['telefone' => '5511987654321']
        );

        // Refresh proposta
        $proposta->refresh();

        // Verify SMS was registered
        $this->assertNotNull($proposta->sms_enviados);
    }
}
