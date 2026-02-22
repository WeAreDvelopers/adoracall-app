/**
 * Profile Page Script
 * Valida autenticação e carrega dados do usuário
 */

(function() {
    // Função para validar se o usuário está logado
    function checkAuth() {
        const token = localStorage.getItem('api_token');

        if (!token) {
            console.warn('❌ Token não encontrado. Redirecionando para login...');
            window.location.href = '/login';
            return false;
        }

        console.log('✅ Token encontrado. Usuário autenticado.');
        return true;
    }

    // Função para carregar dados do usuário no frontend
    function loadUserInfo() {
        const user = window.safeParseJSON(localStorage.getItem('user'), null);

        if (user && document.getElementById('userName')) {
            document.getElementById('userName').textContent = user.name;
        }
    }

    // Validar autenticação quando o DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        if (!checkAuth()) {
            return;
        }

        loadUserInfo();

        // Validar token a cada 30 segundos
        setInterval(function() {
            const token = localStorage.getItem('api_token');
            if (!token) {
                console.warn('❌ Sessão expirada. Redirecionando para login...');
                window.location.href = '/login';
            }
        }, 30000);
    });

    // Expor função de logout global
    window.logoutUser = function() {
        localStorage.removeItem('api_token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    };
})();
