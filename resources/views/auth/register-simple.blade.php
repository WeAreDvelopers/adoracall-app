<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar - URA Dvelopers</title>
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
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
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
            <p>Sistema de URA - Retell AI</p>
        </div>

        <div class="auth-header">
            <h1>📝 Registrar</h1>
            <p>Crie sua conta</p>
        </div>

        <div id="errorMessage" class="error-message"></div>

        <form id="registerForm">
            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" id="name" name="name" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirmar Senha</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required>
            </div>

            <button type="submit" class="btn-auth" id="btnRegister">
                <i class="fas fa-user-plus"></i> Registrar
            </button>
        </form>

        <div class="auth-footer">
            Já tem conta? <a href="/login">Faça login aqui</a>
        </div>
    </div>

    <script src="/js/config.js"></script>
    <script src="/js/feedback.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btnRegister = document.getElementById('btnRegister');
            const errorMessage = document.getElementById('errorMessage');
            const password = document.getElementById('password').value;
            const passwordConfirmation = document.getElementById('password_confirmation').value;

            if (password !== passwordConfirmation) {
                errorMessage.textContent = 'As senhas não correspondem';
                errorMessage.classList.add('show');
                return;
            }

            btnRegister.disabled = true;
            btnRegister.textContent = 'Registrando...';
            errorMessage.classList.remove('show');

            try {
                const response = await fetch(`${API_BASE_URL}/auth/register`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: document.getElementById('name').value,
                        email: document.getElementById('email').value,
                        password: password,
                        password_confirmation: passwordConfirmation,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    const errorText = typeof data.error === 'string' ? data.error : JSON.stringify(data.error);
                    throw new Error(errorText || 'Erro ao registrar');
                }

                // Salvar token e usuário no localStorage
                localStorage.setItem('api_token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));

                // Mostrar sucesso
                if (typeof feedback !== 'undefined') {
                    feedback.success('Usuário criado com sucesso! Redirecionando...');
                }

                // Redirecionar para home
                setTimeout(() => {
                    window.location.href = '/';
                }, 1500);

            } catch (error) {
                errorMessage.textContent = error.message;
                errorMessage.classList.add('show');
                console.error('Erro ao registrar:', error);
                btnRegister.disabled = false;
                btnRegister.textContent = 'Registrar';
            }
        });
    </script>
</body>
</html>
