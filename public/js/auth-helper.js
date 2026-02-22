/**
 * Helper de Autenticação
 * Gerencia usuário autenticado e logout
 */

(function() {
    // Carregar dados do usuário (se forem necessários no frontend)
    // O acesso às páginas é controlado pelo middleware do Laravel

    window.logout = async function() {
        try {
            const response = await fetch(`${API_BASE_URL}/auth/logout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                },
            });

            // Limpar dados locais
            localStorage.removeItem('api_token');
            localStorage.removeItem('user');

            // Redirecionar para home
            setTimeout(() => {
                window.location.href = '/';
            }, 500);

        } catch (error) {
            console.error('Erro ao fazer logout:', error);

            // Mesmo com erro, limpar dados e redirecionar
            localStorage.removeItem('api_token');
            localStorage.removeItem('user');
            window.location.href = '/';
        }
    };

    // Função para carregar dados do usuário na página
    window.loadUserInfo = function() {
        const user = window.safeParseJSON(localStorage.getItem('user'), null);
        if (user && document.getElementById('userName')) {
            document.getElementById('userName').textContent = user.name;
        }
    };

    // Executar quando DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        loadUserInfo();
    });
})();
