<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - URA Dvelopers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-container {
            max-width: 450px;
            width: 100%;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .auth-header p {
            color: #999;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-auth {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-auth:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-auth:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
            font-size: 14px;
        }

        .error-message.show {
            display: block;
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .auth-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h2 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .logo p {
            color: #999;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="logo">
            <h2>🎯 We Are Dvelopers</h2>
            <p>Sistema de URA - Dvelopers AI</p>
        </div>

        <div class="auth-header">
            <h1>🔐 Login</h1>
            <p>Entre com suas credenciais</p>
        </div>

        <div id="errorMessage" class="error-message"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-auth" id="btnLogin">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div class="auth-footer">
            Não tem conta? <a href="/register">Registre-se aqui</a>
        </div>
    </div>

    <script src="/js/config.js"></script>
    <script src="/js/feedback.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btnLogin = document.getElementById('btnLogin');
            const errorMessage = document.getElementById('errorMessage');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            btnLogin.disabled = true;
            btnLogin.textContent = 'Entrando...';
            errorMessage.classList.remove('show');

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
                console.log('📋 Resposta completa do servidor:', data);
                console.log('📦 Estrutura data.data:', data.data);
                console.log('🔑 Token:', data.data?.token);

                if (!response.ok) {
                    const errorText = typeof data.error === 'string' ? data.error : (data.message ||
                        'Erro ao fazer login');
                    throw new Error(errorText);
                }

                // Salvar token no localStorage
                const token = data.data?.token;
                const user = data.data?.user;
                const permissions = data.data?.permissions;

                console.log('💾 Salvando no localStorage - Token:', token);
                console.log('💾 Salvando no localStorage - User:', user);
                console.log('💾 Salvando no localStorage - Permissions:', permissions);

                if (!token || !user) {
                    throw new Error('Resposta inválida do servidor - faltam dados');
                }

                localStorage.setItem('api_token', token);
                localStorage.setItem('user', JSON.stringify(user));
                localStorage.setItem('permissions', JSON.stringify(permissions || []));

                console.log('✅ Token, user e permissions armazenados no localStorage');

                // 🔍 DEBUG: Validar o que foi realmente salvo
                console.log('🔍 VERIFICANDO LOCALSTORAGE:');
                console.log('   api_token:', localStorage.getItem('api_token'));
                console.log('   user:', localStorage.getItem('user'));
                console.log('   permissions:', localStorage.getItem('permissions'));

                // Exibir todos os itens do localStorage
                console.log('📋 Todos os itens do localStorage:');
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    const value = localStorage.getItem(key);
                    console.log(`   ${key}:`, value);
                }

                // ⏸️ PARADO PARA DEBUG - Descomente para redirecionar
                window.location.href = '/';

                // alert('✅ Login realizado! Verifique o console para os dados do localStorage.');

            } catch (error) {
                errorMessage.innerHTML = '<i class="fas fa-exclamation-circle"></i> <span>' + error.message +
                    '</span>';
                errorMessage.style.display = 'flex';
                btnLogin.disabled = false;
                btnLogin.innerHTML = originalContent;
            }
        });
    </script>
</body>

</html>
