-- ============================================
-- Usuário Admin Padrão
-- ============================================
-- Inserir usuário admin padrão
-- Senha: admin123 (Hash bcrypt)

INSERT IGNORE INTO users (name, email, password, role, active, created_at, updated_at)
VALUES (
    'Administrador',
    'admin@dvelopers.com.br',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    true,
    NOW(),
    NOW()
);

-- ============================================
-- Instruções de Mudança de Senha
-- ============================================
-- Para alterar a senha do admin, use um destes métodos:
--
-- 1. Via Laravel Tinker:
--    docker-compose exec php php artisan tinker
--    $user = App\Models\User::where('email', 'admin@dvelopers.com.br')->first();
--    $user->password = bcrypt('sua_nova_senha');
--    $user->save();
--
-- 2. Gerar um hash bcrypt online em: https://bcrypt.online/
--    E atualize direto no banco:
--    UPDATE users SET password = 'hash_aqui' WHERE email = 'admin@dvelopers.com.br';
