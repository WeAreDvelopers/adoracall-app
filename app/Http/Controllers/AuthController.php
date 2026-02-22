<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ApiResponseService;
use App\Services\JwtService;
use App\Services\ValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Login - retorna API token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->toArray());
        }

        // Validate email format
        $email = ValidationService::validateEmail($request->email);
        if (! $email) {
            return ApiResponseService::validationError(['email' => 'Email inválido']);
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Log failed login attempt for security
            ApiResponseService::logSecurityEvent('failed_login_attempt', 'warning', [
                'email'  => $email,
                'reason' => ! $user ? 'user_not_found' : 'password_mismatch',
            ]);

            return ApiResponseService::unauthorized('Credenciais inválidas');
        }

        // Check if user is active
        if (! $user->active) {
            return ApiResponseService::forbidden('Usuário desativado. Contate o administrador.');
        }

        // Carregar empresa do usuário
        $user->load('empresa');

        // Generate JWT token with role and empresa
        $jwtService = new JwtService();
        $tokenPayload = [
            'user_id' => $user->id,
            'email'   => $user->email,
            'name'    => $user->name,
            'role'    => $user->role,
        ];

        if ($user->empresa) {
            $tokenPayload['empresa_id'] = $user->empresa_id;
            $tokenPayload['empresa_nome'] = $user->empresa->nome;
        }

        $token = $jwtService->generate($tokenPayload);

        // Log successful login
        ApiResponseService::logAction('login', 'User', $user->id, $user->id, [
            'email' => $user->email,
        ]);

        $responseData = [
            'user'        => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
            'token'       => $token,
            'permissions' => $user->getRolePermissions(),
        ];

        if ($user->empresa) {
            $responseData['empresa'] = [
                'id'   => $user->empresa->id,
                'nome' => $user->empresa->nome,
                'slug' => $user->empresa->slug,
            ];
        }

        return ApiResponseService::success($responseData, 'Login realizado com sucesso', 200);
    }

    /**
     * Register - cria novo usuário
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return ApiResponseService::validationError($validator->errors()->toArray());
        }

        // Validate email format
        $email = ValidationService::validateEmail($request->email);
        if (! $email) {
            return ApiResponseService::validationError(['email' => 'Email inválido']);
        }

        // Validate name
        $name = ValidationService::validateString($request->name, 1, 255);
        if (! $name) {
            return ApiResponseService::validationError(['name' => 'Nome deve ter entre 1 e 255 caracteres']);
        }

        try {
            // Atribuir à empresa padrão (ignora empresa_id do request por segurança)
            $empresaPadrao = \App\Models\Empresa::where('slug', 'empresa-padrao')->first();
            $empresaId = $empresaPadrao ? $empresaPadrao->id : null;

            $user = User::create([
                'name'       => $name,
                'email'      => $email,
                'password'   => Hash::make($request->password),
                'active'     => true,
                'role'       => 'operador', // Default role
                'empresa_id' => $empresaId,
            ]);

            $user->load('empresa');

            // Generate JWT token
            $jwtService = new JwtService();
            $tokenPayload = [
                'user_id' => $user->id,
                'email'   => $user->email,
                'name'    => $user->name,
                'role'    => $user->role,
            ];

            if ($user->empresa) {
                $tokenPayload['empresa_id'] = $user->empresa_id;
                $tokenPayload['empresa_nome'] = $user->empresa->nome;
            }

            $token = $jwtService->generate($tokenPayload);

            // Log user creation
            ApiResponseService::logAction('register', 'User', $user->id, $user->id, [
                'email' => $user->email,
                'name'  => $user->name,
            ]);

            $responseData = [
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
                'token' => $token,
            ];

            if ($user->empresa) {
                $responseData['empresa'] = [
                    'id'   => $user->empresa->id,
                    'nome' => $user->empresa->nome,
                    'slug' => $user->empresa->slug,
                ];
            }

            return ApiResponseService::success($responseData, 'Usuário criado com sucesso', 201);
        } catch (\Exception $e) {
            Log::error('User registration error', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return ApiResponseService::serverError('Erro ao criar usuário');
        }
    }

    /**
     * Logout - invalida API token (JSON ou redirect)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->api_token = null;
            $user->save();

            // Log logout action
            ApiResponseService::logAction('logout', 'User', $user->id, $user->id);
        }

        // Se a requisição espera JSON, retorna JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponseService::success(null, 'Logout realizado com sucesso');
        }

        // Caso contrário, redireciona para home
        return redirect()->route('home')->with('success', 'Logout realizado com sucesso');
    }

    /**
     * Me - retorna usuário autenticado
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponseService::unauthorized('Usuário não autenticado');
        }

        $user->load('empresa');

        $userData = [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'role'   => $user->role,
            'active' => $user->active,
        ];

        $responseData = ['user' => $userData];

        if ($user->empresa) {
            $responseData['empresa'] = [
                'id'   => $user->empresa->id,
                'nome' => $user->empresa->nome,
                'slug' => $user->empresa->slug,
            ];
        }

        return ApiResponseService::success($responseData, 'Usuário recuperado com sucesso');
    }

    /**
     * Exibe formulário de login
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Exibe formulário de registro
     *
     * @return \Illuminate\View\View
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }
}
