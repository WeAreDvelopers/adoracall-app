<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    /**
     * Test: Login com credenciais válidas
     */
    public function test_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        // Should return 200
        $response->assertStatus(200);

        // Should return user data and token
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
                'token',
                'permissions',
            ]
        ]);

        // Verify user data
        $this->assertEquals($user->id, $response['data']['user']['id']);
        $this->assertEquals('admin@test.com', $response['data']['user']['email']);

        // Verify token exists
        $this->assertNotEmpty($response['data']['token']);
    }

    /**
     * Test: Login com email inválido
     */
    public function test_login_with_invalid_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password123',
        ]);

        // Should return 401
        $response->assertStatus(401);
        $response->assertJsonStructure(['success', 'message']);
    }

    /**
     * Test: Login com senha inválida
     */
    public function test_login_with_invalid_password()
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong_password',
        ]);

        // Should return 401
        $response->assertStatus(401);
    }

    /**
     * Test: Login com usuário desativado
     */
    public function test_login_with_inactive_user()
    {
        User::factory()->create([
            'email' => 'inactive@test.com',
            'password' => Hash::make('password123'),
            'active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'inactive@test.com',
            'password' => 'password123',
        ]);

        // Should return 403 Forbidden
        $response->assertStatus(403);
    }

    /**
     * Test: Login com email em formato inválido
     */
    public function test_login_with_invalid_email_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not_an_email',
            'password' => 'password123',
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Login sem email
     */
    public function test_login_without_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123',
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Login sem senha
     */
    public function test_login_without_password()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Registrar novo usuário com dados válidos
     */
    public function test_register_with_valid_data()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'João Silva',
            'email' => 'joao@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Should return 201 Created
        $response->assertStatus(201);

        // Should return user and token
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
                'token',
            ]
        ]);

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'joao@test.com',
            'name' => 'João Silva',
        ]);
    }

    /**
     * Test: Registrar com email duplicado
     */
    public function test_register_with_duplicate_email()
    {
        User::factory()->create([
            'email' => 'existing@test.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Outro Usuário',
            'email' => 'existing@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Registrar com senhas não correspondentes
     */
    public function test_register_with_mismatched_passwords()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'João Silva',
            'email' => 'joao@test.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Registrar sem nome
     */
    public function test_register_without_name()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'joao@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Novo usuário tem role padrão (operator)
     */
    public function test_new_user_has_default_role()
    {
        $this->postJson('/api/auth/register', [
            'name' => 'João Silva',
            'email' => 'joao@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'joao@test.com')->first();
        $this->assertEquals('operator', $user->role);
    }

    /**
     * Test: Novo usuário começa ativo
     */
    public function test_new_user_is_active()
    {
        $this->postJson('/api/auth/register', [
            'name' => 'João Silva',
            'email' => 'joao@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'joao@test.com')->first();
        $this->assertTrue($user->active);
    }

    /**
     * Test: Logout limpa o token
     */
    public function test_logout()
    {
        $user = User::factory()->create([
            'api_token' => 'test_token_123',
        ]);

        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer test_token_123',
        ]);

        // Should return 200
        $response->assertStatus(200);

        // Verify token was cleared
        $user->refresh();
        $this->assertNull($user->api_token);
    }

    /**
     * Test: GET /api/auth/me retorna usuário autenticado
     */
    public function test_get_authenticated_user()
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'role' => 'admin',
        ]);

        // Mock authentication
        $this->actingAs($user);

        $response = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer test_token',
        ]);

        // Should return 200
        $response->assertStatus(200);

        // Verify user data
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'active',
                ]
            ]
        ]);
    }

    /**
     * Test: GET /api/auth/me retorna erro se não autenticado
     */
    public function test_get_user_without_authentication()
    {
        $response = $this->getJson('/api/auth/me');

        // Should return 401
        $response->assertStatus(401);
    }

    /**
     * Test: Login retorna permissões do usuário
     */
    public function test_login_returns_user_permissions()
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        // Should include permissions
        $response->assertJsonStructure(['data' => ['permissions']]);
    }

    /**
     * Test: Email validation é stricto
     */
    public function test_email_validation_rejects_invalid_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid.email@',
            'password' => 'password123',
        ]);

        // Should return 422
        $response->assertStatus(422);
    }

    /**
     * Test: Token é JWT válido
     */
    public function test_login_returns_valid_jwt_token()
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $token = $response['data']['token'];

        // Token should have 3 parts (JWT format: header.payload.signature)
        $parts = explode('.', $token);
        $this->assertEquals(3, count($parts));
    }
}
