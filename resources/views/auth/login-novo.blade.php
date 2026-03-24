<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() ?? '' }}">
    <title>Login - AdoraCall</title>

    <link rel="stylesheet" href="{{ asset('css/notion.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 48px 40px;
            animation: slideUp 0.6s ease-out;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-logo {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: #37352f;
            margin-bottom: 8px;
        }

        .login-subtitle {
            font-size: 14px;
            color: #9b9a97;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-field label {
            font-size: 13px;
            font-weight: 600;
            color: #37352f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-field input {
            padding: 12px 14px;
            border: 1px solid #e5e3e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
        }

        .form-field input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-field input::placeholder {
            color: #9b9a97;
        }

        .btn-login {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .error-message {
            padding: 12px 14px;
            background-color: #ffe8e8;
            border: 1px solid #d93026;
            border-radius: 8px;
            color: #d93026;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            animation: slideUp 0.3s ease;
        }

        .error-icon {
            font-size: 16px;
            flex-shrink: 0;
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e3e0;
        }

        .login-footer p {
            font-size: 13px;
            color: #626161;
            margin-bottom: 0;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .login-footer a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .login-divider {
            text-align: center;
            margin: 24px 0;
            position: relative;
        }

        .login-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e3e0;
        }

        .login-divider span {
            background: white;
            padding: 0 12px;
            color: #9b9a97;
            font-size: 12px;
            position: relative;
        }

        .features {
            display: flex;
            gap: 16px;
            margin-top: 32px;
            padding-top: 32px;
            border-top: 1px solid #e5e3e0;
        }

        .feature-item {
            flex: 1;
            text-align: center;
        }

        .feature-icon {
            font-size: 32px;
            margin-bottom: 8px;
            opacity: 0.7;
        }

        .feature-text {
            font-size: 12px;
            color: #9b9a97;
            line-height: 1.4;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }

            .login-title {
                font-size: 24px;
            }

            .login-logo {
                font-size: 40px;
            }

            .features {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <span class="login-logo">📞</span>
                <h1 class="login-title">AdoraCall</h1>
                <p class="login-subtitle">Plataforma de Cobrança Inteligente</p>
            </div>

            <div id="errorMessage" class="error-message" style="display: none;">
                <span class="error-icon"><i class="fas fa-exclamation-circle"></i></span>
                <span id="errorText"></span>
            </div>

            <form id="loginForm" class="login-form">
                <div class="form-field">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="seu@email.com"
                        required
                        autofocus
                    >
                </div>

                <div class="form-field">
                    <label for="password">Senha</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>

            <div class="login-divider">
                <span>ou</span>
            </div>

            <div class="login-footer">
                <p>Não tem conta? <a href="{{ route('auth.register') }}">Registre-se aqui</a></p>
            </div>

            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">🤖</div>
                    <div class="feature-text">IA Inteligente</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <div class="feature-text">Analytics</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⚡</div>
                    <div class="feature-text">Rápido</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = '{{ config("app.api_url") ?? url("/api") }}';

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btnLogin = document.getElementById('btnLogin');
            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');

            btnLogin.disabled = true;
            const originalContent = btnLogin.innerHTML;
            btnLogin.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
            errorMessage.style.display = 'none';

            try {
                const response = await fetch(`${API_BASE_URL}/auth/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        email: document.getElementById('email').value,
                        password: document.getElementById('password').value,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    const errMsg = typeof data.error === 'string' ? data.error : (data.message || 'Erro ao fazer login');
                    throw new Error(errMsg);
                }

                const token = data.data?.token;
                const user = data.data?.user;
                const permissions = data.data?.permissions;

                if (!token || !user) {
                    throw new Error('Resposta inválida do servidor');
                }

                localStorage.setItem('api_token', token);
                localStorage.setItem('user', JSON.stringify(user));
                localStorage.setItem('permissions', JSON.stringify(permissions || []));

                // Redirecionar após sucesso
                setTimeout(() => {
                    window.location.href = '/dashboard';
                }, 500);

            } catch (error) {
                errorText.textContent = error.message;
                errorMessage.style.display = 'flex';
                btnLogin.disabled = false;
                btnLogin.innerHTML = originalContent;
            }
        });
    </script>
</body>
</html>
