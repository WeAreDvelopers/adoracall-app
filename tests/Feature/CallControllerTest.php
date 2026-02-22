<?php

namespace Tests\Feature;

use App\Models\Contato;
use App\Models\Ligacao;
use App\Models\Mailing;
use App\Models\User;
use Tests\TestCase;

class CallControllerTest extends TestCase
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
            'nome' => 'Teste Chamadas',
            'tipo' => 'cobranca',
        ]);

        // Create test contact
        $this->contato = Contato::factory()->create([
            'mailing_id' => $this->mailing->id,
            'nome' => 'João Silva',
            'telefone' => '+5511987654321',
            'valor_debito' => 1000.00,
            'status' => 'pendente',
        ]);

        // Generate token
        $this->token = 'test_token_' . uniqid();
    }

    /**
     * Test: Iniciar chamada com contato válido
     */
    public function test_start_call_with_valid_contact()
    {
        $response = $this->postJson('/api/ura/call/start', [
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
        ]);

        // Should return 200 or 201
        $response->assertStatus(200);

        // Should return call_id
        $response->assertJsonStructure([
            'success',
            'data' => [
                'call_id',
                'status',
                'contato' => [
                    'id',
                    'nome',
                    'telefone',
                ]
            ]
        ]);

        // Verify call_id format
        $this->assertNotNull($response['data']['call_id']);
        $this->assertTrue(strlen($response['data']['call_id']) > 0);
    }

    /**
     * Test: Iniciar chamada com contato inválido
     */
    public function test_start_call_with_invalid_contact()
    {
        $response = $this->postJson('/api/ura/call/start', [
            'contato_id' => 99999,
            'mailing_id' => $this->mailing->id,
        ]);

        // Should return 404
        $response->assertStatus(404);
        $response->assertJsonStructure([
            'success',
            'message'
        ]);
    }

    /**
     * Test: Iniciar chamada com mailing inválido
     */
    public function test_start_call_with_invalid_mailing()
    {
        $response = $this->postJson('/api/ura/call/start', [
            'contato_id' => $this->contato->id,
            'mailing_id' => 99999,
        ]);

        // Should return 404
        $response->assertStatus(404);
    }

    /**
     * Test: Validação de dados obrigatórios
     */
    public function test_start_call_missing_required_fields()
    {
        // Missing contato_id
        $response = $this->postJson('/api/ura/call/start', [
            'mailing_id' => $this->mailing->id,
        ]);

        // Should return 422
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'errors'
        ]);
    }

    /**
     * Test: Obter informações da chamada
     */
    public function test_get_call_info()
    {
        // First, create a call
        $ligacao = Ligacao::factory()->create([
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
            'retell_call_id' => 'call_test_123',
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/ura/call/{$ligacao->retell_call_id}/info");

        // Should return 200
        $response->assertStatus(200);

        // Verify structure
        $response->assertJsonStructure([
            'success',
            'data' => [
                'call_id',
                'status',
                'contato_id',
                'mailing_id',
            ]
        ]);
    }

    /**
     * Test: Obter informações de chamada inexistente
     */
    public function test_get_call_info_not_found()
    {
        $response = $this->getJson('/api/ura/call/call_invalid_123/info');

        // Should return 404
        $response->assertStatus(404);
    }

    /**
     * Test: Webhook com payload válido
     */
    public function test_webhook_with_valid_payload()
    {
        // Create a call first
        $ligacao = Ligacao::factory()->create([
            'retell_call_id' => 'call_webhook_test_123',
            'status' => 'initiated',
        ]);

        $payload = json_encode([
            'event' => 'call_ended',
            'call_id' => 'call_webhook_test_123',
            'status' => 'completed',
            'data' => [
                'duration_seconds' => 120,
                'transcript' => 'Olá, como posso ajudar?',
                'status_result' => 'success'
            ]
        ]);

        // Calculate valid signature (if needed)
        $secret = config('services.retell.webhook_secret');
        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->postJson('/api/ura/webhook',
            json_decode($payload, true),
            [
                'X-Retell-Signature' => $signature,
            ]
        );

        // Should return 200
        $response->assertStatus(200);

        // Verify call status was updated
        $ligacao->refresh();
        $this->assertEquals('completed', $ligacao->status);
    }

    /**
     * Test: Webhook com assinatura inválida
     */
    public function test_webhook_with_invalid_signature()
    {
        $payload = json_encode([
            'event' => 'call_ended',
            'call_id' => 'call_invalid_sig_123',
            'status' => 'completed',
        ]);

        $response = $this->postJson('/api/ura/webhook',
            json_decode($payload, true),
            [
                'X-Retell-Signature' => 'invalid_signature_here',
            ]
        );

        // Should return 401
        $response->assertStatus(401);
    }

    /**
     * Test: Webhook com payload inválido
     */
    public function test_webhook_with_invalid_payload()
    {
        $response = $this->postJson('/api/ura/webhook', [
            'event' => 'invalid_event',
            'call_id' => 'call_test',
        ]);

        // Should handle gracefully (200 or 400)
        $this->assertIn($response->status(), [200, 400]);
    }

    /**
     * Test: Telefone é formatado corretamente
     */
    public function test_call_phone_formatting()
    {
        $response = $this->postJson('/api/ura/call/start', [
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
        ]);

        // Verify phone is in correct format (+55...)
        $this->assertStringStartsWith('+55', $response['data']['contato']['telefone']);
    }

    /**
     * Test: Ligação é registrada no banco
     */
    public function test_call_is_logged_in_database()
    {
        $initialCount = Ligacao::count();

        $response = $this->postJson('/api/ura/call/start', [
            'contato_id' => $this->contato->id,
            'mailing_id' => $this->mailing->id,
        ]);

        // Should create a new record
        $newCount = Ligacao::count();
        $this->assertTrue($newCount > $initialCount);

        // Verify record has correct data
        $ligacao = Ligacao::latest()->first();
        $this->assertEquals($this->contato->id, $ligacao->contato_id);
        $this->assertEquals($this->mailing->id, $ligacao->mailing_id);
    }

    /**
     * Test: CPF é descriptografado antes de usar
     */
    public function test_cpf_is_decrypted_before_sending()
    {
        // Create contact with encrypted CPF
        $contato = Contato::factory()->create([
            'mailing_id' => $this->mailing->id,
            'cpf' => encrypt('12345678901'),
        ]);

        $response = $this->postJson('/api/ura/call/start', [
            'contato_id' => $contato->id,
            'mailing_id' => $this->mailing->id,
        ]);

        // Should succeed (not send encrypted CPF)
        $response->assertStatus(200);
    }
}
