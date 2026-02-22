<?php

namespace Tests\Feature;

use App\Models\Mailing;
use App\Models\User;
use Tests\TestCase;

class MailingControllerTest extends TestCase
{
    protected $user;
    protected $mailing;
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
            'nome' => 'Teste Campanha',
            'tipo' => 'cobranca',
            'status' => 'ativa',
        ]);

        // Generate token
        $this->token = 'test_token_' . uniqid();
    }

    /**
     * Test: Listar campanhas
     */
    public function test_list_campaigns()
    {
        // Create test campaigns
        Mailing::factory(3)->create([
            'tipo' => 'cobranca',
        ]);

        $response = $this->getJson('/api/mailings');

        // Should return 200
        $response->assertStatus(200);

        // Should return array of campaigns
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'nome',
                    'tipo',
                    'status',
                    'created_at',
                ]
            ]
        ]);

        // Verify count
        $this->assertGreaterThanOrEqual(3, count($response['data']));
    }

    /**
     * Test: Criar campanha com dados válidos
     */
    public function test_create_campaign_with_valid_data()
    {
        $response = $this->postJson('/api/mailings', [
            'nome' => 'Nova Campanha',
            'tipo' => 'cobranca',
            'descricao' => 'Teste de cobrança',
        ]);

        // Should return 201 Created
        $response->assertStatus(201);

        // Should return campaign data
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'nome',
                'tipo',
                'status',
                'created_at',
            ]
        ]);

        // Verify campaign was created in database
        $this->assertDatabaseHas('mailings', [
            'nome' => 'Nova Campanha',
            'tipo' => 'cobranca',
        ]);
    }

    /**
     * Test: Criar campanha sem nome
     */
    public function test_create_campaign_without_name()
    {
        $response = $this->postJson('/api/mailings', [
            'tipo' => 'cobranca',
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Criar campanha com tipo inválido
     */
    public function test_create_campaign_with_invalid_type()
    {
        $response = $this->postJson('/api/mailings', [
            'nome' => 'Teste',
            'tipo' => 'invalido',
        ]);

        // Should return 422 or 400
        $response->assertStatus(422);
    }

    /**
     * Test: Obter detalhes da campanha
     */
    public function test_get_campaign_details()
    {
        $response = $this->getJson("/api/mailings/{$this->mailing->id}");

        // Should return 200
        $response->assertStatus(200);

        // Verify data matches
        $this->assertEquals($this->mailing->id, $response['data']['id']);
        $this->assertEquals($this->mailing->nome, $response['data']['nome']);
    }

    /**
     * Test: Obter campanha inexistente
     */
    public function test_get_nonexistent_campaign()
    {
        $response = $this->getJson('/api/mailings/99999');

        // Should return 404
        $response->assertStatus(404);
    }

    /**
     * Test: Atualizar campanha
     */
    public function test_update_campaign()
    {
        $response = $this->putJson("/api/mailings/{$this->mailing->id}", [
            'nome' => 'Campanha Atualizada',
            'descricao' => 'Nova descrição',
        ]);

        // Should return 200
        $response->assertStatus(200);

        // Verify campaign was updated
        $this->assertDatabaseHas('mailings', [
            'id' => $this->mailing->id,
            'nome' => 'Campanha Atualizada',
        ]);
    }

    /**
     * Test: Deletar campanha
     */
    public function test_delete_campaign()
    {
        $response = $this->deleteJson("/api/mailings/{$this->mailing->id}");

        // Should return 200
        $response->assertStatus(200);

        // Verify campaign was deleted
        $this->assertDatabaseMissing('mailings', [
            'id' => $this->mailing->id,
        ]);
    }

    /**
     * Test: Ativar campanha
     */
    public function test_activate_campaign()
    {
        // Create paused campaign
        $mailing = Mailing::factory()->create([
            'status' => 'pausada',
        ]);

        $response = $this->postJson("/api/mailings/{$mailing->id}/ativar");

        // Should return 200
        $response->assertStatus(200);

        // Verify status was updated
        $mailing->refresh();
        $this->assertEquals('ativa', $mailing->status);
    }

    /**
     * Test: Pausar campanha
     */
    public function test_pause_campaign()
    {
        $response = $this->postJson("/api/mailings/{$this->mailing->id}/pausar");

        // Should return 200
        $response->assertStatus(200);

        // Verify status was updated
        $this->mailing->refresh();
        $this->assertEquals('pausada', $this->mailing->status);
    }

    /**
     * Test: Listar campanhas com filtro de tipo
     */
    public function test_list_campaigns_with_type_filter()
    {
        // Create campaigns of different types
        Mailing::factory(2)->create(['tipo' => 'cobranca']);
        Mailing::factory(2)->create(['tipo' => 'pesquisa']);

        $response = $this->getJson('/api/mailings?tipo=cobranca');

        // Should return 200
        $response->assertStatus(200);

        // All returned campaigns should be of type 'cobranca'
        foreach ($response['data'] as $campaign) {
            $this->assertEquals('cobranca', $campaign['tipo']);
        }
    }

    /**
     * Test: Listar campanhas com filtro de status
     */
    public function test_list_campaigns_with_status_filter()
    {
        // Create campaigns with different status
        Mailing::factory(2)->create(['status' => 'ativa']);
        Mailing::factory(2)->create(['status' => 'pausada']);

        $response = $this->getJson('/api/mailings?status=ativa');

        // Should return 200
        $response->assertStatus(200);

        // All returned campaigns should have status 'ativa'
        foreach ($response['data'] as $campaign) {
            $this->assertEquals('ativa', $campaign['status']);
        }
    }

    /**
     * Test: Listar campanhas com busca por nome
     */
    public function test_list_campaigns_with_search()
    {
        Mailing::factory()->create(['nome' => 'Campanha Especial']);
        Mailing::factory()->create(['nome' => 'Campanha Normal']);

        $response = $this->getJson('/api/mailings?search=Especial');

        // Should return 200
        $response->assertStatus(200);

        // Should contain only matching campaign
        $this->assertTrue(
            collect($response['data'])->pluck('nome')->contains('Campanha Especial')
        );
    }

    /**
     * Test: Importar CSV em uma campanha
     */
    public function test_import_csv_to_campaign()
    {
        // Create a CSV file content
        $csvContent = "nome,telefone,cpf,email,valor_debito,data_vencimento,data_nascimento\n";
        $csvContent .= "João Silva,11987654321,12345678901,joao@test.com,1000.00,2024-12-31,1990-01-01\n";
        $csvContent .= "Maria Santos,11987654322,12345678902,maria@test.com,2000.00,2024-12-31,1992-05-15\n";

        $response = $this->postJson("/api/mailings/{$this->mailing->id}/importar-csv", [
            'csv_content' => $csvContent,
        ]);

        // Should return 200 or 201
        $response->assertStatus(200);
    }

    /**
     * Test: Importar CSV com dados inválidos
     */
    public function test_import_csv_with_invalid_data()
    {
        // CSV with invalid phone (only 2 digits)
        $csvContent = "nome,telefone,cpf,email,valor_debito,data_vencimento,data_nascimento\n";
        $csvContent .= "João Silva,11,12345678901,joao@test.com,1000.00,2024-12-31,1990-01-01\n";

        $response = $this->postJson("/api/mailings/{$this->mailing->id}/importar-csv", [
            'csv_content' => $csvContent,
        ]);

        // Should process but mark row as error
        $response->assertStatus(200);
    }

    /**
     * Test: Campanhas têm status padrão "ativa" ao criar
     */
    public function test_campaign_default_status_is_ativa()
    {
        $response = $this->postJson('/api/mailings', [
            'nome' => 'Nova Campanha',
            'tipo' => 'cobranca',
        ]);

        // Verify status is 'ativa'
        $this->assertEquals('ativa', $response['data']['status']);
    }
}
